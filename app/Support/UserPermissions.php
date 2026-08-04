<?php

namespace App\Support;

use App\Filament\Clusters\ApiTokens\Pages\ApiTokenStatus;
use App\Filament\Clusters\DataQuality\Pages\IndicatorQualityChecks;
use App\Filament\Clusters\UhcClock\Pages\UhcClockProgress;
use App\Filament\Resources\DataElementValues\DataElementValueResource;
use App\Filament\Resources\HealthIndicatorValues\HealthIndicatorValueResource;
use App\Filament\Resources\KnowledgeProducts\KnowledgeProductResource;
use App\Filament\Resources\UserPageVisits\UserPageVisitResource;
use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UserPermissions
{
    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    private static array $definitionsCache = [];

    /**
     * @var array<string, array<string, string>>
     */
    private static array $optionsCache = [];

    public const ACTION_VIEW = 'view';

    public const ACTION_CREATE = 'create';

    public const ACTION_UPDATE = 'update';

    public const ACTION_DELETE = 'delete';

    public const ACTION_IMPORT = 'import';

    public const ACTION_APPROVE = 'approve';

    public const FORM_FIELDS = [
        'permission_view' => self::ACTION_VIEW,
        'permission_create' => self::ACTION_CREATE,
        'permission_update' => self::ACTION_UPDATE,
        'permission_delete' => self::ACTION_DELETE,
        'permission_import' => self::ACTION_IMPORT,
        'permission_approve' => self::ACTION_APPROVE,
    ];

    private const EXCLUDED_RESOURCES = [
        UserPageVisitResource::class,
        'App\\Filament\\Resources\\Permissions\\PermissionResource',
    ];

    private const PAGE_CLASSES = [
        ApiTokenStatus::class,
        IndicatorQualityChecks::class,
        UhcClockProgress::class,
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $locale = app()->getLocale();

        return self::$definitionsCache[$locale] ??= Cache::remember('user-permissions.definitions.'.$locale, now()->addMinutes(10), function (): array {
            $definitions = [
                ...static::resourceDefinitions(),
                ...static::pageDefinitions(),
            ];

            uasort(
                $definitions,
                fn (array $first, array $second): int => [$first['menu_sort'], $first['sort'], $first['label']]
                    <=> [$second['menu_sort'], $second['sort'], $second['label']]
            );

            return $definitions;
        });
    }

    /**
     * @return array<int, string>
     */
    public static function actions(): array
    {
        return [
            static::ACTION_VIEW,
            static::ACTION_CREATE,
            static::ACTION_UPDATE,
            static::ACTION_DELETE,
            static::ACTION_IMPORT,
            static::ACTION_APPROVE,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function optionsForAction(string $action): array
    {
        $cacheKey = app()->getLocale().'.'.$action;

        return self::$optionsCache[$cacheKey] ??= collect(static::definitions())
            ->filter(fn (array $definition): bool => in_array($action, $definition['actions'], true))
            ->mapWithKeys(fn (array $definition, string $key): array => [
                $key => "{$definition['menu_label']} / {$definition['label']}",
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function assignableOptionsForAction(string $action, ?User $actor = null): array
    {
        $options = static::optionsForAction($action);
        $actor ??= auth()->user();

        if (! $actor || $actor->is_super_admin) {
            return $options;
        }

        $permissions = static::permissionsFor($actor);
        $allowedKeys = array_flip($permissions[$action] ?? []);

        return array_intersect_key($options, $allowedKeys);
    }

    /**
     * @return array<int, Component>
     */
    public static function formFields(?User $actor = null): array
    {
        return [
            Tabs::make(__('aho.permissions.matrix'))
                ->tabs([
                    Tab::make(__('aho.permissions.tabs.visibility'))
                        ->icon('heroicon-o-eye')
                        ->schema([
                            CheckboxList::make('permission_view')
                                ->label(__('aho.permissions.view'))
                                ->helperText(__('aho.permissions.view_help'))
                                ->options(fn (): array => static::assignableOptionsForAction(static::ACTION_VIEW, $actor))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                        ]),
                    Tab::make(__('aho.permissions.tabs.contribution'))
                        ->icon('heroicon-o-pencil-square')
                        ->schema([
                            CheckboxList::make('permission_create')
                                ->label(__('aho.permissions.create'))
                                ->options(fn (): array => static::assignableOptionsForAction(static::ACTION_CREATE, $actor))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                            CheckboxList::make('permission_import')
                                ->label(__('aho.permissions.import'))
                                ->helperText(__('aho.permissions.import_help'))
                                ->options(fn (): array => static::assignableOptionsForAction(static::ACTION_IMPORT, $actor))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                            CheckboxList::make('permission_approve')
                                ->label(__('aho.permissions.approve'))
                                ->helperText(__('aho.permissions.approve_help'))
                                ->options(fn (): array => static::assignableOptionsForAction(static::ACTION_APPROVE, $actor))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                        ]),
                    Tab::make(__('aho.permissions.tabs.administration'))
                        ->icon('heroicon-o-shield-check')
                        ->schema([
                            CheckboxList::make('permission_update')
                                ->label(__('aho.permissions.update'))
                                ->options(fn (): array => static::assignableOptionsForAction(static::ACTION_UPDATE, $actor))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                            CheckboxList::make('permission_delete')
                                ->label(__('aho.permissions.delete'))
                                ->options(fn (): array => static::assignableOptionsForAction(static::ACTION_DELETE, $actor))
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(2),
                        ]),
                ])
                ->persistTabInQueryString('permissions_tab')
                ->columnSpanFull(),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function formState(User $user): array
    {
        return static::formStateFromPermissions(static::permissionsFor($user));
    }

    /**
     * @param  array<string, mixed>  $permissions
     * @return array<string, array<int, string>>
     */
    public static function formStateFromPermissions(array $permissions): array
    {
        $permissions = static::normalize($permissions);

        return [
            'permission_view' => $permissions[static::ACTION_VIEW],
            'permission_create' => $permissions[static::ACTION_CREATE],
            'permission_update' => $permissions[static::ACTION_UPDATE],
            'permission_delete' => $permissions[static::ACTION_DELETE],
            'permission_import' => $permissions[static::ACTION_IMPORT],
            'permission_approve' => $permissions[static::ACTION_APPROVE],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string>>
     */
    public static function extractFormState(array $data, ?User $actor = null): array
    {
        $permissions = [];

        foreach (static::FORM_FIELDS as $field => $action) {
            $permissions[$action] = array_values(array_filter((array) ($data[$field] ?? [])));
        }

        return static::restrictToAssignable(static::normalize($permissions), $actor ?? auth()->user());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function removeFormState(array $data): array
    {
        foreach (array_keys(static::FORM_FIELDS) as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * @param  array<string, array<int, string>>  $permissions
     */
    public static function sync(User $user, array $permissions): void
    {
        $user->forceFill([
            'menu_permissions' => $user->is_super_admin ? null : static::normalize($permissions),
        ])->saveQuietly();
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function permissionsFor(User $user): array
    {
        if ($user->is_super_admin) {
            return static::allPermissions();
        }

        $rolePermissions = $user->relationLoaded('role')
            ? $user->role?->menu_permissions
            : (filled($user->role_id) ? $user->role()->first(['id', 'menu_permissions'])?->menu_permissions : null);

        return static::normalize($rolePermissions ?: ($user->menu_permissions ?? []));
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function allPermissions(): array
    {
        $all = [];

        foreach (static::actions() as $action) {
            $all[$action] = array_keys(static::optionsForAction($action));
        }

        return static::normalize($all);
    }

    public static function allowsModel(User $user, string|Model|null $model, string $action): ?bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($model === null) {
            return null;
        }

        // Some menus intentionally reuse the same model with different filters.
        // Accept any matching resource key so a scoped submenu does not break the global one.
        $keys = static::keysForModel($model);

        if ($keys === []) {
            return null;
        }

        foreach ($keys as $key) {
            if (static::allowsKey($user, $key, $action)) {
                return true;
            }
        }

        return false;
    }

    public static function allowsResource(User $user, string $resourceClass, string $action): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return static::allowsKey($user, static::keyForClass($resourceClass, 'resource'), $action);
    }

    public static function allowsPage(User $user, string $pageClass): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return static::allowsKey($user, static::keyForClass($pageClass, 'page'), static::ACTION_VIEW);
    }

    public static function actionForAbility(string $ability): ?string
    {
        return match ($ability) {
            'viewAny', 'view' => static::ACTION_VIEW,
            'create' => static::ACTION_CREATE,
            'update' => static::ACTION_UPDATE,
            'delete', 'deleteAny' => static::ACTION_DELETE,
            default => null,
        };
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function normalize(array $permissions): array
    {
        $normalized = [];

        foreach (static::actions() as $action) {
            $validKeys = array_keys(static::optionsForAction($action));
            $normalized[$action] = array_values(array_unique(array_intersect((array) ($permissions[$action] ?? []), $validKeys)));
        }

        $normalized[static::ACTION_VIEW] = array_values(array_unique([
            ...$normalized[static::ACTION_VIEW],
            ...$normalized[static::ACTION_CREATE],
            ...$normalized[static::ACTION_UPDATE],
            ...$normalized[static::ACTION_DELETE],
            ...$normalized[static::ACTION_IMPORT],
            ...$normalized[static::ACTION_APPROVE],
        ]));

        return $normalized;
    }

    /**
     * @param  array<string, array<int, string>>  $permissions
     * @return array<string, array<int, string>>
     */
    public static function restrictToAssignable(array $permissions, ?User $actor = null): array
    {
        $permissions = static::normalize($permissions);

        if (! $actor || $actor->is_super_admin) {
            return $permissions;
        }

        $actorPermissions = static::permissionsFor($actor);

        foreach (static::actions() as $action) {
            $permissions[$action] = array_values(array_intersect(
                $permissions[$action] ?? [],
                $actorPermissions[$action] ?? [],
            ));
        }

        $permissions[static::ACTION_VIEW] = array_values(array_unique([
            ...$permissions[static::ACTION_VIEW],
            ...$permissions[static::ACTION_CREATE],
            ...$permissions[static::ACTION_UPDATE],
            ...$permissions[static::ACTION_DELETE],
            ...$permissions[static::ACTION_IMPORT],
            ...$permissions[static::ACTION_APPROVE],
        ]));

        $permissions[static::ACTION_VIEW] = array_values(array_intersect(
            $permissions[static::ACTION_VIEW],
            $actorPermissions[static::ACTION_VIEW] ?? [],
        ));

        return $permissions;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function resourceDefinitions(): array
    {
        $resources = [];

        foreach (static::resourceClasses() as $resourceClass) {
            $clusterClass = $resourceClass::getCluster();
            $modelClass = $resourceClass::getModel();
            $key = static::keyForClass($resourceClass, 'resource');

            $resources[$key] = [
                'type' => 'resource',
                'class' => $resourceClass,
                'model' => $modelClass,
                'label' => $resourceClass::getNavigationLabel(),
                'menu_label' => static::menuLabel($clusterClass, $resourceClass::getNavigationGroup()),
                'menu_sort' => static::menuSort($clusterClass),
                'sort' => $resourceClass::getNavigationSort() ?? 100,
                'actions' => [
                    static::ACTION_VIEW,
                    static::ACTION_CREATE,
                    static::ACTION_UPDATE,
                    static::ACTION_DELETE,
                    ...(in_array($resourceClass, [HealthIndicatorValueResource::class], true) ? [static::ACTION_IMPORT] : []),
                    ...(in_array($resourceClass, [DataElementValueResource::class, HealthIndicatorValueResource::class, KnowledgeProductResource::class], true) ? [static::ACTION_APPROVE] : []),
                ],
            ];
        }

        return $resources;
    }

    /**
     * @return array<int, class-string>
     */
    private static function resourceClasses(): array
    {
        return collect(File::glob(app_path('Filament/Resources/*/*Resource.php')))
            ->map(function (string $path): string {
                $directory = basename(dirname($path));
                $class = basename($path, '.php');

                return "App\\Filament\\Resources\\{$directory}\\{$class}";
            })
            ->filter(fn (string $class): bool => class_exists($class))
            ->reject(fn (string $class): bool => in_array($class, static::EXCLUDED_RESOURCES, true))
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function pageDefinitions(): array
    {
        $pages = [];

        foreach (static::PAGE_CLASSES as $pageClass) {
            $clusterClass = $pageClass::getCluster();
            $key = static::keyForClass($pageClass, 'page');

            $pages[$key] = [
                'type' => 'page',
                'class' => $pageClass,
                'label' => $pageClass::getNavigationLabel(),
                'menu_label' => static::menuLabel($clusterClass),
                'menu_sort' => static::menuSort($clusterClass),
                'sort' => $pageClass::getNavigationSort() ?? 100,
                'actions' => [static::ACTION_VIEW],
            ];
        }

        return $pages;
    }

    private static function menuLabel(?string $clusterClass, mixed $navigationGroup = null): string
    {
        if ($clusterClass !== null) {
            return $clusterClass::getNavigationLabel();
        }

        return filled($navigationGroup) ? (string) $navigationGroup : __('aho.permissions.other_menu');
    }

    private static function menuSort(?string $clusterClass): int
    {
        if ($clusterClass === null) {
            return 100;
        }

        return $clusterClass::getNavigationSort() ?? 100;
    }

    private static function allowsKey(User $user, string $key, string $action): bool
    {
        $permissions = static::permissionsFor($user);

        return in_array($key, $permissions[static::ACTION_VIEW], true)
            && in_array($key, $permissions[$action] ?? [], true);
    }

    /**
     * Return every permission key backed by the same Eloquent model.
     *
     * This matters for proxy-style resources such as Health workforce publications, resource
     * types and resource categories, which share models with the global Publications menu.
     *
     * @return array<int, string>
     */
    private static function keysForModel(string|Model $model): array
    {
        $modelClass = is_string($model) ? $model : $model::class;
        $keys = [];

        foreach (static::resourceDefinitions() as $key => $definition) {
            if (($definition['model'] ?? null) === $modelClass) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    private static function keyForClass(string $class, string $prefix): string
    {
        $baseName = class_basename($class);

        if ($prefix === 'resource') {
            $baseName = Str::beforeLast($baseName, 'Resource');
        }

        return $prefix.':'.Str::of($baseName)->kebab()->toString();
    }
}
