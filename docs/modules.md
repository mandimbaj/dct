# Cartographie des modules applicatifs

Ce document complete `docs/health-workforce.md`. Il donne une vue de maintenance des autres modules Filament : role fonctionnel, sous-menus, ressources principales et points d'attention.

Pour chaque module, le cluster se trouve dans :

```text
app/Filament/Clusters
```

Les ecrans CRUD se trouvent dans :

```text
app/Filament/Resources
```

## Vue d'ensemble

| Module | Slug | Role |
|---|---|---|
| Indicators | `indicators` | Indicateurs, valeurs, archives, imports, exports et references |
| Facilities | `facilities` | Structures sanitaires et mesures de services de facility |
| Health services | `health-services` | Valeurs de services de sante |
| Data elements | `data-elements` | Elements de donnees, groupes et valeurs de bas niveau |
| Publications | `publications` | Produits de connaissance et references de publication |
| Locations | `regions` | Pays, niveaux administratifs et localites |
| Data integration | `data-integration` | Connexions externes et mapping de champs |
| Data quality | `data-quality` | Tables de controle qualite et resolution d'anomalies |
| API tokens | `api-tokens` | Creation, affichage et revocation de jetons API |
| Authentication | `authentication` | Utilisateurs, roles, permissions et historique |
| UHC clock | `uhc-clock` | Themes, groupes, indicateurs et valeurs prioritaires UHC |
| Health workforce | `health-workforce` | Documente separement dans `docs/health-workforce.md` |

## Indicators

Cluster : `App\Filament\Clusters\Indicators`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Indicator values | `values` | `HealthIndicatorValueResource` |
| Data | Archives | `archives` | `HealthIndicatorArchiveResource` |
| Data wizard | Imports | `imports` | `ImportRecordResource` |
| Data wizard | Exports | `exports` | `ExportRecordResource` |
| References | Definitions | `definitions` | `IndicatorResource` |
| References | Domains | `domains` | `IndicatorDomainResource` |
| References | References | `references` | `IndicatorReferenceResource` |
| References | Periods | `periods` | `TimePeriodResource` |
| References | Measure methods | `measure-methods` | `MeasureMethodResource` |
| References | Disaggregations | `disaggregations` | `IndicatorCategoryResource` |
| References | Sources | `sources` | `DataSourceResource` |

Points de maintenance :

- `HealthIndicatorValueResource` est la table principale de saisie et d'approbation.
- Les valeurs sont country-scoped avec `UserCountryAccess`.
- La recherche globale des valeurs utilise `FilamentSearch` pour chercher dans les traductions et references liees.
- Les imports/exports sont regroupes sous Data wizard.

## Facilities

Cluster : `App\Filament\Clusters\Facilities`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Facilities | `facilities` | `HealthFacilityResource` |
| Data | Service availability | `service-availability` | `ServiceAvailabilityResource` |
| Data | Service capacity | `service-capacity` | `ServiceCapacityResource` |
| Data | Service readiness | `service-readiness` | `ServiceReadinessResource` |
| References | Owners | `owners` | `FacilityOwnerResource` |
| References | Types | `types` | `FacilityTypeResource` |
| References | Service domains | `service-domains` | `ServiceDomainResource` |
| References | Service areas | `service-areas` | `ServiceAreaResource` |
| References | Service interventions | `service-interventions` | `ServiceInterventionResource` |
| References | Provision units | `provision-units` | `ProvisionUnitResource` |

Points de maintenance :

- Les ressources Data representent les enregistrements operationnels.
- Les ressources References decrivent la taxonomie des services.
- Les tables factuelles doivent rester filtrees par pays pour les utilisateurs pays.

## Health services

Cluster : `App\Filament\Clusters\HealthServices`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Values | `values` | `HealthServiceValueResource` |

Points de maintenance :

- Le module est volontairement etroit.
- Les valeurs reutilisent les references communes : indicateur, source, methode, categorie et location.
- La recherche table doit rester large pour retrouver une valeur par periode, indicateur, source, pays ou valeur numerique.

## Data elements

Cluster : `App\Filament\Clusters\DataElements`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Values | `values` | `DataElementValueResource` |
| References | Definitions | `definitions` | `DataElementResource` |
| References | Groups | `groups` | `DataElementGroupResource` |

Points de maintenance :

- Les data elements sont des intrants warehouse plus bas niveau que les indicateurs.
- Les definitions et groupes doivent rester separes des definitions d'indicateurs.
- Les valeurs sont country-scoped et utilisent les memes references de source et de disaggregation.

## Publications

Cluster : `App\Filament\Clusters\Publications`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Products | `products` | `KnowledgeProductResource` |
| References | Domains | `domains` | `PublicationDomainResource` |
| References | Types | `types` | `ResourceTypeResource` |
| References | Categories | `categories` | `ResourceCategoryResource` |

Points de maintenance :

- `KnowledgeProductResource` est la vue globale non filtree.
- Health workforce et d'autres domaines peuvent exposer des vues filtrees de ces memes modeles.
- Ne pas ajouter de filtre Health workforce dans le module Publications global.
- Les actions d'approbation sont gerees par `ApprovalWorkflow` et `UserPermissions`.

## Locations

Cluster : `App\Filament\Clusters\Regions`

Le libelle utilisateur est **Locations**. Le nom de classe `Regions` reste pour compatibilite avec la structure initiale.

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| References | Locations | `locations` | `CountryResource` |
| References | Level 2 locations | `level-2-locations` | `LevelTwoLocationResource` |
| References | Levels | `levels` | `LocationLevelResource` |

Points de maintenance :

- Le modele `Country` pointe sur `stg_location`.
- Les pays correspondent aux locations de niveau 2.
- Les utilisateurs pays ne doivent voir que leur pays et son arbre de descendants.
- L'URL regionale globale utilise `/admin/af`.

## Data integration

Cluster : `App\Filament\Clusters\DataIntegration`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Sources | Connections | `connections` | `DataIntegrationConnectionResource` |
| Sources | Field mapping | `field-mappings` | `DataIntegrationFieldMappingResource` |

Points de maintenance :

- Les connexions definissent les fournisseurs externes et leurs parametres.
- La page `field-mapping` relie les champs entrants aux concepts warehouse.
- Les mappings doivent etre testes avec des donnees representatives avant activation.

## Data quality

Cluster : `App\Filament\Clusters\DataQuality`

| Sous-menu | Ressource |
|---|---|
| Facts dataset | `DqaFactsDatasetResource` |
| Facts filter | `DqaFactsFilterResource` |
| Missing values | `DqaMissingValueResource` |
| Multiple measures | `DqaMultipleMeasureResource` |
| Internal consistencies | `DqaInternalConsistencyResource` |
| External consistencies | `DqaExternalConsistencyResource` |
| Value type consistencies | `DqaValueTypeConsistencyResource` |
| Check categories | `DqaCheckCategoryResource` |
| Check measures | `DqaCheckMeasureResource` |
| Check periods | `DqaCheckPeriodResource` |
| Check sources | `DqaCheckSourceResource` |
| Valid category options | `DqaValidCategoryOptionResource` |
| Valid data sources | `DqaValidDataSourceResource` |
| Valid measure types | `DqaValidMeasureTypeResource` |
| Failed import rows | `FailedImportRowResource` |

Points de maintenance :

- Les ressources DQA exposent des vues de controle et de resolution.
- `App\Support\DataQuality` contient la logique partagee de colonnes, actions et resolution.
- Les actions de correction doivent rester limitees par permissions et contexte pays.

## API tokens

Cluster : `App\Filament\Clusters\ApiTokens`

| Page | Slug | Classe |
|---|---|---|
| Token status | `status` | `ApiTokenStatus` |

Points de maintenance :

- Les tokens sont geres par une page, pas par un CRUD complet.
- Le token en clair n'est affiche qu'une seule fois apres creation.
- Les droits passent par `UserPermissions::allowsPage()`.

## Authentication

Cluster : `App\Filament\Clusters\Authentication`

| Sous-menu | Slug | Ressource |
|---|---|---|
| Users | `users` | `UserResource` |
| Roles | `roles` | `RoleResource` |
| Permissions | `permissions` | `PermissionResource` |
| User history | `user-history` | `UserPageVisitResource` |

Points de maintenance :

- Les roles et utilisateurs portent des permissions menu/action.
- Les super admins ignorent la matrice de permissions.
- Les utilisateurs pays sont limites par `RedirectAuthenticatedToCountry` et `UserCountryAccess`.

## UHC clock

Cluster : `App\Filament\Clusters\UhcClock`

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Priority indicators | `priority-indicators` | `UhcPriorityIndicatorResource` |
| References | Themes | `themes` | `UhcClockThemeResource` |
| References | Groups | `groups` | `UhcClockGroupResource` |
| References | Indicators | `indicators` | `UhcClockIndicatorResource` |

Points de maintenance :

- Les references decrivent la structure du clock.
- Les valeurs prioritaires alimentent les experiences de consultation UHC.
- Garder les groupes, themes et indicateurs coherents avant d'ajouter des valeurs.

## Verification rapide

```bash
php artisan route:list
php artisan test
```

Pour verifier un module precis :

```bash
php artisan route:list --path=indicators
php artisan route:list --path=facilities
php artisan route:list --path=data-quality
```
