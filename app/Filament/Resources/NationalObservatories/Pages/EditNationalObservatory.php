<?php

namespace App\Filament\Resources\NationalObservatories\Pages;

use App\Filament\Resources\NationalObservatories\NationalObservatoryResource;
use App\Filament\Resources\Pages\EditTranslatedRecord;

class EditNationalObservatory extends EditTranslatedRecord
{
    protected static string $resource = NationalObservatoryResource::class;

    /**
     * @var array<int, string>
     */
    protected static array $translationFields = ['name', 'shortname', 'custom_header', 'custom_footer', 'announcement', 'coat_arms', 'address'];
}
