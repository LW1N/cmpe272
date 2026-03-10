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

    environment {
        IMAGE = 'docker.io/lw1n/php-mysql-demo'
        IMAGE_TAG = 'sha-placeholder'
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
                    env.IMAGE_TAG = "sha-${env.SHORT_SHA}"
                }
            }
        }

        stage('Test') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('test') {
                    sh 'echo "Running PHP lint..."'
                    sh 'find . -name "*.php" -exec php -l {} \\;'
                }
            }
        }

        stage('Build & Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('kaniko') {
                    sh """
                    /kaniko/executor \
                        --context=dir://\$(pwd) \
                        --dockerfile=Dockerfile \
                        --destination=${IMAGE}:${IMAGE_TAG} \
                        --destination=${IMAGE}:latest \
                        --cache=true \
                        --cleanup
                    """
                }
            }
        }

        stage('Update GitOps and Push') {
            when { expression { env.SKIP_BUILD != 'true' } }
            steps {
                container('git') {
                    sh """
                    set -euo pipefail

                    export HOME=/var/jenkins_home
                    mkdir -p "\$HOME/.ssh"
                    chmod 700 "\$HOME/.ssh"

                    cp /etc/git-secret/ssh-privatekey "\$HOME/.ssh/id_ed25519"
                    chmod 600 "\$HOME/.ssh/id_ed25519"

                    ssh-keyscan -4 github.com > "\$HOME/.ssh/known_hosts" 2>/dev/null
                    chmod 644 "\$HOME/.ssh/known_hosts"

                    # chacha20-poly1305 fails with EINVAL in the bitnami/git container; use AES-GCM instead
                    cat > "\$HOME/.ssh/config" <<'SSHEOF'
Host github.com
  HostName github.com
  Port 22
  User git
  IdentityFile /var/jenkins_home/.ssh/id_ed25519
  IdentitiesOnly yes
  AddressFamily inet
  Ciphers aes256-gcm@openssh.com,aes128-gcm@openssh.com,aes256-ctr,aes192-ctr,aes128-ctr
  StrictHostKeyChecking yes
  UserKnownHostsFile /var/jenkins_home/.ssh/known_hosts
SSHEOF
                    chmod 600 "\$HOME/.ssh/config"

                    export GIT_SSH_COMMAND="ssh -F \$HOME/.ssh/config -o IdentitiesOnly=yes"

                    git config --global user.name "jenkins-ci"
                    git config --global user.email "jenkins@selfhosted-webapps.local"

                    WORK_DIR=\$(mktemp -d)
                    git clone --depth 1 --branch main ${GIT_REPO_GITOPS} "\$WORK_DIR"
                    cd "\$WORK_DIR"

                    sed -i 's|newTag: .*|newTag: ${IMAGE_TAG}|' ${KUSTOMIZATION_FILE}

                    git add ${KUSTOMIZATION_FILE}
                    git diff --cached --quiet && echo "No change to commit" && rm -rf "\$WORK_DIR" && exit 0

                    git commit -m "deploy: update php-mysql-demo image to ${IMAGE_TAG}"
                    git push origin main

                    rm -rf "\$WORK_DIR"
                    """
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
                    echo "Image ${IMAGE_TAG} built, pushed, and GitOps repo updated."
                }
            }
        }
        failure {
            echo "Build failed. Check logs."
        }
    }
}
