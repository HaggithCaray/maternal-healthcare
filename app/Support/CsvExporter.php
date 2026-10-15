<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams CSV straight to the browser so large exports never load fully into memory.
 *
 * Two safeguards for files opened in Excel:
 *  - a UTF-8 BOM, so accented and non-Latin characters (e.g. Ñ, umlauts) render correctly;
 *  - formula-injection escaping, so a cell such as "=cmd|'/C calc'!A1" (or one beginning
 *    with =, +, - or @) is neutralised with a leading apostrophe instead of being executed.
 */
class CsvExporter
{
    /**
     * @param  array<int, array{0: string, 1: string}>  $columns  [row key, header label] pairs
     * @param  iterable<array<string, mixed>>  $rows  each row keyed by the column keys
     */
    public function download(string $filename, array $columns, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel detects the encoding.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_column($columns, 1));

            $keys = array_column($columns, 0);
            foreach ($rows as $row) {
                fputcsv($out, array_map(
                    fn (string $key) => $this->escape($row[$key] ?? ''),
                    $keys
                ));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Neutralise a cell that a spreadsheet app might treat as a formula.
     */
    public function escape(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d');
        }

        if ($value === null) {
            return '';
        }

        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
