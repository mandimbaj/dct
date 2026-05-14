<?php

namespace App\Filament\Resources\KnowledgeProducts\Pages;

use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeProduct extends CreateRecord
{
    protected static string $resource = KnowledgeProductResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
