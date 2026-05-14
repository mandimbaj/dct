<?php

namespace App\Filament\Resources\KnowledgeProducts\Pages;

use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeProducts extends ListRecords
{
    protected static string $resource = KnowledgeProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
