# System Architecture — Private Office AI
- **Phase 1 Strategy:** Laravel Modular Monolith (PHP 8.3, Laravel, Blade, Vite, Tailwind CSS)
- **Web Server & Runtime:** Docker (`poai_nginx` on port 8085, `poai_app` PHP-FPM + Node 20)
- **Primary Database:** Host MySQL 8.0 (`private_office_ai` via `host.docker.internal:3306`)
- **Local AI Engine (Phase 3+):** Host Ollama (`qwen3:8b` via `host.docker.internal:11434`)
- **Future Scaling (Phase 4):** Selective extraction of Document Processing, Embeddings, and RAG into Go microservices.
