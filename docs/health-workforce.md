# Module Health workforce

Ce document decrit le menu **Health workforce** dans l'application Laravel/Filament. Il sert de reference pour retrouver rapidement l'origine Django, les tables warehouse et les ressources Filament associees.

## Objectif fonctionnel

Le module Health workforce regroupe les donnees liees au personnel de sante :

- valeurs d'indicateurs du personnel de sante ;
- cadres de sante ;
- institutions de formation ;
- types d'institutions ;
- programmes de formation ;
- ressources et guides dedies au personnel de sante ;
- evenements recurrents, notamment Nursing and midwifery ;
- annonces liees a ces evenements.

Le menu est divise en deux groupes :

| Groupe | Usage |
|---|---|
| Data | Donnees operationnelles ou contenus publies |
| References | Tables de reference et nomenclatures |

## Mapping depuis l'application Django

L'ancienne application Django exposait le module dans `health_workforce/admin.py` et `health_workforce/models.py`.

| Django admin/model | Laravel/Filament | Table warehouse | Statut |
|---|---|---|---|
| `StgHealthWorkforceFacts` | `HealthWorkforceValueResource` | `fact_health_workforce` | Existant |
| `StgHealthCadre` | `HealthCadreResource` | `stg_health_cadre` | Existant |
| `StgTrainingInstitution` | `TrainingInstitutionResource` | `stg_traininginstitution` | Existant, enrichi |
| `StgInstitutionType` | `InstitutionTypeResource` | `stg_institution_type` | Ajoute |
| `StgInstitutionProgrammes` | `InstitutionProgrammeResource` | `stg_institution_programme` | Ajoute |
| `StgRecurringEvent` | `RecurringEventResource` | `stg_recurring_event` | Ajoute |
| `StgAnnouncements` | `EventAnnouncementResource` | `stg_event_announcement` | Ajoute |
| `HumanWorkforceResourceProxy` | `HealthWorkforceKnowledgeProductResource` | `stg_knowledge_product` | Ajoute |
| `ResourceTypeProxy` | `HealthWorkforceResourceTypeResource` | `stg_resource_type` | Ajoute |
| `ResourceCategoryProxy` | `HealthWorkforceResourceCategoryResource` | `stg_resource_category` | Ajoute |

## Sous-menus Filament

| Groupe | Sous-menu | Slug | Ressource |
|---|---|---|---|
| Data | Workforce values | `values` | `app/Filament/Resources/HealthWorkforceValues/HealthWorkforceValueResource.php` |
| Data | Resources / guides | `resources-guides` | `app/Filament/Resources/HealthWorkforceKnowledgeProducts/HealthWorkforceKnowledgeProductResource.php` |
| Data | Nursing and midwifery | `nursing-midwifery` | `app/Filament/Resources/RecurringEvents/RecurringEventResource.php` |
| Data | Announcements | `announcements` | `app/Filament/Resources/EventAnnouncements/EventAnnouncementResource.php` |
| References | Health cadres | `cadres` | `app/Filament/Resources/HealthCadres/HealthCadreResource.php` |
| References | Training institutions | `training-institutions` | `app/Filament/Resources/TrainingInstitutions/TrainingInstitutionResource.php` |
| References | Institution types | `institution-types` | `app/Filament/Resources/InstitutionTypes/InstitutionTypeResource.php` |
| References | Training programmes | `training-programmes` | `app/Filament/Resources/InstitutionProgrammes/InstitutionProgrammeResource.php` |
| References | Resource types | `resource-types` | `app/Filament/Resources/HealthWorkforceResourceTypes/HealthWorkforceResourceTypeResource.php` |
| References | Resource categories | `resource-categories` | `app/Filament/Resources/HealthWorkforceResourceCategories/HealthWorkforceResourceCategoryResource.php` |

Pour verifier les routes :

```bash
php artisan route:list --path=health-workforce
```

## Tables et relations

### Types d'institutions

| Element | Valeur |
|---|---|
| Modele | `App\Models\InstitutionType` |
| Table principale | `stg_institution_type` |
| Cle primaire | `type_id` |
| Table de traduction | `stg_institution_type_translation` |
| Relation principale | `trainingInstitutions()` vers `stg_traininginstitution.type_id` |

### Programmes de formation

| Element | Valeur |
|---|---|
| Modele | `App\Models\InstitutionProgramme` |
| Table principale | `stg_institution_programme` |
| Cle primaire | `course_id` |
| Table de traduction | `stg_institution_programme_translation` |
| Pivot | `stg_institution_programs_lookup` |
| Relation principale | `institutions()` vers `TrainingInstitution` |

Le pivot utilise les colonnes Django historiques :

| Colonne pivot | Pointe vers |
|---|---|
| `stgtraininginstitution_id` | `stg_traininginstitution.institution_id` |
| `stginstitutionprogrammes_id` | `stg_institution_programme.course_id` |

### Evenements recurrents

| Element | Valeur |
|---|---|
| Modele | `App\Models\RecurringEvent` |
| Table principale | `stg_recurring_event` |
| Cle primaire | `event_id` |
| Table de traduction | `stg_recurring_event_translation` |
| Pivot cadres | `stg_recurring_event_lookup` |
| Relation pays | `location_id` vers `countries.location_id` |

Dans l'interface, ces enregistrements sont exposes sous le nom **Nursing and midwifery**, conformement au libelle Django.

### Annonces

| Element | Valeur |
|---|---|
| Modele | `App\Models\EventAnnouncement` |
| Table principale | `stg_event_announcement` |
| Cle primaire | `event_id` |
| Table de traduction | `stg_event_announcement_translation` |
| Relation pays | `location_id` vers `countries.location_id` |

### Ressources et guides workforce

Les ressources Health workforce reutilisent les modeles existants de publication :

| Sous-menu | Modele | Filtre |
|---|---|---|
| Resources / guides | `KnowledgeProduct` | `category.category = 2` |
| Resource types | `ResourceType` | au moins une categorie avec `category = 2` |
| Resource categories | `ResourceCategory` | `category = 2` |

La valeur `category = 2` vient de l'ancienne application Django et identifie le perimetre Health workforce.

## Traductions et affichage

Les tables warehouse utilisent des tables de traduction separees. Les modeles qui ont un champ `display_name`, `display_shortname`, `display_theme` ou `display_message` utilisent le trait :

```php
App\Models\Concerns\HasPreferredTranslationName
```

Ce trait cherche la meilleure traduction selon la langue active, puis retombe sur le code ou la cle primaire si aucune traduction n'est disponible.

Les labels d'interface sont dans :

```text
lang/en/aho.php
lang/fr/aho.php
lang/pt/aho.php
```

Quand un sous-menu est ajoute, il faut ajouter les trois traductions pour :

- `navigation` ;
- `model` ;
- `plural`.

## Permissions

Les nouveaux sous-menus doivent rester visibles aux roles pays qui avaient deja acces aux ressources Health workforce historiques.

Le trait suivant permet cette compatibilite :

```php
App\Filament\Resources\Concerns\UsesFallbackResourcePermission
```

Principe :

1. Si l'utilisateur est super administrateur, l'acces est autorise.
2. Sinon, la ressource verifie sa propre permission.
3. Si la permission directe n'existe pas encore, elle accepte une permission parente definie par `fallbackPermissionResources()`.

Exemples :

| Nouvelle ressource | Permission fallback |
|---|---|
| `InstitutionTypeResource` | `HealthCadreResource`, `TrainingInstitutionResource` |
| `InstitutionProgrammeResource` | `HealthCadreResource`, `TrainingInstitutionResource` |
| `RecurringEventResource` | `HealthWorkforceValueResource` |
| `EventAnnouncementResource` | `HealthWorkforceValueResource` |
| `HealthWorkforceKnowledgeProductResource` | `KnowledgeProductResource` |
| `HealthWorkforceResourceTypeResource` | `ResourceTypeResource` |
| `HealthWorkforceResourceCategoryResource` | `ResourceCategoryResource` |

`UserPermissions::allowsModel()` accepte aussi plusieurs cles de ressource pour un meme modele. C'est necessaire parce que `KnowledgeProduct`, `ResourceType` et `ResourceCategory` existent a la fois dans les menus globaux et dans Health workforce.

## Conventions pour ajouter un sous-menu similaire

1. Creer le modele Eloquent dans `app/Models`.
2. Declarer la connexion `warehouse`, la table, la cle primaire et les colonnes timestamps.
3. Ajouter le modele de traduction si la table `*_translation` existe.
4. Ajouter les relations utiles : `translations`, `location`, pivots, enfants.
5. Creer une ressource Filament dans `app/Filament/Resources`.
6. Assigner la ressource au cluster `HealthWorkforce`.
7. Definir le groupe `Data` ou `References`, le slug et le tri.
8. Ajouter `fallbackPermissionResources()` si les roles existants doivent voir le nouveau menu sans reconfiguration.
9. Ajouter les traductions `en`, `fr`, `pt`.
10. Verifier les routes et ouvrir la page dans un contexte pays.

## Verification rapide

```bash
php artisan route:list --path=health-workforce
php artisan test
```

Verifier ensuite dans le navigateur :

```text
http://127.0.0.1:8002/admin/mw/health-workforce
```

Les pages ajoutees doivent s'ouvrir sans erreur et respecter le filtre pays lorsque la ressource contient `location_id`.
