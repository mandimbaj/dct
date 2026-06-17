<?php

namespace App\Filament\Resources\HealthIndicatorValues\Pages;

use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Support\IndicatorExcelImport;
use App\Support\UserPermissions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Text;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ListHealthIndicatorValues extends ListRecords
{
    protected static string $resource = HealthIndicatorValueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_data')
                ->label(__('aho.actions.import_data'))
                ->icon(Heroicon::ArrowUpTray)
                ->modalWidth(Width::SevenExtraLarge)
                ->modalHeading(__('aho.indicator_import.modal_heading'))
                ->modalDescription(__('aho.indicator_import.modal_description'))
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->steps([
                    Step::make(__('aho.indicator_import.steps.upload'))
                        ->description(__('aho.indicator_import.steps.upload_description'))
                        ->schema([
                            FileUpload::make('excel_file')
                                ->label(__('aho.indicator_import.file'))
                                ->helperText(__('aho.indicator_import.file_help'))
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'application/zip',
                                    'application/octet-stream',
                                ])
                                ->maxSize(10240)
                                ->storeFiles(false)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (mixed $state, Set $set): void {
                                    self::loadImportPreview($state, $set, shouldThrow: false);
                                }),
                            Text::make(__('aho.indicator_import.required_headers') . ': ' . implode(' | ', IndicatorExcelImport::HEADERS)),
                            Actions::make([
                                Action::make('download_indicator_import_template')
                                    ->label(__('aho.indicator_import.download_template'))
                                    ->icon(Heroicon::OutlinedArrowDownTray)
                                    ->color('gray')
                                    ->action(fn () => IndicatorExcelImport::downloadTemplate()),
                            ]),
                            Text::make(fn (Get $get): string => __('aho.indicator_import.format_status') . ': ' . ($get('format_status') ?: __('aho.indicator_import.waiting_for_file'))),
                            Hidden::make('detected_rows'),
                        ])
                        ->afterValidation(function (Get $get, Set $set): void {
                            self::loadImportPreview($get('excel_file'), $set);
                        }),
                    Step::make(__('aho.indicator_import.steps.mapping'))
                        ->description(__('aho.indicator_import.steps.mapping_description'))
                        ->schema([
                            Text::make(__('aho.indicator_import.mapping_help_title') . ': ' . __('aho.indicator_import.mapping_help')),
                            self::mappingRepeater('indicator_mappings', __('aho.indicator_import.sections.indicators'), 'indicator'),
                            self::mappingRepeater('location_mappings', __('aho.indicator_import.sections.locations'), 'location'),
                            self::mappingRepeater('category_mappings', __('aho.indicator_import.sections.categories'), 'category'),
                            self::mappingRepeater('datasource_mappings', __('aho.indicator_import.sections.datasources'), 'datasource'),
                            self::mappingRepeater('measuremethod_mappings', __('aho.indicator_import.sections.measuremethods'), 'measuremethod'),
                        ]),
                    Step::make(__('aho.indicator_import.steps.confirm'))
                        ->description(__('aho.indicator_import.steps.confirm_description'))
                        ->schema([
                            Text::make(fn (Get $get): string => __('aho.indicator_import.summary') . ': ' . __('aho.indicator_import.review_summary', [
                                    'rows' => (int) ($get('detected_rows') ?? 0),
                                    'indicators' => count($get('indicator_mappings') ?? []),
                                    'locations' => count($get('location_mappings') ?? []),
                                    'categories' => count($get('category_mappings') ?? []),
                                    'sources' => count($get('datasource_mappings') ?? []),
                                    'measures' => count($get('measuremethod_mappings') ?? []),
                                ])),
                        ]),
                    Step::make(__('aho.indicator_import.steps.progress'))
                        ->description(__('aho.indicator_import.steps.progress_description'))
                        ->schema([
                            Text::make(__('aho.indicator_import.progress_title') . ': ' . __('aho.indicator_import.progress_notice')),
                        ]),
                ])
                ->modalSubmitActionLabel(__('aho.indicator_import.start_import'))
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(__('aho.indicator_import.start_import'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('success')
                    ->tooltip(__('aho.indicator_import.processing_hint')))
                ->action(function (array $data): void {
                    try {
                        $result = IndicatorExcelImport::import($data['excel_file'] ?? null, $data);
                    } catch (ValidationException $exception) {
                        self::sendImportFailureNotification(
                            collect($exception->errors())->flatten()->implode("\n") ?: $exception->getMessage(),
                        );

                        throw $exception;
                    } catch (Throwable $exception) {
                        Log::error('Indicator Excel import failed.', [
                            'user_id' => auth()->id(),
                            'exception' => $exception,
                        ]);

                        $message = __('aho.indicator_import.errors.connection_or_server');

                        self::sendImportFailureNotification($message);

                        throw ValidationException::withMessages([
                            'excel_file' => $message,
                        ]);
                    }

                    Notification::make()
                        ->title(__('aho.indicator_import.imported_title'))
                        ->body(__('aho.indicator_import.imported_body', ['rows' => $result['created']]))
                        ->actions([self::notificationOkAction('import_success_ok', 'success')])
                        ->success()
                        ->persistent()
                        ->send();
                })
                ->visible(fn (): bool => (bool) auth()->user() && UserPermissions::allowsResource(auth()->user(), HealthIndicatorValueResource::class, UserPermissions::ACTION_IMPORT)),
            CreateAction::make()
                ->label(__('aho.actions.add_indicator_value')),
        ];
    }

    private static function mappingRepeater(string $name, string $label, string $type): Repeater
    {
        return Repeater::make($name)
            ->label($label)
            ->schema([
                TextInput::make('file_value')
                    ->label(__('aho.indicator_import.file_value'))
                    ->disabled()
                    ->dehydrated(),
                Select::make('matched_id')
                    ->label(__('aho.indicator_import.database_match'))
                    ->helperText(__('aho.indicator_import.match_hint'))
                    ->options(fn (Get $get): array => IndicatorExcelImport::optionsFor($type, $get('file_value')))
                    ->getSearchResultsUsing(fn (Get $get, ?string $search): array => IndicatorExcelImport::optionsFor($type, $search ?: $get('file_value')))
                    ->getOptionLabelUsing(fn (mixed $value): ?string => IndicatorExcelImport::labelForId($type, $value))
                    ->searchable()
                    ->required(),
            ])
            ->columns(2)
            ->defaultItems(0)
            ->addable(false)
            ->deletable(false)
            ->reorderable(false)
            ->itemLabel(fn (array $state): ?string => $state['file_value'] ?? null);
    }

    private static function loadImportPreview(mixed $file, Set $set, bool $shouldThrow = true): void
    {
        if (! IndicatorExcelImport::hasFile($file)) {
            self::clearImportPreview($set);
            $set('format_status', __('aho.indicator_import.waiting_for_file'));

            return;
        }

        try {
            $preview = IndicatorExcelImport::preview($file);

            foreach ($preview as $key => $value) {
                $set($key, $value);
            }
        } catch (ValidationException $exception) {
            self::clearImportPreview($set);

            $message = collect($exception->errors())->flatten()->first() ?? $exception->getMessage();

            Notification::make()
                ->title(__('aho.indicator_import.format_error'))
                ->body($message)
                ->danger()
                ->send();

            $set('format_status', $message);

            if ($shouldThrow) {
                throw $exception;
            }
        }
    }

    private static function clearImportPreview(Set $set): void
    {
        foreach ([
            'detected_rows',
            'indicator_mappings',
            'location_mappings',
            'category_mappings',
            'datasource_mappings',
            'measuremethod_mappings',
        ] as $key) {
            $set($key, null);
        }
    }

    private static function sendImportFailureNotification(string $message): void
    {
        Notification::make()
            ->title(__('aho.indicator_import.failed_title'))
            ->body($message)
            ->actions([self::notificationOkAction('import_failure_ok', 'danger')])
            ->danger()
            ->persistent()
            ->send();
    }

    private static function notificationOkAction(string $name, string $color): Action
    {
        return Action::make($name)
            ->label(__('aho.actions.ok'))
            ->button()
            ->color($color)
            ->close();
    }
}
