<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaClientService
{
    private string $baseUrl;
    private string $chatModel;
    private string $embeddingModel;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config("ai.ollama_url"), "/");
        $this->chatModel = config("ai.chat_model");
        $this->embeddingModel = config("ai.embedding_model");
        $this->timeout = config("ai.timeout", 300);
    }

    public function isHealthy(): bool
    {
        try {
            return Http::timeout(5)->get("{$this->baseUrl}/api/tags")->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function embed(string $text): array
    {
        $response = Http::timeout($this->timeout)->post("{$this->baseUrl}/api/embeddings", [
            "model" => $this->embeddingModel,
            "prompt" => $text,
            "keep_alive" => "30m",
        ]);

        if (!$response->successful()) {
            throw new RuntimeException("Ollama embedding failed: " . $response->body());
        }

        $embedding = $response->json("embedding");
        if (!is_array($embedding) || empty($embedding)) {
            throw new RuntimeException("Invalid embedding response from Ollama.");
        }

        return $embedding;
    }

    public function chat(array $messages): string
    {
        $response = Http::timeout($this->timeout)
            ->connectTimeout(15)
            ->post("{$this->baseUrl}/api/chat", [
                "model" => $this->chatModel,
                "messages" => $messages,
                "stream" => false,
                "think" => false,
                "keep_alive" => "30m",
                "options" => [
                    "temperature" => 0.2,
                    "num_ctx" => 3072,
                    "num_predict" => 400,
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException("Ollama chat generation failed: " . $response->body());
        }

        $content = (string) $response->json("message.content", "");
        $content = preg_replace("/<think>.*?<\/think>/s", "", $content);

        return trim((string) $content);
    }

    public function getEmbeddingModelName(): string
    {
        return $this->embeddingModel;
    }
}