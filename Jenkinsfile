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

                    # ১. অ্যাপ কন্টেইনার চালু করা
                    docker run -d \
                      --name poai_app \
                      --network poai_network \
                      --add-host=host.docker.internal:host-gateway \
                      -v poai_public_data:/var/www/html/public \
                      -e APP_ENV=local \
                      -e APP_DEBUG=true \
                      -e APP_KEY=base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10= \
                      ${IMAGE_NAME}:latest

                    # ২. .env তৈরি, SQLite ফাইল তৈরি ও মাইগ্রেশন চালানো
                    docker exec poai_app sh -c "cp -n .env.example .env || true && mkdir -p database && touch database/database.sqlite && chown -R www-data:www-data database storage bootstrap/cache && php artisan config:clear && php artisan migrate --force"

                    # ৩. Nginx কন্টেইনার চালু ও কাস্টম কনফিগারেশন বসানো
                    docker run -d \
                      --name poai_nginx \
                      --network poai_network \
                      -p 8085:80 \
                      -v poai_public_data:/var/www/html/public:ro \
                      nginx:alpine

                    docker cp docker/nginx/default.conf poai_nginx:/etc/nginx/conf.d/default.conf
                    docker exec poai_nginx nginx -s reload
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