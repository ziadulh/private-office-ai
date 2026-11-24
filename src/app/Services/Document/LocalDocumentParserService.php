<?php

namespace App\Services\Document;

use App\Contracts\DocumentParserInterface;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;

class LocalDocumentParserService implements DocumentParserInterface
{
    public function extractSegments(Document $document): array
    {
        if (!Storage::disk('local')->exists($document->storage_path)) {
            throw new RuntimeException('Document file not found in private storage.');
        }

        $fullPath = Storage::disk('local')->path($document->storage_path);
        $ext = strtolower(pathinfo($document->original_filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => $this->parsePdf($fullPath),
            'docx' => $this->parseDocx($fullPath),
            'txt', 'csv' => $this->parsePlainText($fullPath),
            'xlsx' => $this->parseXlsx($fullPath),
            default => throw new RuntimeException("Unsupported file extension: .{$ext}"),
        };
    }

    private function parsePdf(string $path): array
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        $segments = [];

        foreach ($pdf->getPages() as $index => $page) {
            $text = trim($page->getText());
            if ($text !== '') {
                $segments[] = ['page' => $index + 1, 'text' => $this->cleanText($text)];
            }
        }

        return $segments;
    }

    private function parseDocx(string $path): array
    {
        $phpWord = WordIOFactory::load($path);
        $buffer = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $buffer[] = $element->getText();
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $buffer[] = $child->getText();
                        }
                    }
                }
            }
        }

        $text = $this->cleanText(implode("\n", $buffer));
        return $text !== '' ? [['page' => 1, 'text' => $text]] : [];
    }

    private function parsePlainText(string $path): array
    {
        $content = file_get_contents($path);
        $text = $this->cleanText((string) $content);
        return $text !== '' ? [['page' => 1, 'text' => $text]] : [];
    }

    private function parseXlsx(string $path): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($path);
        $segments = [];

        foreach ($spreadsheet->getAllSheets() as $sheetIndex => $sheet) {
            $rows = [];
            foreach ($sheet->toArray(null, true, true, false) as $row) {
                $filtered = array_filter($row, fn ($cell) => $cell !== null && trim((string) $cell) !== '');
                if (!empty($filtered)) {
                    $rows[] = implode(' | ', $filtered);
                }
            }
            $text = $this->cleanText(implode("\n", $rows));
            if ($text !== '') {
                $segments[] = ['page' => $sheetIndex + 1, 'text' => "Sheet: {$sheet->getTitle()}\n{$text}"];
            }
        }

        return $segments;
    }

    private function cleanText(string $text): string
    {
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", (string) $text);
        return trim((string) $text);
    }

    /**
     * Split extracted segments into overlapping UTF-8 safe chunks (~800 chars with 150 char overlap).
     */
    public function buildChunks(array $segments, int $chunkSize = 800, int $overlap = 150): array
    {
        $chunks = [];
        $chunkIndex = 0;

        foreach ($segments as $segment) {
            $text = $segment['text'];
            $page = $segment['page'];
            $length = mb_strlen($text, 'UTF-8');
            $start = 0;

            while ($start < $length) {
                $slice = mb_substr($text, $start, $chunkSize, 'UTF-8');
                $slice = trim($slice);

                if ($slice !== '') {
                    $chunks[] = [
                        'chunk_index' => $chunkIndex++,
                        'page_number' => $page,
                        'content' => $slice,
                        'token_estimate' => (int) ceil(mb_strlen($slice, 'UTF-8') / 4),
                    ];
                }

                if ($start + $chunkSize >= $length) {
                    break;
                }

                $start += ($chunkSize - $overlap);
            }
        }

        return $chunks;
    }
}
