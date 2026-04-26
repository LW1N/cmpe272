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
      image: alpine/git:2.45.2
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
        IMAGE = 'docker.io/lw1n/pap_app'
        IMAGE_TAG = ''
        GIT_REPO_GITOPS = 'git@github.com:LW1N/selfhosted-webapps.git'
        KUSTOMIZATION_FILE = 'apps/pap_app/kustomization.yaml'
    }

    stages {

        // Only build image and update GitOps when this repo (app) has a new commit from a
        // non-robot author. Skips when the trigger is a jenkins-ci commit to avoid loops.
        stage('Check commit author') {
            steps {
                script {
                    def commitAuthor = sh(
                        script: 'git log -1 --format=%an',
                        returnStdout: true
                    ).trim()
                    env.COMMIT_AUTHOR = commitAuthor
                    if (commitAuthor == 'jenkins-ci') {
                        echo "Commit by jenkins-ci — skipping build and GitOps update."
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
                    def shortSha = sh(
                        script: 'git rev-parse --short HEAD',
                        returnStdout: true
                    ).trim()
                    if (!shortSha) {
                        error 'Failed to determine git commit SHA.'
                    }
                    def imageTag = "sha-${shortSha}"
                    env.IMAGE_TAG = imageTag
                    writeFile file: '.image-tag', text: imageTag
                }
            }
        }

        stage('Test') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('test') {
                    sh '''
                    echo "Running PHP lint..."
                    find . -name "*.php" -not -path "./.git/*" -print0 | xargs -0 -n1 php -l
                    echo "Running unit tests..."
                    php tests/run_tests.php
                    '''
                }
            }
        }

        stage('Build & Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('kaniko') {
                    script {
                        withEnv(["IMAGE_TAG=${env.IMAGE_TAG}"]) {
                            sh '''
                            # Recover IMAGE_TAG from workspace file if the env var didn't propagate.
                            if [ -z "${IMAGE_TAG}" ] || [ "${IMAGE_TAG}" = "null" ]; then
                                if [ -f .image-tag ]; then
                                    export IMAGE_TAG=$(cat .image-tag)
                                fi
                            fi
                            if [ -z "${IMAGE_TAG}" ] || [ "${IMAGE_TAG}" = "null" ]; then
                                short_sha=$(git rev-parse --short HEAD)
                                export IMAGE_TAG="sha-${short_sha}"
                                printf '%s' "${IMAGE_TAG}" > .image-tag
                            fi

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
            }
        }

        stage('Update GitOps and Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('git') {
                    script {
                        withEnv(["IMAGE_TAG=${env.IMAGE_TAG}"]) {
                    sh '''
                    set -euo pipefail

                    # env.IMAGE_TAG may not propagate into the container; use workspace file from Prepare tags.
                    if [ -z "${IMAGE_TAG}" ] || [ "${IMAGE_TAG}" = "null" ]; then
                        if [ -f .image-tag ]; then
                            export IMAGE_TAG=$(cat .image-tag)
                        fi
                    fi
                    if [ -z "${IMAGE_TAG}" ] || [ "${IMAGE_TAG}" = "null" ]; then
                        short_sha=$(git rev-parse --short HEAD)
                        export IMAGE_TAG="sha-${short_sha}"
                        printf '%s' "${IMAGE_TAG}" > .image-tag
                    fi

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

                    ssh-keyscan -4 -t ed25519 github.com > "$HOME/.ssh/known_hosts" 2>&1
                    if [ ! -s "$HOME/.ssh/known_hosts" ]; then
                        echo "ssh-keyscan failed to fetch GitHub host keys." >&2
                        exit 1
                    fi
                    chmod 644 "$HOME/.ssh/known_hosts"

                    # Use GitHub's ED25519 host key and let OpenSSH negotiate the
                    # rest. The Bitnami git image/cipher override has failed with
                    # "incorrect signature" during the SSH handshake.
                    cat > "$HOME/.ssh/config" <<SSHEOF
Host github.com
  HostName github.com
  Port 22
  User git
  IdentityFile $HOME/.ssh/id_ed25519
  IdentitiesOnly yes
  AddressFamily inet
  HostKeyAlgorithms ssh-ed25519
  StrictHostKeyChecking yes
  UserKnownHostsFile $HOME/.ssh/known_hosts
SSHEOF
                    chmod 600 "$HOME/.ssh/config"

                    export GIT_SSH_COMMAND="ssh -F $HOME/.ssh/config -o IdentitiesOnly=yes"
                    git --version
                    ssh -V

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
                    if git diff --cached --quiet; then
                        echo "Kustomization newTag already ${IMAGE_TAG}; nothing to push."
                        exit 0
                    fi

                    git -c user.name="jenkins-ci" -c user.email="jenkins@selfhosted-webapps.local" \
                        commit -m "deploy: update pap_app image to ${IMAGE_TAG}"

                    push_ok=false
                    for attempt in 1 2 3; do
                        if git push origin main; then
                            push_ok=true
                            break
                        fi
                        echo "Push attempt $attempt failed, retrying in 5s..." >&2
                        sleep 5
                        git pull --rebase origin main
                    done
                    if [ "$push_ok" != "true" ]; then
                        echo "All push attempts failed." >&2
                        exit 1
                    fi
                    '''
                        }
                    }
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
                    echo "Image ${env.IMAGE_TAG} built and pushed; GitOps kustomization updated and pushed when changed."
                }
            }
        }
        failure {
            echo 'Build failed. Check logs.'
        }
    }
}
