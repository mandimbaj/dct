<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

final class ExcelTemplateBuilder
{
    public static function download(
        string $tempPrefix,
        string $downloadName,
        array $headers,
        array $dropdowns = [],
        int $dataRows = 5000,
    ): BinaryFileResponse {
        $directory = storage_path('app/import-templates');
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.uniqid($tempPrefix, true).'.xlsx';
        self::write($path, $headers, $dropdowns, $dataRows);

        return response()
            ->download($path, $downloadName)
            ->deleteFileAfterSend();
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<string, array<int, string>>  $dropdowns
     */
    private static function write(string $path, array $headers, array $dropdowns, int $dataRows): void
    {
        $dropdowns = self::normalizeDropdowns($dropdowns);
        $zip = new ZipArchive;
        $result = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            throw new \RuntimeException('Unable to create Excel template.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRelationships());
        $zip->addFromString('xl/workbook.xml', self::workbook($dropdowns));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelationships());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::templateSheet($headers, $dropdowns, $dataRows));
        $zip->addFromString('xl/worksheets/sheet2.xml', self::listsSheet($dropdowns));
        $zip->close();
    }

    /**
     * @param  array<string, array<int, string>>  $dropdowns
     * @return array<string, array{name: string, values: array<int, string>, column: string}>
     */
    private static function normalizeDropdowns(array $dropdowns): array
    {
        $normalized = [];
        $index = 1;

        foreach ($dropdowns as $header => $values) {
            $values = collect($values)
                ->map(fn (mixed $value): string => self::clean((string) $value))
                ->filter(fn (string $value): bool => $value !== '')
                ->unique()
                ->sortBy(fn (string $value): string => mb_strtolower($value))
                ->values()
                ->all();

            if ($values === []) {
                continue;
            }

            $normalized[$header] = [
                'name' => '_list_'.preg_replace('/[^A-Za-z0-9_]+/', '_', strtolower($header)).'_'.$index,
                'values' => $values,
                'column' => self::columnLetter($index),
            ];

            $index++;
        }

        return $normalized;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<string, array{name: string, values: array<int, string>, column: string}>  $dropdowns
     */
    private static function templateSheet(array $headers, array $dropdowns, int $dataRows): string
    {
        $cells = collect($headers)
            ->values()
            ->map(fn (string $header, int $index): string => self::inlineStringCell(self::columnLetter($index + 1).'1', $header))
            ->implode('');

        $validations = [];

        foreach ($headers as $index => $header) {
            if (! isset($dropdowns[$header])) {
                continue;
            }

            $column = self::columnLetter($index + 1);
            $name = $dropdowns[$header]['name'];
            $validations[] = '<dataValidation type="list" allowBlank="1" showErrorMessage="1" sqref="'.$column.'2:'.$column.($dataRows + 1).'"><formula1>'.$name.'</formula1></dataValidation>';
        }

        $validationXml = $validations === []
            ? ''
            : '<dataValidations count="'.count($validations).'">'.implode('', $validations).'</dataValidations>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .self::columnsXml(count($headers))
            .'<sheetData><row r="1">'.$cells.'</row></sheetData>'
            .$validationXml
            .'</worksheet>';
    }

    /**
     * @param  array<string, array{name: string, values: array<int, string>, column: string}>  $dropdowns
     */
    private static function listsSheet(array $dropdowns): string
    {
        $maxRows = collect($dropdowns)
            ->map(fn (array $dropdown): int => count($dropdown['values']))
            ->max() ?? 0;

        $rows = [];

        for ($row = 1; $row <= $maxRows; $row++) {
            $cells = [];

            foreach ($dropdowns as $dropdown) {
                $value = $dropdown['values'][$row - 1] ?? null;

                if ($value === null) {
                    continue;
                }

                $cells[] = self::inlineStringCell($dropdown['column'].$row, $value);
            }

            if ($cells !== []) {
                $rows[] = '<row r="'.$row.'">'.implode('', $cells).'</row>';
            }
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'</worksheet>';
    }

    /**
     * @param  array<string, array{name: string, values: array<int, string>, column: string}>  $dropdowns
     */
    private static function workbook(array $dropdowns): string
    {
        $definedNames = [];

        foreach ($dropdowns as $dropdown) {
            $lastRow = count($dropdown['values']);
            $column = $dropdown['column'];
            $definedNames[] = '<definedName name="'.$dropdown['name'].'">\'Lists\'!$'.$column.'$1:$'.$column.'$'.$lastRow.'</definedName>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Template" sheetId="1" r:id="rId1"/>'
            .'<sheet name="Lists" sheetId="2" state="hidden" r:id="rId2"/>'
            .'</sheets>'
            .($definedNames === [] ? '' : '<definedNames>'.implode('', $definedNames).'</definedNames>')
            .'</workbook>';
    }

    private static function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'</Relationships>';
    }

    private static function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private static function columnsXml(int $count): string
    {
        $columns = [];

        for ($index = 1; $index <= $count; $index++) {
            $columns[] = '<col min="'.$index.'" max="'.$index.'" width="24" customWidth="1"/>';
        }

        return '<cols>'.implode('', $columns).'</cols>';
    }

    private static function inlineStringCell(string $reference, string $value): string
    {
        return '<c r="'.$reference.'" t="inlineStr"><is><t>'.self::escape($value).'</t></is></c>';
    }

    private static function columnLetter(int $index): string
    {
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    private static function clean(string $value): string
    {
        return trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? '');
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars(self::clean($value), ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
