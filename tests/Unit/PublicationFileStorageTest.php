<?php

namespace Tests\Unit;

use App\Support\PublicationFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicationFileStorageTest extends TestCase
{
    public function test_it_stores_publications_locally_when_azure_is_disabled(): void
    {
        Storage::fake('public');
        config()->set('filesystems.publications.enabled', false);

        $path = PublicationFileStorage::store(
            UploadedFile::fake()->create('report.pdf', 20, 'application/pdf'),
            'production/files',
        );

        Storage::disk('public')->assertExists($path);
        $this->assertStringStartsWith('production/files/', $path);
    }

    public function test_it_uploads_publications_to_the_django_azure_directory(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://afahobckpstorageaccount.blob.core.windows.net/*' => Http::response('', 201),
        ]);
        config()->set('filesystems.publications', [
            'enabled' => true,
            'account' => 'afahobckpstorageaccount',
            'account_key' => base64_encode('test-account-key'),
            'container' => 'afahobckpcontainer',
            'sas_token' => '',
            'endpoint' => 'https://afahobckpstorageaccount.blob.core.windows.net',
        ]);

        $path = PublicationFileStorage::store(
            UploadedFile::fake()->create('report.pdf', 20, 'application/pdf'),
            'production/files',
        );

        $this->assertStringStartsWith('production/files/', $path);
        Http::assertSent(function ($request) use ($path): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'https://afahobckpstorageaccount.blob.core.windows.net/afahobckpcontainer/'.$path
                && $request->hasHeader('x-ms-blob-type', 'BlockBlob')
                && str_starts_with($request->header('Authorization')[0] ?? '', 'SharedKey afahobckpstorageaccount:');
        });
    }

    public function test_it_builds_the_public_azure_url_for_existing_paths(): void
    {
        Storage::fake('public');
        config()->set('filesystems.publications', [
            'enabled' => true,
            'account' => 'afahobckpstorageaccount',
            'account_key' => '',
            'container' => 'afahobckpcontainer',
            'sas_token' => 'test-sas',
            'endpoint' => 'https://afahobckpstorageaccount.blob.core.windows.net',
        ]);

        $this->assertSame(
            'https://afahobckpstorageaccount.blob.core.windows.net/afahobckpcontainer/production/files/report%202026.pdf',
            PublicationFileStorage::url('production/files/report 2026.pdf'),
        );
    }
}
