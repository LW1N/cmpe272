pipeline {
    agent {
        kubernetes {
            yaml '''
apiVersion: v1
kind: Pod
metadata:
  labels:
    jenkins: agent
spec:
  serviceAccountName: jenkins-agent
  containers:
    - name: kaniko
      image: gcr.io/kaniko-project/executor:debug
      command: ["/busybox/cat"]
      tty: true
      volumeMounts:
        - name: docker-config
          mountPath: /kaniko/.docker
      resources:
        requests:
          cpu: 100m
          memory: 256Mi
        limits:
          cpu: "1"
          memory: 1Gi
    - name: test
      image: php:8.2-cli
      command: ["cat"]
      tty: true
      resources:
        requests:
          cpu: 50m
          memory: 128Mi
        limits:
          cpu: 250m
          memory: 256Mi
    - name: git
      image: bitnami/git:latest
      command: ["cat"]
      tty: true
      volumeMounts:
        - name: git-ssh
          mountPath: /etc/git-secret
          readOnly: true
      resources:
        requests:
          cpu: 50m
          memory: 64Mi
        limits:
          cpu: 250m
          memory: 128Mi
  volumes:
    - name: docker-config
      secret:
        secretName: dockerhub-credentials
        items:
          - key: .dockerconfigjson
            path: config.json
    - name: git-ssh
      secret:
        secretName: git-ssh-key
        defaultMode: 0400
'''
        }
    }

    options {
        disableConcurrentBuilds()
        timeout(time: 15, unit: 'MINUTES')
        buildDiscarder(logRotator(numToKeepStr: '20'))
    }

    environment {
        IMAGE = 'docker.io/lw1n/php-mysql-demo'
        IMAGE_TAG = ''
        GIT_REPO_GITOPS = 'git@github.com:LW1N/selfhosted-webapps.git'
        KUSTOMIZATION_FILE = 'apps/php-mysql-demo/kustomization.yaml'
    }

    stages {

        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Check commit author') {
            steps {
                script {
                    env.COMMIT_AUTHOR = sh(
                        script: 'git log -1 --format=%an',
                        returnStdout: true
                    ).trim()
                    if (env.COMMIT_AUTHOR == 'jenkins-ci') {
                        echo "Commit by jenkins-ci — skipping build."
                        env.SKIP_BUILD = 'true'
                    } else {
                        env.SKIP_BUILD = 'false'
                    }
                }
            }
        }

        stage('Prepare tags') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                script {
                    env.SHORT_SHA = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    if (!env.SHORT_SHA) {
                        error 'Failed to determine git commit SHA.'
                    }
                    env.IMAGE_TAG = "sha-${env.SHORT_SHA}"
                }
            }
        }

        stage('Test') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('test') {
                    sh 'echo "Running PHP lint..."'
                    sh 'find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l'
                }
            }
        }

        stage('Build & Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('kaniko') {
                    // sh '''...''' prevents Groovy interpolation; the shell expands
                    // $IMAGE and $IMAGE_TAG from the Jenkins environment at runtime.
                    sh '''
                    /kaniko/executor \
                        --context=dir://$(pwd) \
                        --dockerfile=Dockerfile \
                        --destination=${IMAGE}:${IMAGE_TAG} \
                        --destination=${IMAGE}:latest \
                        --cache=true \
                        --cleanup
                    '''
                }
            }
        }

        stage('Update GitOps and Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('git') {
                    // sh '''...''' prevents Groovy interpolation so that every $VAR
                    // reference is expanded by the shell from Jenkins env vars.
                    sh '''
                    set -euo pipefail

                    WORK_DIR=$(mktemp -d)
                    HOME_DIR=$(mktemp -d)
                    trap 'rm -rf "$WORK_DIR" "$HOME_DIR"' EXIT

                    export HOME="$HOME_DIR"
                    mkdir -p "$HOME/.ssh"
                    chmod 700 "$HOME/.ssh"

                    if [ ! -f /etc/git-secret/ssh-privatekey ]; then
                        echo "Missing SSH key at /etc/git-secret/ssh-privatekey (Kubernetes secret: git-ssh-key)." >&2
                        exit 1
                    fi
                    cp /etc/git-secret/ssh-privatekey "$HOME/.ssh/id_ed25519"
                    chmod 600 "$HOME/.ssh/id_ed25519"

                    ssh-keyscan -4 github.com > "$HOME/.ssh/known_hosts" 2>/dev/null
                    chmod 644 "$HOME/.ssh/known_hosts"

                    # chacha20-poly1305 fails with EINVAL in the bitnami/git container; use AES-GCM instead
                    cat > "$HOME/.ssh/config" <<SSHEOF
Host github.com
  HostName github.com
  Port 22
  User git
  IdentityFile $HOME/.ssh/id_ed25519
  IdentitiesOnly yes
  AddressFamily inet
  Ciphers aes256-gcm@openssh.com,aes128-gcm@openssh.com,aes256-ctr,aes192-ctr,aes128-ctr
  StrictHostKeyChecking yes
  UserKnownHostsFile $HOME/.ssh/known_hosts
SSHEOF
                    chmod 600 "$HOME/.ssh/config"

                    export GIT_SSH_COMMAND="ssh -F $HOME/.ssh/config -o IdentitiesOnly=yes"

                    git clone --depth 1 --branch main "${GIT_REPO_GITOPS}" "$WORK_DIR"
                    cd "$WORK_DIR"

                    if [ ! -f "${KUSTOMIZATION_FILE}" ]; then
                        echo "Missing kustomization file: ${KUSTOMIZATION_FILE}" >&2
                        exit 1
                    fi

                    # Update the image tag (portable sed; keeps indentation).
                    # Shell double-quotes let ${IMAGE_TAG} expand; \$ and \\1 are
                    # passed to sed as literal $ (anchor) and \1 (backreference).
                    sed -i.bak -E "s|^([[:space:]]*newTag:[[:space:]]*).*\$|\\1${IMAGE_TAG}|" "${KUSTOMIZATION_FILE}"
                    rm -f "${KUSTOMIZATION_FILE}.bak"

                    git add "${KUSTOMIZATION_FILE}"
                    git diff --cached --quiet && echo "No change to commit" && exit 0

                    git -c user.name="jenkins-ci" -c user.email="jenkins@selfhosted-webapps.local" \
                        commit -m "deploy: update php-mysql-demo image to ${IMAGE_TAG}"

                    for attempt in 1 2 3; do
                        git push origin main && break
                        echo "Push attempt $attempt failed, retrying in 5s..." >&2
                        sleep 5
                        git pull --rebase origin main
                    done
                    '''
                }
            }
        }
    }

    post {
        success {
            script {
                if (env.SKIP_BUILD == 'true') {
                    echo "Skipped (commit by ${env.COMMIT_AUTHOR})."
                } else {
                    echo "Image ${env.IMAGE_TAG} built, pushed, and GitOps repo updated."
                }
            }
        }
        failure {
            echo 'Build failed. Check logs.'
        }
    }
}
