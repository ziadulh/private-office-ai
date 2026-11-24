# Technical Decisions Log
1. **Dockerized App + Host DB/AI:** PHP 8.3, Composer, Node 20, and Nginx run inside Docker (`poai_*`) to keep the Ubuntu 24.04 host clean. MySQL 8.0 and Ollama remain on the host to preserve existing services and GPU/RAM resources.
2. **Port Isolation:** Nginx mapped to port `8085` to avoid conflict with host Nginx (`80`) and Jenkins (`8090`).
3. **LLM Selection:** Host already has `qwen3:8b` installed, which excels in both Bengali and English compared to standard Llama 3.
