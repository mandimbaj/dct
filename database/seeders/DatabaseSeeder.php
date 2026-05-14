<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\DataSource;
use App\Models\Indicator;
use App\Models\IndicatorCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'MORTALITY', 'name' => 'Mortalite', 'description' => 'Indicateurs de mortalite et survie.'],
            ['code' => 'SERVICE_COVERAGE', 'name' => 'Couverture des services', 'description' => 'Acces et utilisation des services essentiels.'],
            ['code' => 'DISEASE', 'name' => 'Maladies', 'description' => 'Charge et incidence des maladies prioritaires.'],
            ['code' => 'FINANCING', 'name' => 'Financement', 'description' => 'Depenses et ressources pour la sante.'],
        ];

        foreach ($categories as $category) {
            IndicatorCategory::updateOrCreate(['code' => $category['code']], $category);
        }

        $countries = [
            ['name' => 'Cameroon', 'iso2' => 'CM', 'iso3' => 'CMR'],
            ['name' => 'Congo', 'iso2' => 'CG', 'iso3' => 'COG'],
            ['name' => 'Democratic Republic of the Congo', 'iso2' => 'CD', 'iso3' => 'COD'],
            ['name' => 'Ethiopia', 'iso2' => 'ET', 'iso3' => 'ETH'],
            ['name' => 'Ghana', 'iso2' => 'GH', 'iso3' => 'GHA'],
            ['name' => 'Kenya', 'iso2' => 'KE', 'iso3' => 'KEN'],
            ['name' => 'Nigeria', 'iso2' => 'NG', 'iso3' => 'NGA'],
            ['name' => 'Rwanda', 'iso2' => 'RW', 'iso3' => 'RWA'],
            ['name' => 'Senegal', 'iso2' => 'SN', 'iso3' => 'SEN'],
            ['name' => 'South Africa', 'iso2' => 'ZA', 'iso3' => 'ZAF'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(['iso3' => $country['iso3']], $country + ['who_region' => 'AFRO']);
        }

        $sources = [
            ['code' => 'WHO_GHO', 'name' => 'WHO Global Health Observatory', 'organization' => 'World Health Organization', 'url' => 'https://www.who.int/data/gho'],
            ['code' => 'DHIS2', 'name' => 'DHIS2 national', 'organization' => 'Ministry of Health'],
            ['code' => 'NATIONAL_REPORT', 'name' => 'Rapport national', 'organization' => 'Ministry of Health'],
        ];

        foreach ($sources as $source) {
            DataSource::updateOrCreate(['code' => $source['code']], $source);
        }

        $mortality = IndicatorCategory::where('code', 'MORTALITY')->first();
        $coverage = IndicatorCategory::where('code', 'SERVICE_COVERAGE')->first();
        $disease = IndicatorCategory::where('code', 'DISEASE')->first();

        $indicators = [
            ['code' => 'U5MR', 'name' => 'Mortalite des moins de 5 ans', 'unit' => 'deces pour 1 000 naissances vivantes', 'frequency' => 'Annual', 'indicator_category_id' => $mortality?->id],
            ['code' => 'MMR', 'name' => 'Ratio de mortalite maternelle', 'unit' => 'deces pour 100 000 naissances vivantes', 'frequency' => 'Annual', 'indicator_category_id' => $mortality?->id],
            ['code' => 'DTP3', 'name' => 'Couverture vaccinale DTP3', 'unit' => '%', 'frequency' => 'Annual', 'indicator_category_id' => $coverage?->id],
            ['code' => 'LIFE_EXPECTANCY', 'name' => 'Esperance de vie a la naissance', 'unit' => 'annees', 'frequency' => 'Annual', 'indicator_category_id' => $mortality?->id],
            ['code' => 'MALARIA_INCIDENCE', 'name' => 'Incidence du paludisme', 'unit' => 'cas pour 1 000 habitants a risque', 'frequency' => 'Annual', 'indicator_category_id' => $disease?->id],
        ];

        foreach ($indicators as $indicator) {
            Indicator::updateOrCreate(['code' => $indicator['code']], $indicator + ['is_active' => true]);
        }
    }
}
