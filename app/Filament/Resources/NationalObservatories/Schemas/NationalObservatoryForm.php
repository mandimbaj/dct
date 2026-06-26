<?php

namespace App\Filament\Resources\NationalObservatories\Schemas;

use App\Models\Country;
use App\Models\LocationCode;
use App\Models\NationalObservatory;
use App\Support\SelectOptions;
use App\Support\UserCountryAccess;
use App\Support\WarehouseLocale;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class NationalObservatoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('uuid'),
            Hidden::make('code'),
            Hidden::make('user_id')
                ->default(fn (): ?int => auth()->id()),

            Section::make(__('aho.form_sections.observatory_country'))
                ->description(__('aho.help.national_observatory_scope'))
                ->schema([
                    Select::make('location_id')
                        ->label(__('aho.fields.country'))
                        ->options(fn (Get $get): array => self::countryOptions(selectedLocationId: $get('location_id')))
                        ->getSearchResultsUsing(fn (?string $search, Get $get): array => self::countryOptions($search, $get('location_id')))
                        ->default(fn (): ?int => UserCountryAccess::canViewAllCountries() ? null : UserCountryAccess::locationId())
                        ->afterStateHydrated(fn (Get $get, Set $set): mixed => self::syncPhoneCodeFromLocation($set, self::selectedOrAssignedLocationId($get)))
                        ->afterStateUpdated(fn (Set $set, mixed $state): mixed => self::syncPhoneCodeFromLocation($set, $state))
                        ->live()
                        ->searchable()
                        ->required()
                        ->disabled(fn (): bool => ! UserCountryAccess::canViewAllCountries())
                        ->dehydrated(),
                ])
                ->columns(2),

            Section::make(__('aho.form_sections.observatory_contact_details'))
                ->schema([
                    TextInput::make('email')
                        ->label(__('aho.fields.email'))
                        ->email()
                        ->maxLength(250),
                    TextInput::make('phone_code')
                        ->label(__('aho.fields.phone_code'))
                        ->helperText(__('aho.help.phone_code_auto'))
                        ->default(fn (Get $get): ?string => self::phoneCodeForSelectedOrAssignedLocation($get))
                        ->afterStateHydrated(fn (Get $get, Set $set): mixed => self::syncPhoneCodeFromLocation($set, self::selectedOrAssignedLocationId($get)))
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->maxLength(5),
                    TextInput::make('phone_part')
                        ->label(__('aho.fields.phone_part'))
                        ->helperText(__('aho.help.national_observatory_phone_part'))
                        ->rules(['regex:/^[0-9]{8,15}$/'])
                        ->required()
                        ->maxLength(15),
                    TextInput::make('phone_number')
                        ->label(__('aho.fields.phone_number'))
                        ->helperText(__('aho.help.phone_number_auto'))
                        ->disabled()
                        ->dehydrated(false)
                        ->maxLength(20),
                    TextInput::make('url')
                        ->label(__('aho.fields.url'))
                        ->url()
                        ->maxLength(2083)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('aho.form_sections.translations'))
                ->description(__('aho.help.translations'))
                ->schema([
                    Repeater::make('translations')
                        ->relationship(
                            modifyQueryUsing: fn (Builder $query): Builder => $query
                                ->orderByRaw("CASE language_code WHEN 'en' THEN 1 WHEN 'fr' THEN 2 WHEN 'pt' THEN 3 ELSE 4 END"),
                        )
                        ->hiddenLabel()
                        ->schema([
                            Section::make(__('aho.form_sections.observatory_header_details'))
                                ->schema([
                                    Select::make('language_code')
                                        ->label(__('aho.fields.language'))
                                        ->options(fn (): array => WarehouseLocale::supported())
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->distinct()
                                        ->required(),
                                    TextInput::make('name')
                                        ->label(__('aho.fields.observatory_title'))
                                        ->required()
                                        ->maxLength(500),
                                    TextInput::make('shortname')
                                        ->label(__('aho.fields.short_name'))
                                        ->maxLength(100),
                                    Textarea::make('custom_header')
                                        ->label(__('aho.fields.custom_header'))
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),
                                    FileUpload::make('coat_arms')
                                        ->label(__('aho.fields.coat_arms'))
                                        ->helperText(__('aho.help.coat_arms_upload'))
                                        ->disk('public')
                                        ->directory('production/images')
                                        ->image()
                                        ->columnSpanFull(),
                                ])
                                ->columns(2),

                            Section::make(__('aho.form_sections.observatory_local_contact'))
                                ->schema([
                                    Textarea::make('address')
                                        ->label(__('aho.fields.address'))
                                        ->rows(3)
                                        ->maxLength(500)
                                        ->columnSpanFull(),
                                ]),

                            Section::make(__('aho.form_sections.observatory_footer_details'))
                                ->schema([
                                    Textarea::make('custom_footer')
                                        ->label(__('aho.fields.custom_footer'))
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),
                                ]),

                            Section::make(__('aho.form_sections.observatory_announcements'))
                                ->schema([
                                    Textarea::make('announcement')
                                        ->label(__('aho.fields.announcement'))
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->default([
                            ['language_code' => WarehouseLocale::current()],
                        ])
                        ->itemLabel(fn (array $state): string => trim((string) ($state['name'] ?? '')) ?: (WarehouseLocale::supported()[$state['language_code'] ?? ''] ?? __('aho.fields.language')))
                        ->addActionLabel(__('aho.actions.add_language'))
                        ->minItems(1)
                        ->maxItems(count(WarehouseLocale::supported()))
                        ->reorderable(false)
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private static function countryOptions(?string $search = null, mixed $selectedLocationId = null): array
    {
        return SelectOptions::fromDisplayNameQuery(
            self::countriesWithDialCodes($search, $selectedLocationId),
            $search,
            'location_id',
        );
    }

    private static function countriesWithDialCodes(?string $search = null, mixed $selectedLocationId = null): Builder
    {
        $locationCodeIds = LocationCode::query()->select('location_id');
        $usedLocationIds = NationalObservatory::query()
            ->select('location_id')
            ->whereNotNull('location_id');

        return UserCountryAccess::scopeLocations(
            Country::query()
                ->whereIn('location_id', $locationCodeIds)
                ->where(function (Builder $query) use ($selectedLocationId, $usedLocationIds): void {
                    $query->whereNotIn('location_id', $usedLocationIds);

                    if (filled($selectedLocationId)) {
                        $query->orWhere('location_id', $selectedLocationId);
                    }
                })
                ->when($search, fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('translations', fn (Builder $query): Builder => $query->where('name', 'like', "%{$search}%"));
                })),
        );
    }

    private static function syncPhoneCodeFromLocation(Set $set, mixed $locationId): void
    {
        if (blank($locationId)) {
            $set('phone_code', null);

            return;
        }

        $set('phone_code', NationalObservatory::phoneCodeForLocation($locationId));
    }

    private static function selectedOrAssignedLocationId(Get $get): mixed
    {
        return $get('location_id') ?: (
            UserCountryAccess::canViewAllCountries()
                ? null
                : UserCountryAccess::locationId()
        );
    }

    private static function phoneCodeForSelectedOrAssignedLocation(Get $get): ?string
    {
        $locationId = self::selectedOrAssignedLocationId($get);

        return filled($locationId)
            ? NationalObservatory::phoneCodeForLocation($locationId)
            : null;
    }
}
