<?php

namespace Tests\Unit;

use App\Filament\Clusters\ApiTokens;
use App\Filament\Clusters\Authentication;
use App\Filament\Clusters\DataElements;
use App\Filament\Clusters\DataIntegration;
use App\Filament\Clusters\DataQuality;
use App\Filament\Clusters\Facilities;
use App\Filament\Clusters\HealthServices;
use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Clusters\Indicators;
use App\Filament\Clusters\NationalObservatory;
use App\Filament\Clusters\Publications;
use App\Filament\Clusters\Regions;
use App\Filament\Clusters\UhcClock;
use App\Filament\Resources\CustomIcons\CustomIconResource;
use App\Support\AhoIcon;
use Illuminate\Contracts\Support\Htmlable;
use Tests\TestCase;

class AhoIconTest extends TestCase
{
    public function test_main_modules_use_the_code_owned_icon_registry(): void
    {
        $clusters = [
            ApiTokens::class,
            Authentication::class,
            DataElements::class,
            DataIntegration::class,
            DataQuality::class,
            Facilities::class,
            HealthServices::class,
            HealthWorkforce::class,
            Indicators::class,
            NationalObservatory::class,
            Publications::class,
            Regions::class,
            UhcClock::class,
        ];

        foreach ($clusters as $cluster) {
            $icon = $cluster::getNavigationIcon();

            $this->assertInstanceOf(Htmlable::class, $icon);
            $this->assertStringContainsString('aho-custom-icon', $icon->toHtml());
            $this->assertStringContainsString('fa-solid', $icon->toHtml());
        }
    }

    public function test_icon_catalogue_and_warehouse_resource_menu_are_both_available(): void
    {
        $this->assertSame('fa-solid fa-hospital', AhoIcon::all()[AhoIcon::FACILITIES]);
        $this->assertTrue(CustomIconResource::shouldRegisterNavigation());
    }
}
