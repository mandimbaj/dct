<?php

namespace App\Filament\Resources\Concerns;

trait SearchesTranslatedRecords
{
    /**
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'code',
            'translations.name',
        ];
    }
}
