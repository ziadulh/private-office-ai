<?php

namespace App\Jobs;

use App\Contracts\DocumentParserInterface;
use App\Models\Document;
use App\Services\AI\OllamaClientService;
use App\Services\Audit\AuditService;
use App\Services\Document\LocalDocumentParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $documentId) {}

    public function handle(
        DocumentParserInterface $parser,
        OllamaClientService $ollama,
        AuditService $auditService
    ): void {
        $document = Document::find($this->documentId);
        if (!$document) {
            return;
        }

        $document->update([
            "status" => "processing",
            "processing_error" => null,
        ]);

        try {
            $segments = $parser->extractSegments($document);

            if (empty($segments)) {
                throw new \RuntimeException("No extractable text found (file may be empty or a scanned image PDF).");
            }

            /** @var LocalDocumentParserService $parser */
            $chunks = $parser->buildChunks($segments);
            $ollamaAvailable = $ollama->isHealthy();
            $embeddingModel = $ollama->getEmbeddingModelName();

            foreach ($chunks as &$chunk) {
                if ($ollamaAvailable) {
                    try {
                        $chunk["embedding"] = $ollama->embed($chunk["content"]);
                        $chunk["embedding_model"] = $embeddingModel;
                    } catch (Throwable) {
                        $chunk["embedding"] = null;
                    }
                }
            }
            unset($chunk);

            DB::transaction(function () use ($document, $chunks) {
                $document->chunks()->delete();

                foreach ($chunks as $chunk) {
                    $document->chunks()->create($chunk);
                }

                $document->update([
                    "status" => "completed",
                    "processing_error" => null,
                    "processed_at" => now(),
                ]);
            });

            $auditService->log("document.processed", $document, [
                "chunks_count" => count($chunks),
                "embedded" => $ollamaAvailable,
            ], $document->uploaded_by);
        } catch (Throwable $e) {
            $document->update([
                "status" => "failed",
                "processing_error" => mb_substr($e->getMessage(), 0, 1000),
            ]);

            $auditService->log("document.processing_failed", $document, [
                "error" => $e->getMessage(),
            ], $document->uploaded_by);
        }
    }
}