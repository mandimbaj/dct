<?php

namespace Tests\Unit;

use App\Models\HealthCadreTranslation;
use App\Support\WarehouseForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use ReflectionMethod;
use Tests\TestCase;

class TranslatedDimensionFormSupportTest extends TestCase
{
    public function test_warehouse_translation_models_accept_form_attributes(): void
    {
        $translation = new HealthCadreTranslation;

        $translation->fill([
            'language_code' => 'fr',
            'name' => 'Sage-femme',
            'shortname' => 'Sage-femme',
            'academic' => 'diplome',
        ]);

        $this->assertSame('fr', $translation->language_code);
        $this->assertSame('Sage-femme', $translation->name);
        $this->assertSame('diplome', $translation->academic);
    }

    public function test_relation_detection_does_not_call_inherited_eloquent_methods(): void
    {
        $model = new class extends Model
        {
            protected $table = 'dimension_records';

            public function children(): HasMany
            {
                return $this->hasMany(self::class, 'parent_id');
            }
        };

        $method = new ReflectionMethod(WarehouseForm::class, 'relationNameByForeignKey');

        $this->assertNull($method->invoke(null, 'dimension_id', $model));
        $this->assertNotNull(Model::getConnectionResolver());
    }
}
