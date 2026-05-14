<?php

namespace App\Filament\Resources\KnowledgeProducts\Pages;

use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Models\KnowledgeProduct;
use App\Support\ApprovalWorkflow;
use App\Support\UserPermissions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditKnowledgeProduct extends EditRecord
{
    protected static string $resource = KnowledgeProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('aho.actions.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord() instanceof KnowledgeProduct
                    && ! ApprovalWorkflow::isApproved($this->getRecord())
                    && (bool) auth()->user()
                    && UserPermissions::allowsResource(auth()->user(), KnowledgeProductResource::class, UserPermissions::ACTION_APPROVE))
                ->action(function (): void {
                    ApprovalWorkflow::approve($this->getRecord());
                }),
            DeleteAction::make(),
        ];
    }
}
