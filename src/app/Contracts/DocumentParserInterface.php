<?php

namespace App\Contracts;

use App\Models\Document;

interface DocumentParserInterface
{
    /**
     * Extract text segments with optional page numbers from a document.
     *
     * @return array<int, array{page: int|null, text: string}>
     */
    public function extractSegments(Document $document): array;
}
