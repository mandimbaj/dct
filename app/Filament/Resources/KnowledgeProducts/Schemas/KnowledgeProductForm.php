<?php

namespace App\Filament\Resources\KnowledgeProducts\Schemas;

use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Support\ApprovalWorkflow;
use App\Support\UserPermissions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KnowledgeProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label(__('aho.fields.code'))
                    ->required()
                    ->maxLength(255),

                Select::make('location_id')
                    ->label(__('aho.fields.location'))
                    ->relationship('location', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable()
                    ->preload(),

                Select::make('type_id')
                    ->label(__('aho.fields.type'))
                    ->relationship('type', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable()
                    ->preload(),

                Select::make('categorization_id')
                    ->label(__('aho.fields.category'))
                    ->relationship('category', 'code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)
                    ->searchable()
                    ->preload(),

                Select::make('comment')
                    ->label(__('aho.fields.approval_status'))
                    ->options(fn (): array => ApprovalWorkflow::options())
                    ->default(ApprovalWorkflow::STATUS_PENDING)
                    ->required(fn (): bool => (bool) (
                        auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), KnowledgeProductResource::class, UserPermissions::ACTION_APPROVE)
                    ))
                    ->disabled(fn (): bool => ! (
                        auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), KnowledgeProductResource::class, UserPermissions::ACTION_APPROVE)
                    ))
                    ->dehydrated(fn (): bool => (bool) (
                        auth()->user()
                        && UserPermissions::allowsResource(auth()->user(), KnowledgeProductResource::class, UserPermissions::ACTION_APPROVE)
                    )),
            ]);
    }
}
