<?php

namespace App\Filament\Resources\DataIntegrationConnections\Pages;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use App\Models\DataIntegrationConnection;
use App\Models\DataIntegrationFieldMapping;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;

class ConfigureFieldMapping extends Page
{
    protected static string $resource = DataIntegrationConnectionResource::class;

    protected string $view = 'filament.resources.data-integration-connections.pages.configure-field-mapping';

    public DataIntegrationConnection $record;

    public ?array $data = [];

    public Collection $externalFields;

    public function mount(DataIntegrationConnection $record): void
    {
        $this->record = $record;
        $this->externalFields = collect();

        // Detect external fields from the connection
        $this->detectExternalFields();

        $this->form->fill($this->getInitialFormData());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('aho.data_integration.sections.field_mapping_config'))
                    ->description(__('aho.data_integration.help.field_mapping_config_description'))
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Repeater::make('field_mappings')
                            ->label(__('aho.data_integration.fields.field_mappings'))
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('local_field')
                                            ->label(__('aho.data_integration.fields.local_field'))
                                            ->options(DataIntegrationFieldMapping::localFieldOptions())
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(1),
                                        Select::make('external_field')
                                            ->label(__('aho.data_integration.fields.external_field'))
                                            ->options($this->externalFields->mapWithKeys(fn (string $field): array => [$field => $field])->toArray())
                                            ->searchable()
                                            ->creatable()
                                            ->createOptionForm([
                                                TextInput::make('external_field')
                                                    ->label(__('aho.data_integration.fields.external_field'))
                                                    ->required(),
                                            ])
                                            ->required()
                                            ->columnSpan(1),
                                        Select::make('field_type')
                                            ->label(__('aho.data_integration.fields.field_type'))
                                            ->options(DataIntegrationFieldMapping::fieldTypeOptions())
                                            ->default('direct')
                                            ->columnSpan(1),
                                    ]),
                                Toggle::make('is_required')
                                    ->label(__('aho.data_integration.fields.is_required'))
                                    ->default(false),
                            ])
                            ->addActionLabel(__('aho.data_integration.actions.add_mapping'))
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['local_field'] ? "{$state['local_field']} → {$state['external_field']}" : null)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getInitialFormData(): array
    {
        $existingMappings = $this->record->fieldMappings()
            ->get()
            ->map(fn (DataIntegrationFieldMapping $mapping): array => [
                'local_field' => $mapping->local_field,
                'external_field' => $mapping->external_field,
                'field_type' => $mapping->field_type,
                'is_required' => $mapping->is_required,
            ])
            ->toArray();

        return [
            'field_mappings' => $existingMappings ?: [],
        ];
    }

    protected function detectExternalFields(): void
    {
        try {
            $this->externalFields = match ($this->record->integration_method) {
                DataIntegrationConnection::METHOD_API => $this->detectApiFields(),
                DataIntegrationConnection::METHOD_DIRECT => $this->detectDatabaseFields(),
                default => collect(),
            };
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title(__('aho.data_integration.errors.field_detection_failed'))
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function detectApiFields(): Collection
    {
        // This would integrate with the actual API client
        // For now, return empty collection - implement based on provider
        return collect([
            'country',
            'indicator',
            'year',
            'period',
            'sex',
            'ageGroup',
            'value',
            'unit',
            'lowerBound',
            'upperBound',
            'comments',
        ]);
    }

    protected function detectDatabaseFields(): Collection
    {
        try {
            $driver = $this->record->database_driver;
            $tableName = 'health_indicators'; // Default table name

            return match ($driver) {
                'mysql', 'pgsql', 'sqlsrv' => $this->getFieldsFromDatabase(),
                'sqlite' => $this->getFieldsFromDatabase(),
                default => collect(),
            };
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function getFieldsFromDatabase(): Collection
    {
        // Implementation would depend on database connection
        // This is a placeholder that could use Laravel's Schema builder
        return collect([
            'country_code',
            'indicator_code',
            'reporting_year',
            'reporting_period',
            'gender',
            'age_category',
            'reported_value',
            'measurement_unit',
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('aho.actions.save_mapping'))
                ->submit('save'),
            Action::make('skip')
                ->label(__('aho.actions.skip'))
                ->url(DataIntegrationConnectionResource::getUrl('index'))
                ->requiresConfirmation()
                ->action(fn () => $this->redirect(DataIntegrationConnectionResource::getUrl('index'))),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // Delete existing mappings
        $this->record->fieldMappings()->delete();

        // Create new mappings
        foreach ($data['field_mappings'] ?? [] as $index => $mapping) {
            DataIntegrationFieldMapping::create([
                'data_integration_connection_id' => $this->record->id,
                'local_field' => $mapping['local_field'],
                'external_field' => $mapping['external_field'],
                'field_type' => $mapping['field_type'] ?? 'direct',
                'is_required' => $mapping['is_required'] ?? false,
                'sort_order' => $index,
            ]);
        }

        Notification::make()
            ->success()
            ->title(__('aho.data_integration.messages.mapping_saved'))
            ->send();

        $this->redirect(DataIntegrationConnectionResource::getUrl('index'));
    }
}
