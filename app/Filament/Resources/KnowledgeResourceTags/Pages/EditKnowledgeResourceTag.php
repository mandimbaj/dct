<?php

namespace App\Filament\Resources\KnowledgeResourceTags\Pages;

use App\Filament\Resources\KnowledgeResourceTags\KnowledgeResourceTagResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditKnowledgeResourceTag extends EditRecord
{
    protected static string $resource = KnowledgeResourceTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
