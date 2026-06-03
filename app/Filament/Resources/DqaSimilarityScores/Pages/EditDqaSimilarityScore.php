<?php

namespace App\Filament\Resources\DqaSimilarityScores\Pages;

use App\Filament\Resources\DqaSimilarityScores\DqaSimilarityScoreResource;
use App\Filament\Resources\Pages\EditRecordAndReturnToList as EditRecord;
use Filament\Actions\DeleteAction;

class EditDqaSimilarityScore extends EditRecord
{
    protected static string $resource = DqaSimilarityScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
