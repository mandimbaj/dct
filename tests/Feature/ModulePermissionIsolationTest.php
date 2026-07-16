<?php

namespace Tests\Feature;

use App\Filament\Clusters\HealthWorkforce;
use App\Filament\Clusters\NationalObservatory;
use App\Filament\Resources\Countries\CountryResource;
use App\Filament\Resources\HealthWorkforceKnowledgeProducts\HealthWorkforceKnowledgeProductResource;
use App\Filament\Resources\HealthWorkforceResourceCategories\HealthWorkforceResourceCategoryResource;
use App\Filament\Resources\HealthWorkforceResourceTypes\HealthWorkforceResourceTypeResource;
use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Filament\Resources\NationalObservatories\NationalObservatoryResource;
use App\Filament\Resources\ResourceCategories\ResourceCategoryResource;
use App\Filament\Resources\ResourceTypes\ResourceTypeResource;
use App\Models\Role;
use App\Models\User;
use App\Support\UserPermissions;
use Tests\TestCase;

class ModulePermissionIsolationTest extends TestCase
{
    public function test_publication_and_country_permissions_do_not_open_other_modules(): void
    {
        $publicationResources = [
            KnowledgeProductResource::class,
            ResourceTypeResource::class,
            ResourceCategoryResource::class,
        ];
        $viewResources = [...$publicationResources, CountryResource::class];
        $permissions = array_fill_keys(UserPermissions::actions(), []);

        foreach ($viewResources as $resource) {
            $permissions[UserPermissions::ACTION_VIEW][] = $this->keyForResource($resource);
        }

        foreach ($publicationResources as $resource) {
            foreach ([
                UserPermissions::ACTION_CREATE,
                UserPermissions::ACTION_UPDATE,
                UserPermissions::ACTION_DELETE,
            ] as $action) {
                $permissions[$action][] = $this->keyForResource($resource);
            }
        }

        $role = new Role(['menu_permissions' => $permissions]);
        $user = new User(['is_super_admin' => false]);
        $user->setRelation('role', $role);
        $this->actingAs($user);

        $this->assertTrue(KnowledgeProductResource::canAccess());
        $this->assertTrue(CountryResource::canAccess());
        $this->assertFalse(HealthWorkforceKnowledgeProductResource::canAccess());
        $this->assertFalse(HealthWorkforceResourceTypeResource::canAccess());
        $this->assertFalse(HealthWorkforceResourceCategoryResource::canAccess());
        $this->assertFalse(NationalObservatoryResource::canAccess());
        $this->assertFalse(HealthWorkforce::shouldRegisterNavigation());
        $this->assertFalse(NationalObservatory::shouldRegisterNavigation());

        $this->assertTrue(KnowledgeProductResource::canCreate());
        $this->assertFalse(HealthWorkforceKnowledgeProductResource::canCreate());
        $this->assertFalse(NationalObservatoryResource::canCreate());
    }

    /**
     * @param  class-string  $resource
     */
    private function keyForResource(string $resource): string
    {
        $key = collect(UserPermissions::definitions())
            ->search(fn (array $definition): bool => ($definition['class'] ?? null) === $resource);

        $this->assertIsString($key, "Missing permission definition for {$resource}");

        return $key;
    }
}
