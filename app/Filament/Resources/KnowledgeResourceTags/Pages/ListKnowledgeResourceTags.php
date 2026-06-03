<?php

namespace App\Filament\Resources\KnowledgeResourceTags\Pages;

use App\Filament\Resources\KnowledgeResourceTags\KnowledgeResourceTagResource;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeResourceTags extends ListRecords
{
    protected static string $resource = KnowledgeResourceTagResource::class;
}
