# Private Office AI — Self-Hosted Knowledge & RAG Vault

An enterprise-grade, self-hosted **Private Office AI (Retrieval-Augmented Generation)** platform built with **Laravel 13**, **MySQL 8**, **Docker**, and **Local Ollama**.

All documents, vector embeddings, and AI inference run 100% on-premises without sending confidential company data to external cloud APIs.

---

## Core Features

- **Role-Based Access Control (RBAC):** Pre-configured roles (`Super Admin`, `Admin`, `HR Manager`, `Finance Manager`, `Department Manager`, `Employee`) with 16 granular permissions.
- **Departmental Siloing:** Organizational units (`Administration`, `HR`, `Finance`, `IT`, `Development`, `Sales`, `Operations`) with manager assignments.
- **4-Level Document Confidentiality Policy:**
  - `Public Internal` — Accessible to all active employees.
  - `Department Restricted` — Accessible only to members of the owning department.
  - `Confidential` — Restricted to authorized managers and admins.
  - `Highly Confidential` — Restricted strictly to the uploader, Super Admin, and explicitly granted users.
- **Private Encrypted-at-Rest Vault:** Files (`PDF`, `DOCX`, `TXT`, `XLSX`, `CSV`) are stored outside public web roots (`storage/app/private/documents/`) with SHA-256 checksum deduplication.
- **Permission-Aware RAG Assistant:**
  - Automatically extracts text, splits into overlapping UTF-8 chunks, and generates vector embeddings via local Ollama (`nomic-embed-text`).
  - Pre-filters chunks strictly against user permissions before retrieval to prevent cross-department data leaks.
  - Supports **Bengali & English** queries with verifiable inline source citations (`[1] Document Title • Pg X`).
  - Zero-reload SPA-style chat interface with conversation history and response feedback.
- **Comprehensive Security Audit Logs:** Tracks logins, failed access attempts, document uploads/downloads, and AI queries.

---

## Tech Stack

- **Backend:** PHP 8.3, Laravel 13
- **Database:** MySQL 8.0
- **Web Server:** Nginx + PHP-FPM (Dockerized)
- **Frontend:** Blade, Tailwind CSS, Vite, Vanilla JS (AJAX)
- **Local AI Runtime:** Ollama (`qwen2.5:3b` or `qwen3:8b` for chat, `nomic-embed-text` for embeddings)

---

## Prerequisites

Ensure the following are installed on your host machine:
1. **Docker** and **Docker Compose**
2. **Ollama** installed and running on the host machine ([https://ollama.com](https://ollama.com))

---

## Step-by-Step Setup Guide

### 1. Clone the Repository
    git clone <your-repository-url>
    cd private-office-ai

### 2. Configure Host Ollama for Docker Access
Allow Docker containers (`host.docker.internal`) to communicate with Ollama on port `11434`, then pull the required models:

    sudo mkdir -p /etc/systemd/system/ollama.service.d
    printf "[Service]\nEnvironment=\"OLLAMA_HOST=0.0.0.0:11434\"\n" | sudo tee /etc/systemd/system/ollama.service.d/override.conf
    sudo systemctl daemon-reload
    sudo systemctl restart ollama

    ollama pull nomic-embed-text
    ollama pull qwen2.5:3b

*(Optional: If your server has a dedicated GPU, pull `qwen3:8b` and set `OLLAMA_CHAT_MODEL=qwen3:8b` in `src/.env`.)*

### 3. Configure Environment Variables
Copy the example environment file inside `src/`:

    cp src/.env.example src/.env

Ensure the following values are configured in `src/.env`:

    APP_NAME="Private Office AI"
    APP_URL=http://localhost:8085

    DB_CONNECTION=mysql
    DB_HOST=poai_mysql
    DB_PORT=3306
    DB_DATABASE=private_office_ai
    DB_USERNAME=ziadul
    DB_PASSWORD=secret

    QUEUE_CONNECTION=database

    OLLAMA_BASE_URL=[http://host.docker.internal:11434](http://host.docker.internal:11434)
    OLLAMA_CHAT_MODEL=qwen2.5:3b
    OLLAMA_EMBEDDING_MODEL=nomic-embed-text
    OLLAMA_TIMEOUT=600

### 4. Start Docker Containers
    docker compose up -d --build

### 5. Install Dependencies, Run Migrations & Seed Initial Data
    docker exec -it poai_app composer install
    docker exec -it poai_app npm install && docker exec -it poai_app npm run build

    docker exec -it poai_app php artisan key:generate
    docker exec -it poai_app mkdir -p storage/framework/views storage/framework/cache storage/framework/sessions storage/logs storage/app/private/documents
    docker exec -it poai_app chmod -R 777 storage bootstrap/cache

    docker exec -it poai_app php artisan migrate --seed

### 6. Start the Background Queue Worker
Document text extraction and vector embedding run asynchronously via Laravel queues:

    docker exec -d poai_app php artisan queue:work --sleep=3 --tries=2

---

## Default Login Credentials

Open **http://localhost:8085** in your browser and sign in with the seeded Super Admin account:

- **Email:** `admin@office.local`
- **Password:** `Admin@12345`

> **Security Note:** Change the Super Admin password immediately after first login in production. Public registration is disabled by design; new employee accounts must be created by an Administrator.

---

## Running Automated Security Tests

    docker exec -it poai_app php artisan test

---

## Project Structure

    private-office-ai/
    ├── docker/                  # Nginx & PHP-FPM Docker configurations
    ├── docker-compose.yml       # Container orchestration (App, Nginx, MySQL)
    └── src/                     # Laravel 13 Application
        ├── app/
        │   ├── Contracts/       # Parser interfaces (Go-service ready)
        │   ├── Http/            # Controllers & RBAC/Active-user Middleware
        │   ├── Jobs/            # Background document chunking & embedding jobs
        │   ├── Models/          # Eloquent models (User, Document, ChatSession, etc.)
        │   ├── Policies/        # 4-tier Document confidentiality authorization
        │   └── Services/        # Audit, Document Vault, Ollama Client & RAG Engine
        ├── database/            # Migrations & Office RBAC Seeders
        └── resources/views/     # Blade UI (Dashboard, Vault, Users, AI Assistant)
