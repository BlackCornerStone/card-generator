<?php

namespace CardGenerator\Utils;

/**
 * Common helpers for repositories: CSV loading, row normalization, link detection, and utilities.
 */
class RepositoryUtils
{
    /**
     * Read a semicolon-delimited CSV file into an array of associative rows using the header.
     * Skips malformed/empty lines.
     *
     * @param string $filename
     * @return array<int, array<string, string|null>>
     */
    public static function readCsv(string $filename): array
    {
        $rows = [];
        if (!is_file($filename)) {
            return $rows;
        }

        if (($handle = fopen($filename, 'r')) === false) {
            return $rows;
        }

        $header = fgetcsv($handle, 2000, ';');
        if ($header === false) {
            fclose($handle);
            return $rows;
        }

        while (($data = fgetcsv($handle, 2000, ';')) !== false) {
            if ($data === null || $data === [null] || count($data) === 0) {
                continue;
            }
            if (count($data) !== count($header)) {
                // skip malformed row
                continue;
            }
            /** @var array<string, string|null> $row */
            $row = array_combine($header, $data);
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Normalize a raw associative row by grouping bracketed fields and expanding star-groups.
     * Also extracts link references in the form of {dataset} Field => value.
     *
     * Returns [data, links] where links is an associative map fieldName => ['dataset' => string, 'key' => string].
     *
     * @param array<string, mixed> $row
     * @return array{0: array<string, mixed>, 1: array<string, array{dataset:string, key:string}>}
     */
    public static function normalizeRow(array $row): array
    {
        $processed = [];
        $links = [];

        foreach ($row as $key => $value) {
            if (preg_match('/^\[(.+?)\]\s*(.+)$/', (string)$key, $m)) {
                $group = $m[1];
                $sub = $m[2];
                $processed[$group][$sub] = $value;
            } elseif (preg_match('/^\{(.+?)\}\s*(.+)$/', (string)$key, $m)) {
                $dataset = $m[1];
                $sub = $m[2];
                $links[$sub] = ['dataset' => $dataset, 'key' => (string)$value];
                // Keep the original raw key value around at top-level for reference too
                $processed[$sub] = $value;
            } else {
                $processed[$key] = $value;
            }
        }

        // Expand groups ending with asterisk into arrays of objects by splitting values by '|'
        foreach (array_keys($processed) as $groupName) {
            $groupData = $processed[$groupName] ?? null;
            if (is_array($groupData) && substr($groupName, -1) === '*') {
                $clean = substr($groupName, 0, -1);
                $bucket = [];
                foreach ($groupData as $subKey => $subVal) {
                    $strVal = is_string($subVal) ? $subVal : (string)$subVal;
                    if ($strVal !== '' && trim($strVal) !== '') {
                        $parts = explode('|', $strVal);
                        foreach ($parts as $idx => $part) {
                            $bucket[$idx][$subKey] = trim($part);
                        }
                    }
                }
                // Replace with expanded group
                $processed[$clean] = array_values($bucket);
                unset($processed[$groupName]);
            }
        }

        return [$processed, $links];
    }

    /**
     * Convert a human-readable text to a normalized image file path (png) under images/ directory.
     * Returns [dataUri, absolutePath] when file exists; otherwise [null, null].
     *
     * @param string $text
     * @return array{0:?string,1:?string}
     */
    public static function imageFromText(string $text): array
    {
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($text));
        $filename = preg_replace('/_+/', '_', (string)$filename);
        $filename = trim((string)$filename, '_');
        $imagePath = __DIR__ . '/../../images/' . $filename . '.png';
        if (is_file($imagePath)) {
            $imageData = base64_encode((string)file_get_contents($imagePath));
            return ['data:image/png;base64,' . $imageData, $imagePath];
        }
        return [null, null];
    }
}
