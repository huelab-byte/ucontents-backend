<?php

declare(strict_types=1);

namespace Modules\BulkPosting\Services;

use Modules\StorageManagement\Models\StorageFile;

class CsvParserService
{
    /**
     * Parse CSV file and return rows as associative arrays
     * Expected columns: caption (or text), media_url (or media_urls), hashtags
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(StorageFile $storageFile): array
    {
        $content = $this->getCsvContent($storageFile);
        if (empty($content)) {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $content);
        if (empty($lines)) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map('trim', $headers);
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $values = str_getcsv($line);
            $row = [];
            foreach ($headers as $i => $header) {
                $key = strtolower(str_replace([' ', '-'], ['_', '_'], $header));
                $row[$key] = $values[$i] ?? '';
                $row[$header] = $values[$i] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
    }

    protected function getCsvContent(StorageFile $storageFile): string
    {
        try {
            $content = $storageFile->getContent();
            return $content ?? '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
