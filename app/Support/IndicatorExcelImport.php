<?php

namespace App\Support;

use App\Models\Country;
use App\Models\DataSource;
use App\Models\HealthIndicatorValue;
use App\Models\HealthIndicatorArchive;
use App\Models\ImportRecord;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use App\Models\MeasureMethod;
use App\Services\DataQuality\DataQualityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class IndicatorExcelImport
{
    public const HEADERS = [
        'Location',
        'Indicator Name',
        'Category Option',
        'Measure Type',
        'Data Source',
        'Start Period',
        'End Period',
        'Value',
    ];

    private const HEADER_KEYS = [
        'Location' => 'location',
        'Indicator Name' => 'indicator',
        'Category Option' => 'category',
        'Measure Type' => 'measuremethod',
        'Data Source' => 'datasource',
        'Start Period' => 'start_period',
        'End Period' => 'end_period',
        'Value' => 'value_received',
    ];

    private const HEADER_ALIASES = [
        'Location' => ['Location Name', 'Country', 'Country Name'],
        'Indicator Name' => ['Indicator'],
        'Category Option' => ['Categorieoption', 'Categorie Option', 'Category option', 'Disaggregation Option'],
        'Measure Type' => ['Measure', 'Measure Method', 'Data Value Type'],
        'Data Source' => ['Source'],
        'Start Period' => ['Start'],
        'End Period' => ['Ending Period', 'Ending Periode', 'End'],
        'Value' => ['Received Value'],
    ];

    private const SPREADSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const RELATIONSHIPS_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_RELATIONSHIPS_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    public static function hasFile(mixed $file): bool
    {
        while (is_array($file)) {
            $file = collect($file)->first();
        }

        return $file instanceof TemporaryUploadedFile || (is_string($file) && filled($file));
    }

    /**
     * @return array<string, mixed>
     */
    public static function preview(mixed $file): array
    {
        $parsed = self::parse($file);
        $rows = $parsed['rows'];

        return [
            'detected_rows' => count($rows),
            'format_status' => __('aho.indicator_import.format_ok', ['rows' => count($rows)]),
            'indicator_mappings' => self::mappingRows($rows, 'indicator', 'indicator'),
            'location_mappings' => self::mappingRows($rows, 'location', 'location'),
            'category_mappings' => self::mappingRows($rows, 'category', 'category'),
            'datasource_mappings' => self::mappingRows($rows, 'datasource', 'datasource'),
            'measuremethod_mappings' => self::mappingRows($rows, 'measuremethod', 'measuremethod'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function optionsFor(string $type, ?string $search = null, int $limit = 50): array
    {
        $search = self::cell($search);
        $needle = self::normalize($search);

        return self::queryRecords($type, $search, $limit)
            ->map(fn (Model $record): array => [
                'id' => (int) $record->getKey(),
                'label' => self::labelFor($type, $record),
                'score' => self::scoreRecord($record, $type, $needle),
            ])
            ->filter(fn (array $item): bool => blank($needle) || $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->pluck('label', 'id')
            ->all();
    }

    public static function labelForId(string $type, mixed $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        $query = self::baseQuery($type);

        if (! $query) {
            return null;
        }

        $record = $query
            ->whereKey($id)
            ->first();

        return $record ? self::labelFor($type, $record) : null;
    }

    public static function downloadTemplate(): BinaryFileResponse
    {
        $directory = storage_path('app/import-templates');
        File::ensureDirectoryExists($directory);

        $path = tempnam($directory, 'indicator-import-template-');

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::HEADERS));
        $writer->close();

        return response()
            ->download($path, 'indicator-data-import-template.xlsx')
            ->deleteFileAfterSend();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{created: int}
     */
    public static function import(mixed $file, array $data): array
    {
        $parsed = self::parse($file);
        $rows = $parsed['rows'];

        if ($rows === []) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.no_rows'),
            ]);
        }

        $maps = [
            'indicator' => self::selectedMap($data['indicator_mappings'] ?? [], 'indicator'),
            'location' => self::selectedMap($data['location_mappings'] ?? [], 'location'),
            'category' => self::selectedMap($data['category_mappings'] ?? [], 'category'),
            'datasource' => self::selectedMap($data['datasource_mappings'] ?? [], 'datasource'),
            'measuremethod' => self::selectedMap($data['measuremethod_mappings'] ?? [], 'measuremethod'),
        ];

        $errors = [];
        $payloads = [];
        $seenSignatures = [];

        foreach ($rows as $row) {
            $rowNumber = $row['_row'] ?? '?';
            $indicatorId = self::mappedId($maps['indicator'], $row['indicator'], 'Indicator Name', $rowNumber, $errors);
            $locationId = self::mappedId($maps['location'], $row['location'], 'Location', $rowNumber, $errors);
            $categoryId = self::mappedId($maps['category'], $row['category'], 'Category Option', $rowNumber, $errors);
            $datasourceId = self::mappedId($maps['datasource'], $row['datasource'], 'Data Source', $rowNumber, $errors);
            $measuremethodId = self::mappedId($maps['measuremethod'], $row['measuremethod'], 'Measure Type', $rowNumber, $errors);

            if ($locationId && ! UserCountryAccess::allowsLocationId($locationId)) {
                $errors[] = __('aho.indicator_import.errors.location_forbidden', ['row' => $rowNumber]);
            }

            $startPeriod = self::integerValue($row['start_period']);
            $endPeriod = self::integerValue($row['end_period']);
            $receivedValueProvided = filled(self::cell($row['value_received']));
            $receivedValue = self::decimalValue($row['value_received']);

            if ($startPeriod === null) {
                $errors[] = __('aho.indicator_import.errors.invalid_year', ['row' => $rowNumber, 'field' => 'Start Period']);
            }

            if ($endPeriod === null) {
                $errors[] = __('aho.indicator_import.errors.invalid_year', ['row' => $rowNumber, 'field' => 'End Period']);
            }

            if ($receivedValueProvided && $receivedValue === null) {
                $errors[] = __('aho.indicator_import.errors.invalid_number', ['row' => $rowNumber, 'field' => 'Value']);
            }

            if (! $receivedValueProvided) {
                $errors[] = __('aho.indicator_import.errors.value_required', ['row' => $rowNumber]);
            }

            $payloads[] = [
                '_row' => $rowNumber,
                'indicator_id' => $indicatorId,
                'location_id' => $locationId,
                'categoryoption_id' => $categoryId,
                'datasource_id' => $datasourceId,
                'measuremethod_id' => $measuremethodId,
                'start_period' => $startPeriod,
                'end_period' => $endPeriod,
                'period' => ($startPeriod && $endPeriod && $startPeriod !== $endPeriod)
                    ? "{$startPeriod}-{$endPeriod}"
                    : (string) ($startPeriod ?? $endPeriod ?? ''),
                'value_received' => $receivedValue,
                'target_value' => null,
                'string_value' => null,
                'comment' => ApprovalWorkflow::STATUS_PENDING,
                'approval_status' => ApprovalWorkflow::STATUS_PENDING,
                'approved_by' => null,
                'approved_at' => null,
                'user_id' => auth()->id() ?? 1,
            ];
        }

        foreach ($payloads as $payload) {
            if (! self::payloadHasRequiredKeys($payload)) {
                continue;
            }

            $signature = self::payloadSignature($payload);

            if (isset($seenSignatures[$signature])) {
                $errors[] = __('aho.indicator_import.errors.duplicate_in_file', [
                    'row' => $payload['_row'],
                    'first_row' => $seenSignatures[$signature],
                ]);

                continue;
            }

            $seenSignatures[$signature] = $payload['_row'];

            if (self::duplicateExists($payload)) {
                $errors[] = __('aho.indicator_import.errors.duplicate_existing', [
                    'row' => $payload['_row'],
                    'indicator' => self::labelForId('indicator', $payload['indicator_id']) ?? $payload['indicator_id'],
                    'location' => self::labelForId('location', $payload['location_id']) ?? $payload['location_id'],
                    'period' => $payload['period'],
                ]);
            }

            foreach (app(DataQualityService::class)->inspectIndicatorPayload($payload) as $issue) {
                $errors[] = __('aho.indicator_import.errors.quality_issue', [
                    'row' => $payload['_row'],
                    'message' => $issue['message'],
                ]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages([
                'excel_file' => self::limitedErrorMessage($errors),
            ]);
        }

        return DB::connection('warehouse')->transaction(function () use ($payloads, $parsed): array {
            foreach ($payloads as $payload) {
                unset($payload['_row']);

                HealthIndicatorValue::query()->create($payload);
            }

            self::recordImport(count($payloads), $parsed['file_name']);

            return ['created' => count($payloads)];
        });
    }

    /**
     * @return array{file_name: string, rows: array<int, array<string, mixed>>}
     */
    private static function parse(mixed $file): array
    {
        [$path, $fileName] = self::resolveFile($file);

        if (! class_exists(ZipArchive::class) || ! function_exists('simplexml_load_string')) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.reader_missing'),
            ]);
        }

        if (! is_file($path)) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.file_missing'),
            ]);
        }

        if (Str::lower(pathinfo($fileName ?: $path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.xlsx_only'),
            ]);
        }

        $rawRows = self::readRows($path);
        $headerIndex = null;
        $headerRow = [];

        foreach ($rawRows as $index => $rawRow) {
            if (! self::hasContent($rawRow['cells'])) {
                continue;
            }

            $headerIndex = $index;
            $headerRow = $rawRow['cells'];
            break;
        }

        if ($headerIndex === null) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.no_rows'),
            ]);
        }

        $headerPositions = self::headerPositions($headerRow);
        $rows = [];

        foreach (array_slice($rawRows, $headerIndex + 1) as $rawRow) {
            if (! self::hasContent($rawRow['cells'])) {
                continue;
            }

            $row = ['_row' => $rawRow['number']];

            foreach (self::HEADER_KEYS as $header => $key) {
                $row[$key] = self::cell($rawRow['cells'][$headerPositions[$header]] ?? null);
            }

            $rows[] = $row;
        }

        return [
            'file_name' => $fileName,
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array{file_value: string, matched_id: ?int}>
     */
    private static function mappingRows(array $rows, string $key, string $type): array
    {
        return collect($rows)
            ->pluck($key)
            ->map(fn (mixed $value): string => self::cell($value))
            ->filter(fn (string $value): bool => filled($value))
            ->unique(fn (string $value): string => self::normalize($value))
            ->values()
            ->map(fn (string $value): array => [
                'file_value' => $value,
                'matched_id' => self::exactMatchId($type, $value),
            ])
            ->all();
    }

    /**
     * @param  array<int, mixed>  $cells
     * @return array<string, int>
     */
    private static function headerPositions(array $cells): array
    {
        $found = [];

        foreach ($cells as $index => $cell) {
            $found[self::normalizeHeader((string) $cell)] = (int) $index;
        }

        $positions = [];
        $missing = [];

        foreach (self::HEADER_KEYS as $header => $key) {
            $normalizedHeaders = collect([$header, ...(self::HEADER_ALIASES[$header] ?? [])])
                ->map(fn (string $header): string => self::normalizeHeader($header))
                ->all();

            $matchedHeader = collect($normalizedHeaders)
                ->first(fn (string $normalizedHeader): bool => array_key_exists($normalizedHeader, $found));

            if (! $matchedHeader) {
                $missing[] = $header;

                continue;
            }

            $positions[$header] = $found[$matchedHeader];
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.bad_headers', [
                    'headers' => implode(', ', $missing),
                ]),
            ]);
        }

        return $positions;
    }

    /**
     * @return array{string, string}
     */
    private static function resolveFile(mixed $file): array
    {
        while (is_array($file)) {
            $file = collect($file)->first();
        }

        if ($file instanceof TemporaryUploadedFile) {
            return [$file->getRealPath(), $file->getClientOriginalName()];
        }

        if (is_string($file) && filled($file)) {
            return [$file, basename($file)];
        }

        throw ValidationException::withMessages([
            'excel_file' => __('aho.indicator_import.errors.file_required'),
        ]);
    }

    /**
     * @return array<int, array{number: int, cells: array<int, string>}>
     */
    private static function readRows(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.unreadable'),
            ]);
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetPath = self::firstSheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);

        if ($sheetXml === false) {
            $zip->close();

            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.unreadable'),
            ]);
        }

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            $zip->close();

            throw ValidationException::withMessages([
                'excel_file' => __('aho.indicator_import.errors.unreadable'),
            ]);
        }

        $sheet->registerXPathNamespace('m', self::SPREADSHEET_NAMESPACE);
        $rows = [];

        foreach ($sheet->xpath('//m:sheetData/m:row') ?: [] as $rowElement) {
            $rowElement->registerXPathNamespace('m', self::SPREADSHEET_NAMESPACE);
            $cells = [];

            foreach ($rowElement->xpath('m:c') ?: [] as $cell) {
                $reference = (string) $cell['r'];
                $cells[self::columnIndex($reference)] = self::cellValue($cell, $sharedStrings);
            }

            $rows[] = [
                'number' => (int) ((string) $rowElement['r'] ?: count($rows) + 1),
                'cells' => $cells,
            ];
        }

        $zip->close();

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);

        if ($shared === false) {
            return [];
        }

        $shared->registerXPathNamespace('m', self::SPREADSHEET_NAMESPACE);
        $strings = [];

        foreach ($shared->xpath('//m:si') ?: [] as $item) {
            $item->registerXPathNamespace('m', self::SPREADSHEET_NAMESPACE);
            $strings[] = collect($item->xpath('.//m:t') ?: [])
                ->map(fn ($text): string => (string) $text)
                ->implode('');
        }

        return $strings;
    }

    private static function firstSheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationshipsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationshipsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $relationships = simplexml_load_string($relationshipsXml);

        if ($workbook === false || $relationships === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook->registerXPathNamespace('m', self::SPREADSHEET_NAMESPACE);
        $workbook->registerXPathNamespace('r', self::RELATIONSHIPS_NAMESPACE);

        $firstSheet = ($workbook->xpath('//m:sheets/m:sheet') ?: [])[0] ?? null;
        $relationshipId = $firstSheet ? (string) $firstSheet->attributes(self::RELATIONSHIPS_NAMESPACE)['id'] : null;

        if (blank($relationshipId)) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationships->registerXPathNamespace('rel', self::PACKAGE_RELATIONSHIPS_NAMESPACE);

        foreach ($relationships->xpath('//rel:Relationship') ?: [] as $relationship) {
            if ((string) $relationship['Id'] !== $relationshipId) {
                continue;
            }

            $target = (string) $relationship['Target'];

            if (str_starts_with($target, '/')) {
                return ltrim($target, '/');
            }

            return 'xl/'.ltrim($target, '/');
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private static function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cell->registerXPathNamespace('m', self::SPREADSHEET_NAMESPACE);
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return collect($cell->xpath('.//m:t') ?: [])
                ->map(fn ($text): string => (string) $text)
                ->implode('');
        }

        $value = (string) (($cell->xpath('m:v') ?: [])[0] ?? '');

        return $type === 's' ? ($sharedStrings[(int) $value] ?? '') : $value;
    }

    private static function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private static function hasContent(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (filled(self::cell($cell))) {
                return true;
            }
        }

        return false;
    }

    private static function cell(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');
    }

    private static function normalizeHeader(string $value): string
    {
        return Str::lower(preg_replace('/\s+/u', ' ', trim($value)) ?? '');
    }

    private static function normalize(mixed $value): string
    {
        $value = Str::ascii(self::cell($value));
        $value = Str::lower($value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private static function integerValue(mixed $value): ?int
    {
        $value = self::cell($value);

        if ($value === '') {
            return null;
        }

        return preg_match('/^\d{4}(?:\.0+)?$/', $value) === 1 ? (int) $value : null;
    }

    private static function decimalValue(mixed $value): ?string
    {
        $value = self::cell($value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(' ', '', $value);

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (string) $value : null;
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<string, int>
     */
    private static function selectedMap(array $items, string $type): array
    {
        $map = [];

        foreach ($items as $item) {
            $fileValue = self::cell($item['file_value'] ?? null);
            $matchedId = $item['matched_id'] ?? null;

            if (blank($fileValue) || blank($matchedId)) {
                throw ValidationException::withMessages([
                    'excel_file' => __('aho.indicator_import.errors.mapping_required', [
                        'type' => __('aho.indicator_import.types.'.$type),
                    ]),
                ]);
            }

            $map[self::normalize($fileValue)] = (int) $matchedId;
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $map
     * @param  array<int, string>  $errors
     */
    private static function mappedId(array $map, mixed $value, string $header, int | string $row, array &$errors): ?int
    {
        $value = self::cell($value);

        if (blank($value)) {
            $errors[] = __('aho.indicator_import.errors.required_cell', ['row' => $row, 'field' => $header]);

            return null;
        }

        $id = $map[self::normalize($value)] ?? null;

        if (! $id) {
            $errors[] = __('aho.indicator_import.errors.unmapped_cell', [
                'row' => $row,
                'field' => $header,
                'value' => $value,
            ]);
        }

        return $id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function payloadHasRequiredKeys(array $payload): bool
    {
        foreach ([
            'indicator_id',
            'location_id',
            'categoryoption_id',
            'datasource_id',
            'measuremethod_id',
            'start_period',
            'end_period',
        ] as $key) {
            if (blank($payload[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function payloadSignature(array $payload): string
    {
        return implode('|', [
            $payload['indicator_id'],
            $payload['location_id'],
            $payload['categoryoption_id'],
            $payload['datasource_id'],
            $payload['measuremethod_id'],
            $payload['start_period'],
            $payload['end_period'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function duplicateExists(array $payload): bool
    {
        return self::duplicateQuery(HealthIndicatorValue::query(), $payload)->exists()
            || self::duplicateQuery(HealthIndicatorArchive::query(), $payload)->exists();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function duplicateQuery(Builder $query, array $payload): Builder
    {
        return $query
            ->where('indicator_id', $payload['indicator_id'])
            ->where('location_id', $payload['location_id'])
            ->where('categoryoption_id', $payload['categoryoption_id'])
            ->where('datasource_id', $payload['datasource_id'])
            ->where('measuremethod_id', $payload['measuremethod_id'])
            ->where('start_period', $payload['start_period'])
            ->where('end_period', $payload['end_period']);
    }

    /**
     * @param  array<int, string>  $errors
     */
    private static function limitedErrorMessage(array $errors): string
    {
        $uniqueErrors = array_values(array_unique($errors));
        $visibleErrors = array_slice($uniqueErrors, 0, 12);
        $remaining = count($uniqueErrors) - count($visibleErrors);

        if ($remaining > 0) {
            $visibleErrors[] = __('aho.indicator_import.errors.more_errors', ['count' => $remaining]);
        }

        return implode("\n", $visibleErrors);
    }

    private static function exactMatchId(string $type, string $value): ?int
    {
        $needle = self::normalize($value);

        if (blank($needle)) {
            return null;
        }

        return self::queryRecords($type, $value, 20, exact: true)
            ->map(fn (Model $record): array => [
                'id' => (int) $record->getKey(),
                'score' => self::scoreRecord($record, $type, $needle, true),
            ])
            ->firstWhere('score', 100)['id'] ?? null;
    }

    private static function scoreRecord(Model $record, string $type, string $needle, bool $exactOnly = false): int
    {
        if (blank($needle)) {
            return 1;
        }

        $score = 0;

        foreach (self::searchValues($record, $type) as $candidate) {
            $candidate = self::normalize($candidate);

            if (blank($candidate)) {
                continue;
            }

            if ($candidate === $needle) {
                return 100;
            }

            if ($exactOnly) {
                continue;
            }

            if (str_starts_with($candidate, $needle)) {
                $score = max($score, 85);
            } elseif (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
                $score = max($score, 70);
            } else {
                similar_text($needle, $candidate, $percent);

                if ($percent >= 55) {
                    $score = max($score, (int) round($percent / 2));
                }
            }
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private static function searchValues(Model $record, string $type): array
    {
        $values = [
            self::labelCode($type, $record),
            $record->display_name ?? null,
        ];

        if ($type === 'location') {
            $values[] = $record->iso_alpha ?? null;
        }

        if ($type === 'category') {
            $values[] = $record->parentCategory?->display_name;
        }

        foreach ($record->translations ?? [] as $translation) {
            $values[] = $translation->name ?? null;
        }

        return array_values(array_filter($values, fn (mixed $value): bool => filled($value)));
    }

    private static function labelFor(string $type, Model $record): string
    {
        $code = self::labelCode($type, $record);
        $name = $record->display_name ?? $code ?? (string) $record->getKey();

        if ($type === 'category') {
            $group = $record->parentCategory?->display_name;
            $name = filled($group) ? "{$group} / {$name}" : $name;
        }

        return filled($code) ? "{$name} ({$code})" : $name;
    }

    private static function labelCode(string $type, Model $record): ?string
    {
        return match ($type) {
            'indicator' => $record->afrocode,
            'location' => $record->iso_alpha ?: $record->code,
            default => $record->code,
        };
    }

    private static function queryRecords(string $type, ?string $search = null, int $limit = 50, bool $exact = false): Collection
    {
        $query = self::baseQuery($type);

        if (! $query) {
            return collect();
        }

        $search = self::cell($search);

        if (filled($search)) {
            self::applySearch($query, $type, $search, $exact);
        }

        return $query->limit($limit)->get();
    }

    private static function baseQuery(string $type): ?Builder
    {
        return match ($type) {
            'indicator' => Indicator::query()->with('translations')->orderBy('afrocode'),
            'location' => UserCountryAccess::scope(Country::query()->with('translations')->orderBy('code')),
            'category' => IndicatorCategory::query()->with(['translations', 'parentCategory.translations'])->orderBy('code'),
            'datasource' => DataSource::query()->with('translations')->orderBy('code'),
            'measuremethod' => MeasureMethod::query()->with('translations')->orderBy('code'),
            default => null,
        };
    }

    private static function applySearch(Builder $query, string $type, string $search, bool $exact): void
    {
        $terms = collect(preg_split('/\s+/', $search) ?: [])
            ->map(fn (string $term): string => trim($term))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 3)
            ->values()
            ->all();

        if ($terms === []) {
            $terms = [$search];
        }

        $codeColumn = $type === 'indicator' ? 'afrocode' : 'code';

        $query->where(function (Builder $query) use ($codeColumn, $exact, $search, $terms, $type): void {
            foreach ($terms as $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

                if ($exact) {
                    $query->orWhere($codeColumn, $search);
                } else {
                    $query->orWhere($codeColumn, 'like', $like);
                }

                if ($type === 'location') {
                    $exact
                        ? $query->orWhere('iso_alpha', $search)
                        : $query->orWhere('iso_alpha', 'like', $like);
                }

                $query->orWhereHas('translations', function (Builder $query) use ($exact, $like, $search): void {
                    $exact
                        ? $query->where('name', $search)
                        : $query->where('name', 'like', $like);
                });

                if ($type === 'category') {
                    $query->orWhereHas('parentCategory.translations', function (Builder $query) use ($exact, $like, $search): void {
                        $exact
                            ? $query->where('name', $search)
                            : $query->where('name', 'like', $like);
                    });
                }
            }
        });
    }

    private static function recordImport(int $count, string $fileName): void
    {
        try {
            ImportRecord::query()->create([
                'record_count' => $count,
                'loader' => $fileName,
                'serializer' => 'Laravel indicator Excel import',
                'object_id' => null,
                'content_type_id' => null,
                'user_id' => auth()->id() ?? 1,
            ]);
        } catch (Throwable) {
            // The import result itself matters more than optional history metadata.
        }
    }
}
