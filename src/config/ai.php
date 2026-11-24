<?php

return [
    "ollama_url" => env("OLLAMA_BASE_URL", "http://host.docker.internal:11434"),
    "chat_model" => env("OLLAMA_CHAT_MODEL", "qwen3:8b"),
    "embedding_model" => env("OLLAMA_EMBEDDING_MODEL", "nomic-embed-text"),
    "timeout" => (int) env("OLLAMA_TIMEOUT", 300),
];