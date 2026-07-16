<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PublicationFileStorage
{
    public static function store(UploadedFile $file, string $directory): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = (string) Str::ulid();

        if ($extension !== '') {
            $filename .= '.'.$extension;
        }

        $path = trim($directory, '/').'/'.$filename;

        if (! self::usesAzure()) {
            $storedPath = $file->storeAs(trim($directory, '/'), $filename, 'public');

            if (! is_string($storedPath)) {
                throw new RuntimeException('The publication file could not be stored locally.');
            }

            return $storedPath;
        }

        self::putAzureBlob($file, $path);

        return $path;
    }

    /**
     * @return array{name: string, size: int, type: ?string, url: string}
     */
    public static function metadata(string $path): array
    {
        $url = self::url($path);
        $urlPath = parse_url($url, PHP_URL_PATH);

        return [
            'name' => basename(is_string($urlPath) ? $urlPath : $path),
            'size' => 0,
            'type' => null,
            'url' => $url,
        ];
    }

    public static function url(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return self::containerUrl().'/'.self::encodeBlobPath($path);
    }

    public static function usesAzure(): bool
    {
        return (bool) config('filesystems.publications.enabled', false);
    }

    private static function putAzureBlob(UploadedFile $file, string $path): void
    {
        $account = trim((string) config('filesystems.publications.account'));
        $container = trim((string) config('filesystems.publications.container'));
        $accountKey = trim((string) config('filesystems.publications.account_key'));
        $sasToken = ltrim(trim((string) config('filesystems.publications.sas_token')), '?');

        if ($account === '' || $container === '' || ($accountKey === '' && $sasToken === '')) {
            throw new RuntimeException('Azure publication storage is enabled but its credentials are incomplete.');
        }

        $contentType = $file->getMimeType() ?: 'application/octet-stream';
        $contentLength = (string) $file->getSize();
        $date = gmdate('D, d M Y H:i:s').' GMT';
        $headers = [
            'Content-Length' => $contentLength,
            'Content-Type' => $contentType,
            'x-ms-blob-type' => 'BlockBlob',
            'x-ms-date' => $date,
            'x-ms-version' => '2023-11-03',
        ];
        $url = self::containerUrl().'/'.self::encodeBlobPath($path);

        if ($sasToken !== '') {
            $url .= '?'.$sasToken;
        } else {
            $headers['Authorization'] = self::authorizationHeader(
                account: $account,
                accountKey: $accountKey,
                container: $container,
                path: $path,
                contentLength: $contentLength,
                contentType: $contentType,
                date: $date,
            );
        }

        $stream = fopen($file->getRealPath(), 'rb');

        if ($stream === false) {
            throw new RuntimeException('The selected publication file could not be opened.');
        }

        try {
            $response = Http::withHeaders($headers)
                ->send('PUT', $url, ['body' => $stream]);
        } finally {
            fclose($stream);
        }

        if (! $response->successful()) {
            throw new RuntimeException("Azure rejected the publication upload with HTTP {$response->status()}.");
        }
    }

    private static function authorizationHeader(
        string $account,
        string $accountKey,
        string $container,
        string $path,
        string $contentLength,
        string $contentType,
        string $date,
    ): string {
        $decodedKey = base64_decode($accountKey, true);

        if ($decodedKey === false) {
            throw new RuntimeException('The Azure storage account key is invalid.');
        }

        $canonicalHeaders = implode("\n", [
            'x-ms-blob-type:BlockBlob',
            'x-ms-date:'.$date,
            'x-ms-version:2023-11-03',
        ]);
        $canonicalResource = '/'.$account.'/'.$container.'/'.ltrim($path, '/');
        $stringToSign = implode("\n", [
            'PUT',
            '',
            '',
            $contentLength,
            '',
            $contentType,
            '',
            '',
            '',
            '',
            '',
            '',
        ])."\n{$canonicalHeaders}\n{$canonicalResource}";
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $decodedKey, true));

        return "SharedKey {$account}:{$signature}";
    }

    private static function containerUrl(): string
    {
        $endpoint = rtrim((string) config('filesystems.publications.endpoint'), '/');
        $container = trim((string) config('filesystems.publications.container'), '/');

        return "{$endpoint}/{$container}";
    }

    private static function encodeBlobPath(string $path): string
    {
        return collect(explode('/', ltrim($path, '/')))
            ->map(fn (string $segment): string => rawurlencode($segment))
            ->implode('/');
    }
}
