<?php

namespace App\Filament\Resources\KnowledgeProducts\Pages;

use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Filament\Resources\KnowledgeProducts\Pages\Concerns\EnforcesKnowledgeProductData;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeProduct extends CreateRecord
{
    use EnforcesKnowledgeProductData;

    protected static string $resource = KnowledgeProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
