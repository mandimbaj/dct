<?php

namespace App\Filament\Resources\KnowledgeProducts\Pages\Concerns;

use App\Models\ResourceCategory;
use App\Support\ApprovalWorkflow;
use App\Support\ResourceTranslations;
use App\Support\UserCountryAccess;
use Illuminate\Validation\ValidationException;

trait EnforcesKnowledgeProductData
{
    /**
     * @var array<string, mixed>
     */
    private array $translationData = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ResourceTranslations::fill($data, $this->getRecord(), $this->translationFields());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationData = ResourceTranslations::extract($data, $this->translationFields());
        $data = $this->enforceKnowledgeProductData($data);
        $data[ApprovalWorkflow::STATUS_COLUMN] = ApprovalWorkflow::STATUS_PENDING;
        $data[ApprovalWorkflow::MIRROR_COLUMN] = ApprovalWorkflow::STATUS_PENDING;
        $data['approved_by'] = null;
        $data['approved_at'] = null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationData = ResourceTranslations::extract($data, $this->translationFields());

        return $this->enforceKnowledgeProductData($data);
    }

    protected function afterCreate(): void
    {
        ResourceTranslations::sync($this->getRecord(), $this->translationData);
    }

    protected function afterSave(): void
    {
        ResourceTranslations::sync($this->getRecord(), $this->translationData);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function enforceKnowledgeProductData(array $data): array
    {
        $data = UserCountryAccess::enforceLocationData($data);

        if (blank($data['type_id'] ?? null) || blank($data['categorization_id'] ?? null)) {
            return $data;
        }

        $categoryMatchesType = ResourceCategory::query()
            ->whereKey($data['categorization_id'])
            ->where('type_id', $data['type_id'])
            ->exists();

        if ($categoryMatchesType) {
            return $data;
        }

        throw ValidationException::withMessages([
            'data.categorization_id' => __('aho.validation.publication_category_type_mismatch'),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function translationFields(): array
    {
        return [
            'title',
            'description',
            'abstract',
            'author',
            'year_published',
            'internal_url',
            'external_url',
            'cover_image',
        ];
    }
}
