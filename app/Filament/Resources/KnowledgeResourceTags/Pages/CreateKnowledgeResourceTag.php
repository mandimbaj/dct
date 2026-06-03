<?php

namespace App\Filament\Resources\KnowledgeResourceTags\Pages;

use App\Filament\Resources\KnowledgeResourceTags\KnowledgeResourceTagResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKnowledgeResourceTag extends CreateRecord
{
    protected static string $resource = KnowledgeResourceTagResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
