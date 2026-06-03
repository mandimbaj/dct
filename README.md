# Health Indicators Africa - AHO Data Capture Tool

Application Laravel/Filament pour la saisie, la validation, l'administration et la consultation des donnees de sante de la Region africaine de l'OMS.

Le panneau d'administration est multi-pays et multilingue. Chaque utilisateur travaille dans le contexte de son pays, tandis que les super administrateurs utilisent le contexte regional africain.

## Acces rapide

```bash
composer install
npm install
php artisan migrate
npm run build
php artisan serve --host=127.0.0.1 --port=8002
```

URL locale habituelle :

```text
http://127.0.0.1:8002/admin/{country}
```

Exemples :

```text
http://127.0.0.1:8002/admin/mw
http://127.0.0.1:8002/admin/sn
http://127.0.0.1:8002/admin/af
```

`{country}` correspond au code ISO-2 en minuscules pour un pays. Le code `af` represente la Region africaine pour le niveau global.

## Stack technique

| Couche | Technologie |
|---|---|
| Backend | Laravel 13 |
| Administration | Filament 5 |
| Reactivite | Livewire |
| Base de donnees | Connexion principale + connexion `warehouse` |
| PHP | 8.3+ |
| Frontend | Vite, CSS/JS statiques personnalises |

## Structure importante

| Chemin | Role |
|---|---|
| `app/Filament/Clusters` | Menus principaux du panneau admin |
| `app/Filament/Resources` | CRUD Filament et sous-menus |
| `app/Models` | Modeles Eloquent, souvent branches sur la base `warehouse` |
| `app/Support` | Services transverses : permissions, pays, filtres, export, dashboard |
| `lang/en`, `lang/fr`, `lang/pt` | Traductions de l'interface |
| `public/css/who-afro-filament.css` | Styles personnalises du panneau Filament |
| `public/js/aho-sidebar-tooltips.js` | Comportements JS de navigation |
| `resources/views/filament` | Vues Blade injectees dans Filament |
| `docs` | Documentation technique et maintenance |

## Documentation

La documentation principale se trouve dans `docs/maintenance.md`.

Documents utiles :

- `docs/maintenance.md` : guide general de maintenance, interface, langues, droits et deploiement.
- `docs/modules.md` : cartographie des modules Filament hors Health workforce.
- `docs/health-workforce.md` : mapping Django, tables de base de donnees et ressources Filament du menu Health workforce.

## Module Health workforce

Le menu Health workforce combine les anciennes donnees Django et les tables `warehouse`.

Sous-menus actuels :

| Groupe | Sous-menu | Source principale |
|---|---|---|
| Data | Workforce values | `fact_health_workforce` |
| Data | Resources / guides | `stg_knowledge_product` filtre categorie workforce |
| Data | Nursing and midwifery | `stg_recurring_event` |
| Data | Announcements | `stg_event_announcement` |
| References | Health cadres | `stg_health_cadre` |
| References | Training institutions | `stg_traininginstitution` |
| References | Institution types | `stg_institution_type` |
| References | Training programmes | `stg_institution_programme` |
| References | Resource types | `stg_resource_type` filtre workforce |
| References | Resource categories | `stg_resource_category` filtre `category = 2` |

Voir `docs/health-workforce.md` pour les relations, permissions et conventions de code.

## Commandes de verification

```bash
php artisan route:list --path=health-workforce
php artisan test
php -l app/Support/UserPermissions.php
```

Pour vider les caches pendant le developpement :

```bash
php artisan optimize:clear
```

## Notes pour les prochains developpeurs

- Les ressources Filament utilisent les labels traduits dans `lang/{locale}/aho.php`.
- Les donnees multilingues du warehouse passent souvent par une table `*_translation`.
- Le trait `HasPreferredTranslationName` choisit la meilleure traduction selon la langue active.
- Les ressources Health workforce filtrees sur les publications utilisent `category = 2`, valeur heritee de l'ancienne application Django.
- Quand plusieurs ressources Filament representent le meme modele, `UserPermissions::allowsModel()` accepte toutes les cles correspondantes afin de ne pas casser les ressources globales.
