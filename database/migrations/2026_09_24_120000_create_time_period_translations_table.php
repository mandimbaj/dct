<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->withWarehouse(function (): void {
            if (! Schema::connection('warehouse')->hasTable('stg_periodicity_type_translation')) {
                Schema::connection('warehouse')->create('stg_periodicity_type_translation', function (Blueprint $table): void {
                    $table->id();
                    $table->string('language_code', 10);
                    $table->string('name')->nullable();
                    $table->string('shortname')->nullable();
                    $table->text('description')->nullable();
                    $table->unsignedBigInteger('master_id');
                    $table->unique(['master_id', 'language_code'], 'period_translation_language_unique');
                    $table->index('language_code', 'period_translation_language_index');
                });
            }

            $this->seedDefaultTranslations();
        });
    }

    public function down(): void
    {
        $this->withWarehouse(function (): void {
            Schema::connection('warehouse')->dropIfExists('stg_periodicity_type_translation');
        });
    }

    private function withWarehouse(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $exception) {
            if (app()->environment('testing')) {
                return;
            }

            throw $exception;
        }
    }

    private function seedDefaultTranslations(): void
    {
        if (! Schema::connection('warehouse')->hasTable('stg_periodicity_type')) {
            return;
        }

        $translations = [
            'monthly' => [
                'en' => ['Monthly', 'Monthly', 'Monthly reporting period'],
                'fr' => ['Mensuel', 'Mensuel', 'Période de rapport mensuelle'],
                'pt' => ['Mensal', 'Mensal', 'Período de reporte mensal'],
            ],
            'quarterly' => [
                'en' => ['Quarterly', 'Quarterly', 'Quarterly reporting period'],
                'fr' => ['Trimestriel', 'Trimestriel', 'Période de rapport trimestrielle'],
                'pt' => ['Trimestral', 'Trimestral', 'Período de reporte trimestral'],
            ],
            'halfyearly' => [
                'en' => ['Half Yearly', 'Half Year', 'Half-year reporting period'],
                'fr' => ['Semestriel', 'Semestre', 'Période de rapport semestrielle'],
                'pt' => ['Semestral', 'Semestre', 'Período de reporte semestral'],
            ],
            'yearly' => [
                'en' => ['Yearly', 'Annually', 'Annual reporting'],
                'fr' => ['Annuel', 'Annuel', 'Période de rapport annuelle'],
                'pt' => ['Anual', 'Anual', 'Reporte anual'],
            ],
        ];

        $periods = DB::connection('warehouse')
            ->table('stg_periodicity_type')
            ->select('period_id', 'name')
            ->get();

        foreach ($periods as $period) {
            $key = preg_replace('/[^a-z0-9]+/', '', strtolower((string) $period->name)) ?? '';

            if (! isset($translations[$key])) {
                continue;
            }

            foreach ($translations[$key] as $language => [$name, $shortname, $description]) {
                DB::connection('warehouse')
                    ->table('stg_periodicity_type_translation')
                    ->updateOrInsert(
                        ['master_id' => $period->period_id, 'language_code' => $language],
                        [
                            'name' => $name,
                            'shortname' => $shortname,
                            'description' => $description,
                        ],
                    );
            }
        }
    }
};
