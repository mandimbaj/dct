# Documentation de Maintenance — Outil de Saisie de Données AHO

> **Projet :** Health Indicators Africa (iAHO Data Entry Tool)
> **Framework :** Laravel 11 · Filament v3 · Livewire 3
> **Langues supportées :** Anglais · Français · Portugais
> **Maintenu par :** Bureau régional de l'OMS pour l'Afrique

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Stack technique](#2-stack-technique)
3. [Structure du projet](#3-structure-du-projet)
4. [Personnalisation de l'interface](#4-personnalisation-de-linterface)
5. [Gestion des cartes pays](#5-gestion-des-cartes-pays)
6. [Système de langues](#6-système-de-langues)
7. [Gestion des utilisateurs et accès](#7-gestion-des-utilisateurs-et-accès)
8. [Middleware et sécurité](#8-middleware-et-sécurité)
9. [Tâches de maintenance courantes](#9-tâches-de-maintenance-courantes)
10. [Déploiement et mise à jour des assets](#10-déploiement-et-mise-à-jour-des-assets)
11. [Scripts utilitaires](#11-scripts-utilitaires)
12. [Référence des classes Support](#12-référence-des-classes-support)

---

## 1. Vue d'ensemble

L'application est un panneau d'administration multi-pays, multi-langue permettant la saisie, la validation et la consultation des indicateurs de santé des 47 États membres de la région africaine de l'OMS.

**Accès :** `/admin/{country}` où `{country}` est le code ISO-2 en minuscules (ex : `ng`, `ke`, `dz`) ou `global` pour les super administrateurs.

**Rôles principaux :**
| Rôle | Périmètre |
|---|---|
| Super administrateur | Accès à tous les pays, toutes les données |
| Administrateur pays | Accès limité à son pays assigné |
| Utilisateur | Accès en lecture seule ou saisie partielle selon les permissions |

---

## 2. Stack technique

| Composant | Technologie | Version |
|---|---|---|
| Backend | Laravel | 11.x |
| Admin UI | Filament | v3 |
| Réactivité | Livewire | 3.x |
| Base de données | MySQL / MariaDB | — |
| PHP | PHP | 8.3+ |
| CSS personnalisé | Fichier statique compilé | `who-afro-filament.css` |
| JS personnalisé | Vanilla JS (IIFE) | `aho-sidebar-tooltips.js` |
| Serveur de développement | Laragon | Windows |

---

## 3. Structure du projet

```
health-indicators-africa/
├── app/
│   ├── Filament/
│   │   ├── Clusters/          # 12 groupes de navigation (Indicators, Facilities, etc.)
│   │   ├── Resources/         # 61 ressources CRUD (HealthIndicatorValues, Users, etc.)
│   │   └── Widgets/           # Widgets du tableau de bord
│   ├── Http/
│   │   └── Middleware/        # 5 middlewares personnalisés (voir §8)
│   ├── Models/                # Modèles Eloquent (User, Country, Indicator, etc.)
│   ├── Providers/
│   │   └── Filament/
│   │       └── AdminPanelProvider.php   # ⭐ Configuration centrale du panneau
│   └── Support/               # 23 classes utilitaires (voir §12)
├── docs/
│   └── maintenance.md         # Ce fichier
├── lang/
│   ├── en/aho.php             # Traductions anglaises
│   ├── fr/aho.php             # Traductions françaises
│   └── pt/aho.php             # Traductions portugaises
├── public/
│   ├── css/
│   │   └── who-afro-filament.css   # ⭐ CSS personnalisé principal
│   ├── images/
│   │   ├── country-maps/      # 47 fichiers SVG (un par pays AFRO, nommés par ISO-2)
│   │   ├── africa-map.svg     # Carte de l'Afrique entière (super admin)
│   │   ├── aho-logo-combined-en.png
│   │   ├── aho-logo-combined-fr.png
│   │   └── aho-logo-combined-pt.png
│   └── js/
│       └── aho-sidebar-tooltips.js  # Tooltips dynamiques pour la sidebar
├── resources/views/filament/  # 8 vues Blade personnalisées (voir §4)
└── scripts/
    └── generate-africa-svg.php  # Script one-shot de génération de la carte Afrique
```

---

## 4. Personnalisation de l'interface

### 4.1 Fichier CSS principal

**Fichier :** `public/css/who-afro-filament.css`

Ce fichier surcharge le CSS par défaut de Filament. Il est chargé via un render hook dans `AdminPanelProvider.php` avec un paramètre de cache-busting :

```php
// app/Providers/Filament/AdminPanelProvider.php
'<link rel="stylesheet" href="'.asset('css/who-afro-filament.css').'?v=20260526-8">'
```

> **Important :** à chaque modification de ce fichier en production, il faut **incrémenter le numéro de version** (`?v=AAAAMMJJ-N`) pour forcer les navigateurs à recharger le fichier et éviter les problèmes de cache.

**Convention de versionnage :** `?v=YYYYMMDD-N` — date du jour + numéro séquentiel.

### 4.2 Variables CSS personnalisées

Les couleurs et valeurs globales sont définies dans `:root` en haut du fichier CSS :

```css
:root {
    --who-afro-blue:       #0093D5;  /* Bleu principal OMS */
    --who-afro-blue-dark:  #004B87;  /* Bleu foncé OMS */
    --who-afro-ink:        #1e293b;  /* Texte sombre */
    --who-afro-panel-border: #e2e8f0; /* Bordures */
    --who-afro-body:       #f8fafc;  /* Fond de page */
}
```

Pour modifier la palette de couleurs, changer ces variables — les changements se propagent partout.

### 4.3 Vues Blade personnalisées

Toutes se trouvent dans `resources/views/filament/` :

| Fichier | Render Hook | Description |
|---|---|---|
| `topbar-brand.blade.php` | `TOPBAR_START` | Logo + nom de l'app dans la barre du haut |
| `topbar-actions.blade.php` | `TOPBAR_END` | Notifications + sélecteur de langue (droite) |
| `topbar-notifications.blade.php` | _(inclus dans actions)_ | Dropdowns messages et alertes système |
| `language-switcher.blade.php` | `SIMPLE_PAGE_START` + inclus | Boutons EN / FR / PT |
| `country-context-card.blade.php` | `SIDEBAR_FOOTER` | Carte pays en bas de la sidebar |
| `footer.blade.php` | `FOOTER` | Pied de page du panneau |
| `navigation-assistant.blade.php` | `BODY_END` | Assistant de navigation (JS) |

### 4.4 Carte pays dans la sidebar (`country-context-card`)

La carte en bas de la sidebar affiche des informations différentes selon le type d'utilisateur :

**Super administrateur :**
- Affiche la carte SVG de l'Afrique entière (`public/images/africa-map.svg`)
- Générée depuis le TopoJSON `Africa_adm0.json` (voir §5.2)
- Libellé : « Région africaine »

**Administrateur pays / utilisateur :**
- Affiche la carte SVG du pays (depuis `public/images/country-maps/{ISO2}.svg`)
- Affiche le drapeau du pays (source : `flagcdn.com`)
- Libellé : nom du pays dans la langue active

**Classes impliquées :**
- `App\Support\CountryContext` — construit le payload de la carte
- `App\Support\CountryMapData` — charge et transforme le SVG du pays
- Cache : `country-context-card.{country_id}.{locale}` (12 heures)

**Classes CSS principales :**
```
.aho-country-card          → conteneur de la carte
.aho-country-card__visual  → zone image (carte SVG + drapeau)
.aho-country-card__svg     → conteneur du SVG ou img
.aho-country-card__flag    → drapeau (paysage : 3rem × 2rem)
.aho-country-card__name    → nom du pays
```

### 4.5 JavaScript — Tooltips sidebar

**Fichier :** `public/js/aho-sidebar-tooltips.js`

Ajoute dynamiquement des attributs `title` et `aria-label` sur les boutons de la sidebar pour afficher les labels au survol. Se recharge automatiquement après chaque navigation Livewire.

Chargé via render hook avec versionnage :
```php
'<script defer src="'.asset('js/aho-sidebar-tooltips.js').'?v=20260512-3"></script>'
```

---

## 5. Gestion des cartes pays

### 5.1 Cartes des pays membres (47 SVG)

**Emplacement :** `public/images/country-maps/`
**Format :** SVG ammap (format amCharts Maps)
**Nommage :** `{ISO2}.svg` en majuscules (ex : `NG.svg`, `KE.svg`, `DZ.svg`)

**Liste des pays couverts :**
AO, BF, BI, BJ, BW, CF, CG, CI, CM, CV, CD, DZ, ER, ET, GA, GH, GM, GN, GQ, GW, KE, KM, LR, LS, MG, ML, MR, MU, MW, MZ, NA, NE, NG, RW, SC, SL, SN, SS, ST, SZ, TD, TG, TZ, UG, ZA, ZM, ZW

**Ajouter un nouveau SVG pays :**
1. Obtenir le fichier SVG au format ammap (source officielle : amCharts Maps)
2. Nommer le fichier `{ISO2}.svg` (code ISO 3166-1 alpha-2, majuscules)
3. Copier dans `public/images/country-maps/`
4. Vider le cache : `php artisan cache:clear`

**Traitement automatique à l'affichage (via `CountryMapData::buildSvgHtml()`) :**
- Suppression du BOM UTF-8 si présent
- Calcul automatique du `viewBox` à partir des coordonnées des paths
- Application de la couleur bleue OMS (`#0093D5`) en remplacement du gris ammap
- Nettoyage des déclarations XML et namespaces amCharts
- Adoucissement des traits de séparation internes

### 5.2 Carte de l'Afrique (super administrateur)

**Fichier statique :** `public/images/africa-map.svg`

Ce fichier est pré-généré à partir du TopoJSON `Africa_adm0.json` (47 pays, projection géographique simple). Il **ne se régénère pas automatiquement** — c'est un fichier statique.

**Pour régénérer la carte :**
1. S'assurer que le fichier source TopoJSON est disponible :
   `C:/Users/mandimbaj/OneDrive - World Health Organization/AHO/Formation Power BI/Africa_adm0.json`
2. Exécuter le script :
   ```bash
   php scripts/generate-africa-svg.php
   ```
3. Le fichier `public/images/africa-map.svg` est mis à jour.

> **Note :** Ce script est indépendant de l'application Laravel. Il n'a pas besoin d'être exécuté régulièrement sauf si les frontières ou la couleur doivent changer.

**Changer la couleur de la carte Afrique :**
Modifier la variable `$color` dans `scripts/generate-africa-svg.php` puis relancer le script.

---

## 6. Système de langues

### 6.1 Langues supportées

| Code | Langue | Fichier |
|---|---|---|
| `en` | Anglais | `lang/en/aho.php` |
| `fr` | Français | `lang/fr/aho.php` |
| `pt` | Portugais | `lang/pt/aho.php` |

La classe centrale est `App\Support\WarehouseLocale`.

### 6.2 Ajouter ou modifier une traduction

Toutes les chaînes de l'interface personnalisée se trouvent dans `lang/{locale}/aho.php`.

**Structure du fichier :**
```php
return [
    'brand'             => [...],   // Nom de l'app, alt du logo
    'country_context'   => [...],   // Carte pays sidebar
    'notifications'     => [...],   // Messages et alertes système
    'table_exports'     => [...],   // Boutons d'export
    'navigation'        => [...],   // Labels de navigation
    // ...
];
```

**Ajouter une nouvelle clé :**
1. Ajouter la clé dans les trois fichiers (`en`, `fr`, `pt`)
2. L'appeler dans le code avec `__('aho.ma_section.ma_cle')`

### 6.3 Logos multilingues

`App\Support\AhoBrand` retourne le bon logo selon la langue active :

| Locale | Fichier logo |
|---|---|
| `fr` | `public/images/aho-logo-combined-fr.png` |
| `pt` | `public/images/aho-logo-combined-pt.png` |
| `en` (défaut) | `public/images/aho-logo-combined-en.png` |

Pour remplacer un logo : copier le nouveau fichier au même emplacement avec le même nom.

### 6.4 Commutation de langue

Le middleware `SetLocale` lit la langue depuis (par ordre de priorité) :
1. Cookie `locale`
2. Session `locale`
3. Configuration Laravel (`config/app.php` → `locale`)

La route `GET /locale/{locale}` permet à l'utilisateur de changer de langue depuis l'interface.

---

## 7. Gestion des utilisateurs et accès

### 7.1 Modèle utilisateur (`App\Models\User`)

| Attribut | Type | Description |
|---|---|---|
| `name` | string | Nom complet |
| `email` | string | Email (identifiant de connexion) |
| `is_super_admin` | boolean | Accès à tous les pays |
| `is_country_admin` | boolean | Administrateur d'un pays |
| `location_id` | FK | Pays assigné (table `countries`) |
| `role_id` | FK | Rôle assigné |
| `menu_permissions` | JSON | Permissions de menu personnalisées |

### 7.2 Logique d'accès par pays

Le middleware `RedirectAuthenticatedToCountry` redirige automatiquement les utilisateurs vers leur espace pays. Un utilisateur assigné à `NG` (Nigeria) ne peut pas accéder à `/admin/gh/...` (Ghana).

**Règles :**
- Si `is_super_admin = true` → accès à tous les pays + section `/admin/global`
- Sinon → redirection forcée vers `/admin/{iso_du_pays_assigné}/...`

### 7.3 Créer un super administrateur

```bash
php artisan tinker
```
```php
$user = App\Models\User::where('email', 'email@example.com')->first();
$user->is_super_admin = true;
$user->save();
```

### 7.4 Changer le pays d'un utilisateur

```php
$user = App\Models\User::find($id);
$country = App\Models\Country::where('iso_alpha', 'NG')->first();
$user->location_id = $country->id;
$user->save();
```

---

## 8. Middleware et sécurité

### 8.1 Middlewares enregistrés (dans l'ordre d'exécution)

| Middleware | Rôle |
|---|---|
| `EncryptCookies` | Chiffrement des cookies |
| `AddQueuedCookiesToResponse` | Cookies en file d'attente |
| `StartSession` | Démarrage de la session |
| `SetLocale` | Définit la langue et le contexte pays |
| `RedirectAuthenticatedToCountry` | Enforce l'accès au bon pays |
| `AuthenticateSession` | Vérifie l'authenticité de la session |
| `ShareErrorsFromSession` | Partage les erreurs de validation |
| `PreventRequestForgery` | Protection CSRF |
| `SubstituteBindings` | Résolution des paramètres de route |
| `DisableBladeIconComponents` | Performance Filament |
| `DispatchServingFilamentEvent` | Events Filament |
| `SecurityHeaders` | En-têtes de sécurité HTTP |

### 8.2 En-têtes de sécurité HTTP (`SecurityHeaders`)

Appliqués à toutes les réponses :

| En-tête | Valeur |
|---|---|
| `X-Frame-Options` | `DENY` |
| `X-Content-Type-Options` | `nosniff` |
| `Content-Security-Policy` | `frame-ancestors 'none'; base-uri 'self'; object-src 'none'` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | Désactive caméra, micro, géoloc, paiement, USB |
| `Cross-Origin-Opener-Policy` | `same-origin` |
| `Cross-Origin-Resource-Policy` | `same-origin` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS uniquement) |

---

## 9. Tâches de maintenance courantes

### 9.1 Vider les caches

```bash
# Cache applicatif (données mises en cache, ex : cartes pays)
php artisan cache:clear

# Cache de configuration
php artisan config:clear

# Cache des vues Blade
php artisan view:clear

# Cache des routes
php artisan route:clear

# Tout nettoyer d'un coup
php artisan optimize:clear
```

### 9.2 Régénérer le cache en production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 9.3 Forcer le rechargement du CSS côté client

Incrémenter le numéro de version dans `app/Providers/Filament/AdminPanelProvider.php` :

```php
// Avant
'?v=20260526-8'

// Après (exemple)
'?v=20260527-1'
```

Puis vider le cache des vues : `php artisan view:clear`

### 9.4 Vider le cache d'une carte pays spécifique

Le cache est stocké sous la clé `country-context-card.{country_id}.{locale}` (TTL 12h).

Pour forcer le rechargement immédiat, vider tout le cache :
```bash
php artisan cache:clear
```

### 9.5 Consulter les logs

```bash
tail -f storage/logs/laravel.log
```

### 9.6 Mettre à jour les dépendances

```bash
# PHP
composer update

# JavaScript
npm update
npm run build
```

> **Attention :** Tester sur un environnement de développement avant toute mise à jour en production.

---

## 10. Déploiement et mise à jour des assets

### 10.1 Workflow de déploiement (manuel)

```bash
# 1. Récupérer les changements
git pull origin main

# 2. Mettre à jour les dépendances
composer install --no-dev --optimize-autoloader

# 3. Exécuter les migrations
php artisan migrate --force

# 4. Regénérer les caches
php artisan optimize

# 5. Vider le cache de l'application
php artisan cache:clear
```

### 10.2 Modifier le CSS personnalisé

1. Éditer `public/css/who-afro-filament.css`
2. Incrémenter la version dans `AdminPanelProvider.php` (voir §9.3)
3. Vider le cache des vues : `php artisan view:clear`
4. Vérifier dans le navigateur (Ctrl+Shift+R pour forcer le rechargement)

### 10.3 Fichiers à ne jamais supprimer

| Fichier/Dossier | Raison |
|---|---|
| `public/css/who-afro-filament.css` | CSS principal de toute l'interface |
| `public/js/aho-sidebar-tooltips.js` | Tooltips d'accessibilité sidebar |
| `public/images/country-maps/*.svg` | Cartes de tous les pays (47 fichiers) |
| `public/images/africa-map.svg` | Carte Afrique (super admin) |
| `public/images/aho-logo-combined-*.png` | Logos multilingues |
| `app/Support/` | Classes utilitaires critiques |
| `lang/en/aho.php`, `fr`, `pt` | Toutes les traductions |

---

## 11. Scripts utilitaires

### `scripts/generate-africa-svg.php`

**Usage :** Génère `public/images/africa-map.svg` à partir du fichier TopoJSON source.

```bash
php scripts/generate-africa-svg.php
```

**Source requise :**
```
C:/Users/mandimbaj/OneDrive - World Health Organization/AHO/Formation Power BI/Africa_adm0.json
```

**Quand exécuter :**
- Première installation sur un nouveau serveur (si `africa-map.svg` n'est pas présent)
- Modification de la couleur ou du style de la carte Afrique

**Paramètre modifiable :**
```php
$color = '#0093D5'; // Couleur de remplissage (bleu OMS par défaut)
```

---

## 12. Référence des classes Support

Toutes dans le namespace `App\Support`.

| Classe | Rôle |
|---|---|
| `AhoBrand` | Logos et nom de l'application selon la langue |
| `ApprovalWorkflow` | Logique du flux de validation des données |
| `CountryContext` | Construit le payload de la carte pays (sidebar) |
| `CountryFlagColors` | Couleurs des drapeaux des 47 pays AFRO |
| `CountryMapData` | Charge, transforme et retourne le SVG d'un pays |
| `CountryTableFilter` | Filtrage des tableaux par pays |
| `DashboardCache` | Gestion du cache du tableau de bord |
| `DashboardIndicatorValues` | Calcul des valeurs d'indicateurs pour le dashboard |
| `FilamentSearch` | Personnalisation de la recherche globale Filament |
| `GeneratedCode` | Génération de codes uniques |
| `MortalityIndicators` | Logique spécifique aux indicateurs de mortalité |
| `NotificationRecipients` | Destinataires des notifications selon le contexte |
| `ResourceTranslations` | Gestion des traductions des ressources Filament |
| `SelectOptions` | Options pour les champs Select dans les formulaires |
| `TableExportActions` | Actions d'export CSV/Excel dans les tableaux |
| `TextEncoding` | Utilitaires d'encodage de texte |
| `TopbarAlerts` | Alertes et notifications dans la barre du haut |
| `UserCountryAccess` | Contrôle d'accès par pays |
| `UserLocationAssignments` | Assignation de pays aux utilisateurs |
| `UserPermissions` | Calcul des permissions effectives d'un utilisateur |
| `WarehouseForm` | Formulaires spécifiques à l'entrepôt de données |
| `WarehouseLocale` | Gestion centralisée de la locale (en/fr/pt) |

---

## Annexe — Configuration du panneau Filament

**Fichier :** `app/Providers/Filament/AdminPanelProvider.php`

Paramètres clés :

```php
->path('admin/{country}')          // URL de base avec paramètre pays
->sidebarWidth('14rem')            // Largeur de la sidebar
->maxContentWidth(Width::Full)     // Contenu pleine largeur
->spa()                            // Mode SPA (navigation sans rechargement)
->globalSearch()                   // Recherche globale activée
->globalSearchDebounce('700ms')    // Délai de recherche
->colors(['primary' => Color::hex('#0093D5')])  // Couleur principale
```

**Render Hooks enregistrés :**

| Hook | Vue rendue |
|---|---|
| `HEAD_END` | CSS + JS personnalisés (avec versionnage) |
| `SIMPLE_PAGE_START` | Sélecteur de langue (page de login) |
| `TOPBAR_START` | Marque/logo personnalisé |
| `TOPBAR_END` | Actions topbar (notifications, langue) |
| `SIDEBAR_FOOTER` | Carte du contexte pays |
| `FOOTER` | Pied de page |
| `BODY_END` | Assistant de navigation |
