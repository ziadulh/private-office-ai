pipeline {
    agent any

    environment {
        IMAGE_NAME = "private-office-ai-poai_app"
        IMAGE_TAG  = "${env.BUILD_NUMBER}"
    }

    stages {
        stage('1. Checkout Code') {
            steps {
                checkout scm
            }
        }

        stage('2. Build Docker Image') {
            steps {
                sh "docker build -t ${IMAGE_NAME}:${IMAGE_TAG} -t ${IMAGE_NAME}:latest ."
            }
        }

        stage('3. Run Laravel Tests') {
            steps {
                sh "docker run --rm -e APP_ENV=testing -e APP_KEY=base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10= ${IMAGE_NAME}:${IMAGE_TAG} sh -c 'cp -n .env.example .env || true && php artisan test'"
            }
        }

        stage('4. Deploy Application') {
            steps {
                sh """
                    docker network create poai_network || true
                    docker volume create poai_public_data || true
                    docker rm -f poai_app poai_nginx || true

                    docker run -d \
                      --name poai_app \
                      --network poai_network \
                      -v poai_public_data:/var/www/html/public \
                      -e APP_KEY=base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10= \
                      ${IMAGE_NAME}:latest

                    docker run -d \
                      --name poai_nginx \
                      --network poai_network \
                      -p 8085:80 \
                      -v poai_public_data:/var/www/html/public:ro \
                      nginx:alpine
                """
            }
        }
    }

    post {
        always {
            sh "docker image prune -f"
        }
    }
}