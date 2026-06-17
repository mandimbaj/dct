<?php

namespace App\Filament\Resources\DataIntegrationConnections\Pages;

use App\Filament\Resources\DataIntegrationConnections\DataIntegrationConnectionResource;
use App\Models\DataIntegrationConnection;
use App\Models\DataIntegrationFieldMapping;
use App\Support\DataIntegration\ExternalFieldDetector;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class ConfigureFieldMapping extends Page
{
    protected static string $resource = DataIntegrationConnectionResource::class;

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

    public function getTitle(): string
    {
        return __('aho.data_integration.sections.field_mapping_config');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('aho.data_integration.sections.field_mapping_config'))
                    ->description(__('aho.data_integration.help.field_mapping_config_description'))
                    ->icon('heroicon-o-arrow-path')
                    ->schema([
                        Actions::make([
                            Action::make('refresh_external_fields')
                                ->label(__('aho.data_integration.actions.refresh_external_fields'))
                                ->icon('heroicon-o-arrow-path')
                                ->color('gray')
                                ->action(fn (): null => $this->refreshExternalFields()),
                        ])
                            ->columnSpanFull(),
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
                                            ->createOptionForm([
                                                TextInput::make('external_field')
                                                    ->label(__('aho.data_integration.fields.external_field'))
                                                    ->required(),
                                            ])
                                            ->createOptionUsing(fn (array $data): string => $data['external_field'])
                                            ->helperText(__('aho.data_integration.help.external_field'))
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
                                TextInput::make('default_value')
                                    ->label(__('aho.data_integration.fields.default_value'))
                                    ->helperText(__('aho.data_integration.help.default_value')),
                                Textarea::make('transformation_rule')
                                    ->label(__('aho.data_integration.fields.transformation_rule'))
                                    ->helperText(__('aho.data_integration.help.transformation_rule'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Textarea::make('notes')
                                    ->label(__('aho.data_integration.fields.notes'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel(__('aho.data_integration.actions.add_mapping'))
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['local_field'] ?? null) ? "{$state['local_field']} -> ".($state['external_field'] ?? '') : null)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    EmbeddedSchema::make('form'),
                ])
                    ->id('field-mapping-form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make($this->getFormActions())
                            ->alignment($this->getFormActionsAlignment())
                            ->key('field-mapping-actions'),
                    ]),
            ]);
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
                'default_value' => $mapping->transformation_config['default_value'] ?? null,
                'transformation_rule' => $mapping->transformation_config['rule'] ?? null,
                'notes' => $mapping->notes,
            ])
            ->toArray();

        return [
            'field_mappings' => $existingMappings ?: [],
        ];
    }

    protected function detectExternalFields(): void
    {
        try {
            $this->externalFields = ExternalFieldDetector::detect($this->record);
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title(__('aho.data_integration.errors.field_detection_failed'))
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('aho.actions.save_mapping'))
                ->icon('heroicon-o-check-circle')
                ->submit('save'),
            Action::make('back')
                ->label(__('aho.actions.back_to_connections'))
                ->color('gray')
                ->url(DataIntegrationConnectionResource::getUrl('index')),
        ];
    }

    public function refreshExternalFields(): null
    {
        $this->detectExternalFields();

        $count = $this->externalFields->count();

        $notification = Notification::make()
            ->title($count > 0
                ? __('aho.data_integration.messages.external_fields_loaded', ['count' => $count])
                : __('aho.data_integration.messages.no_external_fields'));

        $count > 0 ? $notification->success() : $notification->warning();
        $notification->send();

        return null;
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
                'transformation_config' => array_filter([
                    'default_value' => $mapping['default_value'] ?? null,
                    'rule' => $mapping['transformation_rule'] ?? null,
                ], fn (mixed $value): bool => filled($value)),
                'notes' => $mapping['notes'] ?? null,
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
