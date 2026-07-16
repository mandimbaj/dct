<?php

namespace Tests\Unit;

use App\Models\KnowledgeProduct;
use App\Support\TranslatedReferenceForm;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use ReflectionMethod;
use Tests\TestCase;

class KnowledgeProductUploadFieldTest extends TestCase
{
    public function test_publication_file_paths_use_upload_fields(): void
    {
        $factory = new ReflectionMethod(TranslatedReferenceForm::class, 'translationComponent');

        $internalFile = $factory->invoke(null, $this->column('internal_url', 2000), KnowledgeProduct::class);
        $coverImage = $factory->invoke(null, $this->column('cover_image', 100), KnowledgeProduct::class);
        $externalUrl = $factory->invoke(null, $this->column('external_url', 2083), KnowledgeProduct::class);

        $this->assertInstanceOf(FileUpload::class, $internalFile);
        $this->assertInstanceOf(FileUpload::class, $coverImage);
        $this->assertInstanceOf(TextInput::class, $externalUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function column(string $name, int $length): array
    {
        return [
            'name' => $name,
            'type' => "varchar({$length})",
            'type_name' => 'varchar',
            'nullable' => true,
            'default' => null,
            'auto_increment' => false,
        ];
    }
}
