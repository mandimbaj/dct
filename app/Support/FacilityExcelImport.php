<?php

namespace App\Support;

use App\Models\Country;
use App\Models\FacilityOwner;
use App\Models\FacilityType;
use App\Models\HealthFacility;
use App\Models\ImportRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class FacilityExcelImport
{
    public const HEADERS = [
        'Facility Name',
        'Short Name',
        'Type',
        'Owner',
        'Location',
        'Admin Location',
        'Status',
        'Latitude',
        'Longitude',
        'Altitude',
        'Geosource',
        'Address',
        'Email',
        'Phone Code',
        'Phone Number',
        'URL',
        'Description',
    ];

    private const REQUIRED_HEADERS = [
        'Facility Name',
        'Type',
        'Owner',
        'Location',
    ];

    private const HEADER_KEYS = [
        'Facility Name' => 'name',
        'Short Name' => 'shortname',
        'Type' => 'type',
        'Owner' => 'owner',
        'Location' => 'location',
        'Admin Location' => 'admin_location',
        'Status' => 'status',
        'Latitude' => 'latitude',
        'Longitude' => 'longitude',
        'Altitude' => 'altitude',
        'Geosource' => 'geosource',
        'Address' => 'address',
        'Email' => 'email',
        'Phone Code' => 'phone_code',
        'Phone Number' => 'phone_part',
        'URL' => 'url',
        'Description' => 'description',
    ];

    private const HEADER_ALIASES = [
        'Facility Name' => ['Facility', 'Health Facility', 'Health Facility Name', 'Name'],
        'Short Name' => ['Shortname', 'Facility Short Name'],
        'Type' => ['Facility Type'],
        'Owner' => ['Facility Owner', 'Ownership'],
        'Location' => ['Country', 'Country Name', 'Location Name'],
        'Admin Location' => ['Administrative Location', 'District', 'Province', 'Region'],
        'Phone Number' => ['Phone', 'Telephone'],
        'URL' => ['Website', 'Web Site'],
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
            'format_status' => __('aho.facility_import.format_ok', ['rows' => count($rows)]),
            'type_mappings' => self::mappingRows($rows, 'type', 'type'),
            'owner_mappings' => self::mappingRows($rows, 'owner', 'owner'),
            'location_mappings' => self::mappingRows($rows, 'location', 'location'),
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
                'label' => self::labelFor($record),
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

        $record = $query->whereKey($id)->first();

        return $record ? self::labelFor($record) : null;
    }

    public static function downloadTemplate(): BinaryFileResponse
    {
        return ExcelTemplateBuilder::download(
            'facility-import-template-',
            'facility-import-template.xlsx',
            self::HEADERS,
            [
                'Type' => self::templateOptions('type'),
                'Owner' => self::templateOptions('owner'),
                'Location' => self::templateOptions('location'),
                'Status' => ['active', 'closed'],
            ],
        );
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
                'excel_file' => __('aho.facility_import.errors.no_rows'),
            ]);
        }

        $maps = [
            'type' => self::selectedMap($data['type_mappings'] ?? [], 'type'),
            'owner' => self::selectedMap($data['owner_mappings'] ?? [], 'owner'),
            'location' => self::selectedMap($data['location_mappings'] ?? [], 'location'),
        ];

        $errors = [];
        $payloads = [];
        $seenSignatures = [];

        foreach ($rows as $row) {
            $rowNumber = $row['_row'] ?? '?';
            $name = self::cell($row['name'] ?? null);
            $typeId = self::mappedId($maps['type'], $row['type'] ?? null, 'Type', $rowNumber, $errors);
            $ownerId = self::mappedId($maps['owner'], $row['owner'] ?? null, 'Owner', $rowNumber, $errors);
            $locationId = self::mappedId($maps['location'], $row['location'] ?? null, 'Location', $rowNumber, $errors);

            if (blank($name)) {
                $errors[] = __('aho.facility_import.errors.required_cell', ['row' => $rowNumber, 'field' => 'Facility Name']);
            }

            if ($locationId && ! UserCountryAccess::allowsLocationId($locationId)) {
                $errors[] = __('aho.facility_import.errors.location_forbidden', ['row' => $rowNumber]);
            }

            $latitude = self::optionalDecimal($row['latitude'] ?? null, 'Latitude', $rowNumber, $errors);
            $longitude = self::optionalDecimal($row['longitude'] ?? null, 'Longitude', $rowNumber, $errors);
            $altitude = self::optionalDecimal($row['altitude'] ?? null, 'Altitude', $rowNumber, $errors);
            $email = self::nullableCell($row['email'] ?? null);
            $url = self::nullableCell($row['url'] ?? null);

            if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = __('aho.facility_import.errors.invalid_email', ['row' => $rowNumber]);
            }

            if ($url !== null && filter_var($url, FILTER_VALIDATE_URL) === false) {
                $errors[] = __('aho.facility_import.errors.invalid_url', ['row' => $rowNumber]);
            }

            $payloads[] = [
                '_row' => $rowNumber,
                'name' => $name,
                'shortname' => self::nullableCell($row['shortname'] ?? null),
                'type_id' => $typeId,
                'owner_id' => $ownerId,
                'location_id' => $locationId,
                'admin_location' => self::nullableCell($row['admin_location'] ?? null),
                'status' => self::statusValue($row['status'] ?? null),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'altitude' => $altitude,
                'geosource' => self::nullableCell($row['geosource'] ?? null),
                'address' => self::nullableCell($row['address'] ?? null),
                'email' => $email,
                'phone_code' => self::nullableCell($row['phone_code'] ?? null) ?? '',
                'phone_part' => self::nullableCell($row['phone_part'] ?? null) ?? '',
                'url' => $url,
                'description' => self::nullableCell($row['description'] ?? null),
                'user_id' => auth()->id() ?? 1,
            ];
        }

        foreach ($payloads as $payload) {
            if (! self::payloadHasRequiredKeys($payload)) {
                continue;
            }

            $signature = self::payloadSignature($payload);

            if (isset($seenSignatures[$signature])) {
                $errors[] = __('aho.facility_import.errors.duplicate_in_file', [
                    'row' => $payload['_row'],
                    'first_row' => $seenSignatures[$signature],
                ]);

                continue;
            }

            $seenSignatures[$signature] = $payload['_row'];

            if (self::duplicateExists($payload)) {
                $errors[] = __('aho.facility_import.errors.duplicate_existing', [
                    'row' => $payload['_row'],
                    'facility' => $payload['name'],
                    'location' => self::labelForId('location', $payload['location_id']) ?? $payload['location_id'],
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

                HealthFacility::query()->create($payload);
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
                'excel_file' => __('aho.facility_import.errors.reader_missing'),
            ]);
        }

        if (! is_file($path)) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.facility_import.errors.file_missing'),
            ]);
        }

        if (Str::lower(pathinfo($fileName ?: $path, PATHINFO_EXTENSION)) !== 'xlsx') {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.facility_import.errors.xlsx_only'),
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
                'excel_file' => __('aho.facility_import.errors.no_rows'),
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
                $position = $headerPositions[$header] ?? null;
                $row[$key] = $position === null ? '' : self::cell($rawRow['cells'][$position] ?? null);
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
                if (in_array($header, self::REQUIRED_HEADERS, true)) {
                    $missing[] = $header;
                }

                continue;
            }

            $positions[$header] = $found[$matchedHeader];
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'excel_file' => __('aho.facility_import.errors.bad_headers', [
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
            'excel_file' => __('aho.facility_import.errors.file_required'),
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
                'excel_file' => __('aho.facility_import.errors.unreadable'),
            ]);
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetPath = self::firstSheetPath($zip);
        $sheetXml = $zip->getFromName($sheetPath);

        if ($sheetXml === false) {
            $zip->close();

            throw ValidationException::withMessages([
                'excel_file' => __('aho.facility_import.errors.unreadable'),
            ]);
        }

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false) {
            $zip->close();

            throw ValidationException::withMessages([
                'excel_file' => __('aho.facility_import.errors.unreadable'),
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

    private static function nullableCell(mixed $value): ?string
    {
        $value = self::cell($value);

        return $value === '' ? null : $value;
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

    private static function optionalDecimal(mixed $value, string $field, int|string $row, array &$errors): ?string
    {
        $value = self::cell($value);

        if ($value === '') {
            return null;
        }

        $value = str_replace(' ', '', $value);

        if (str_contains($value, ',') && ! str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            $errors[] = __('aho.facility_import.errors.invalid_number', ['row' => $row, 'field' => $field]);

            return null;
        }

        return (string) $value;
    }

    private static function statusValue(mixed $value): string
    {
        $value = self::normalize($value);

        return match ($value) {
            'closed', 'close', 'ferme', 'fermee', 'fechado', 'fechada', 'inactive' => 'closed',
            default => 'active',
        };
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
                    'excel_file' => __('aho.facility_import.errors.mapping_required', [
                        'type' => __('aho.facility_import.types.'.$type),
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
    private static function mappedId(array $map, mixed $value, string $header, int|string $row, array &$errors): ?int
    {
        $value = self::cell($value);

        if (blank($value)) {
            $errors[] = __('aho.facility_import.errors.required_cell', ['row' => $row, 'field' => $header]);

            return null;
        }

        $id = $map[self::normalize($value)] ?? null;

        if (! $id) {
            $errors[] = __('aho.facility_import.errors.unmapped_cell', [
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
        foreach (['name', 'type_id', 'owner_id', 'location_id'] as $key) {
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
            self::normalize($payload['name']),
            $payload['location_id'],
            self::normalize($payload['admin_location'] ?? ''),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function duplicateExists(array $payload): bool
    {
        return HealthFacility::query()
            ->where('location_id', $payload['location_id'])
            ->whereRaw('LOWER(name) = ?', [Str::lower($payload['name'])])
            ->whereRaw('LOWER(COALESCE(admin_location, ?)) = ?', ['', Str::lower((string) ($payload['admin_location'] ?? ''))])
            ->exists();
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
            $visibleErrors[] = __('aho.facility_import.errors.more_errors', ['count' => $remaining]);
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
            self::labelFor($record),
            $record->display_name ?? null,
            $record->name ?? null,
            $record->shortname ?? null,
            $record->code ?? null,
        ];

        if ($type === 'location') {
            $values[] = $record->iso_alpha ?? null;
            $values[] = $record->iso_number ?? null;
        }

        foreach ($record->translations ?? [] as $translation) {
            $values[] = $translation->name ?? null;
            $values[] = $translation->shortname ?? null;
        }

        return array_values(array_filter($values, fn (mixed $value): bool => filled($value)));
    }

    private static function labelFor(Model $record): string
    {
        return $record->display_name
            ?? $record->name
            ?? $record->shortname
            ?? $record->code
            ?? (string) $record->getKey();
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

        return SelectOptions::orderByDisplayName($query, 'code')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, string>
     */
    private static function templateOptions(string $type): array
    {
        $query = self::baseQuery($type);

        if (! $query) {
            return [];
        }

        return SelectOptions::orderByDisplayName($query, 'code')
            ->get()
            ->map(fn (Model $record): string => self::labelFor($record))
            ->filter(fn (string $label): bool => filled($label))
            ->values()
            ->all();
    }

    private static function baseQuery(string $type): ?Builder
    {
        return match ($type) {
            'type' => FacilityType::query()->with('translations'),
            'owner' => FacilityOwner::query()->with('translations'),
            'location' => UserCountryAccess::scope(Country::query()->with('translations')),
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

        $query->where(function (Builder $query) use ($exact, $search, $terms, $type): void {
            foreach ($terms as $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

                $exact
                    ? $query->orWhere('code', $search)
                    : $query->orWhere('code', 'like', $like);

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
            }
        });
    }

    private static function recordImport(int $count, string $fileName): void
    {
        try {
            ImportRecord::query()->create([
                'record_count' => $count,
                'loader' => $fileName,
                'serializer' => 'Laravel facility Excel import',
                'object_id' => null,
                'content_type_id' => null,
                'user_id' => auth()->id() ?? 1,
            ]);
        } catch (Throwable) {
            // Optional import history must not make a successful import fail.
        }
    }
}
