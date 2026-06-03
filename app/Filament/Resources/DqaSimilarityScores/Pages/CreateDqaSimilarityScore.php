<?php

namespace App\Filament\Resources\DqaSimilarityScores\Pages;

use App\Filament\Resources\DqaSimilarityScores\DqaSimilarityScoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDqaSimilarityScore extends CreateRecord
{
    protected static string $resource = DqaSimilarityScoreResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
