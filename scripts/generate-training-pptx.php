<?php

/**
 * Generates a training PowerPoint (.pptx) for the AHO Data Capture Tool
 * with real screenshots of each menu and submenu, grouped by module.
 */

date_default_timezone_set('UTC');
$outFile = getenv('DCT_TRAINING_PPTX') ?: __DIR__ . '/../docs/formation-data-capture-tool.pptx';
$imgDir  = __DIR__ . '/../docs/screenshots-jpg/';

$WHO_BLUE  = '0093D5';
$WHO_DARK  = '1F4E79';
$WHO_LIGHT = 'E6F4FB';
$GREY      = '595959';
$WHITE     = 'FFFFFF';
$INKGREY   = '404040';

// =============================================================
// SLIDE DEFINITIONS
// =============================================================
// layout: title | agenda | menu | content | module(title, shots, notes)
// shots: array of [img-filename-no-ext, caption]

$slides = [];

$slides[] = ['layout' => 'title', 'title' => 'Data Capture Tool',
    'subtitle' => "Outil de saisie des données de santé\nAfrique – Saisie, validation, administration et consultation",
    'footer' => 'OMS / Bureau Régional pour l’Afrique – Integrated African Health Observatory (iAHO)'];

$slides[] = ['layout' => 'agenda', 'title' => 'Programme de la formation', 'items' => [
    'Présentation et connexion',
    'Le tableau de bord',
    'Navigation : les 12 menus',
    'Indicateurs & données',
    'UHC Clock (Couverture Santé Universelle)',
    'Établissements & Ressources humaines',
    'Services de santé & Éléments de données',
    'Publications & Localisations',
    'Intégration, Qualité & API',
    'Utilisateurs & bonnes pratiques',
]];

$slides[] = ['layout' => 'content', 'title' => '1. Présentation & connexion', 'items' => [
    'Portail de saisie des données de santé de la Région africaine de l’OMS (plateforme iAHO).',
    'Multi-pays : chaque utilisateur travaille dans le contexte de son pays (/admin/{code-pays}).',
    'Multilingue : anglais, français, portugais (sélecteur de langue).',
    'Connexion via compte Microsoft de l’OMS (SSO) ou local e-mail/mot de passe.',
    'Un compte super admin régional voit la Région africaine (code « af ») et tous les pays.',
    'Chaque saisie est tracée et passe par un workflow d’approbation.',
]];

$slides[] = ['layout' => 'module', 'title' => '2. Tableau de bord', 'shots' => [
    ['00-dashboard', 'Vue d’ensemble : cartes statut (approuvé / en attente / rejeté), graphiques et tableaux des dernières saisies.'],
]];

$slides[] = ['layout' => 'menu', 'title' => '3. Les 12 menus principaux', 'cols' => [
    ['01  Indicateurs', '02  UHC Clock', '03  Établissements', '04  Health workforce'],
    ['05  Services de santé', '06  Éléments de données', '07  Publications', '08  Localisations'],
    ['09  Intégration de données', '10  Qualité des données', '11  Jetons API', '12  Authentification'],
]];

$slides[] = ['layout' => 'module', 'title' => '4. Indicateurs – définitions', 'shots' => [
    ['01-indicators-definitions', 'Définitions des indicateurs : métadonnées, codes AFRO, catégories et périodes.'],
    ['01-indicators-sources', 'Sources de données : liste des sources utilisées pour alimenter les valeurs.'],
    ['01-indicators-measure-methods', 'Méthodes de mesure : unités et conventions de calcul.'],
    ['01-indicators-references', 'Références : références documentaires des indicateurs.'],
]];

$slides[] = ['layout' => 'module', 'title' => '4. Indicateurs – valeurs', 'shots' => [
    ['01-indicators-values', 'Valeurs d’indicateurs : saisie, filtres par pays/source/statut, actions d’approbation.'],
]];

$slides[] = ['layout' => 'content', 'title' => '4. Saisie & import (workflow)', 'items' => [
    'Ajouter une valeur : Indicateurs > Valeurs, bouton « Add indicator value ».',
    'Renseigner indicateur, localisation (pays), période, catégorie, source, méthode.',
    'Valeur reçue OU texte, numérateur, dénominateur, min, max, cible, priorité.',
    'Enregistrement → statut « En attente d’approbation » (Pending).',
    'Import de masse : Indicateurs > Import (4 étapes) – fichier Excel .xlsx, correspondances, confirmation, chargement.',
    'Statuts : En attente, Approuvé, Rejeté – seuls les utilisateurs avec permission approuvent.',
]];

$slides[] = ['layout' => 'module', 'title' => '5. UHC Clock', 'shots' => [
    ['02-uhc-themes', 'Thèmes UHC : les grands thèmes de la Couverture Santé Universelle.'],
    ['02-uhc-groups', 'Groupes : organisation des indicateurs par groupe.'],
    ['02-uhc-indicators', 'Indicateurs UHC : indicateurs suivis pour l’horloge UHC.'],
    ['02-uhc-priority', 'Indicateurs prioritaires : valeurs par pays (horloge).'],
]];

$slides[] = ['layout' => 'module', 'title' => '5. UHC Clock – progression', 'shots' => [
    ['02-uhc-progress', 'Progression UHC : horloge à 4 niveaux – Jour (impact), Heure (suivi/finance), Minute (performance), Seconde (intrants).'],
]];

$slides[] = ['layout' => 'module', 'title' => '6. Établissements', 'shots' => [
    ['03-facilities-facilities', 'Établissements : données maîtres des formations sanitaires.'],
    ['03-facilities-types', 'Types d’établissements : typologie des formations.'],
]];

$slides[] = ['layout' => 'module', 'title' => '6. Health workforce (ressources humaines)', 'shots' => [
    ['04-workforce-values', 'Valeurs de main-d’œuvre : effectifs par cadre, période et source.'],
    ['04-workforce-cadres', 'Cadres de santé : définition des catégories de personnel.'],
    ['04-workforce-training', 'Institutions de formation : établissements de formation en santé.'],
]];

$slides[] = ['layout' => 'module', 'title' => '7. Services de santé', 'shots' => [
    ['05-services-values', 'Valeurs des services de santé : suivi des services (disponibilité, capacité, préparation).'],
]];

$slides[] = ['layout' => 'module', 'title' => '7. Éléments de données', 'shots' => [
    ['06-dataelements-definitions', 'Définitions des éléments de données : entrées bas niveau de l’entrepôt.'],
    ['06-dataelements-groups', 'Groupes d’éléments de données : regroupement logique.'],
]];

$slides[] = ['layout' => 'module', 'title' => '8. Publications', 'shots' => [
    ['07-publications-products', 'Produits de connaissance : titres, auteurs, résumés, fichiers/URL, workflow d’approbation.'],
    ['07-publications-types', 'Types de ressources : classification des publications.'],
    ['07-publications-categories', 'Catégories de ressources.'],
]];

$slides[] = ['layout' => 'module', 'title' => '8. Localisations', 'shots' => [
    ['08-regions-locations', 'Localisations : hiérarchie (région, pays, provinces, districts) et scoping des données.'],
    ['08-regions-levels', 'Niveaux de localisation (Location levels).'],
]];

$slides[] = ['layout' => 'module', 'title' => '9. Intégration de données', 'shots' => [
    ['09-dataintegration-connections', 'Connexions externes : DHIS2, DataBank, WHO DataHub, AHO warehouse, API REST, mappage des champs.'],
]];

$slides[] = ['layout' => 'content', 'title' => '9. Data Integration – procédure de A à Z', 'items' => [
    '1. Ouvrir Data Integration > Connections, puis cliquer sur Create / New connection.',
    '2. Décrire la source : nom, pays, fournisseur, méthode, statut et fréquence.',
    '3. Renseigner soit la connexion directe à la base, soit l’URL API et l’authentification.',
    '4. Enregistrer, ouvrir Mapping, charger les champs source et proposer les correspondances.',
    '5. Vérifier chaque ligne de mapping, enregistrer, puis lancer Validate configuration.',
    '6. Si tout est correct, synchroniser/importer ; les valeurs arrivent en Pending pour approbation.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Data Integration – champs de la connexion', 'items' => [
    'Name : nom lisible de la connexion, par exemple DHIS2 Kenya monthly import.',
    'Country : pays concerné ; pour un admin pays, il est automatiquement limité à son pays.',
    'Provider : type de source externe : DHIS2, DataBank data, WHO DataHub, AHO warehouse ou Other source.',
    'Integration method : Direct connection pour lire une base ; API pour lire un service web.',
    'Status : Draft pour préparer, Active pour utiliser, Paused pour suspendre, Error si la connexion échoue.',
    'Sync frequency : Manual, Hourly, Daily, Weekly ou Monthly selon le rythme prévu.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Connexion directe, API et sécurité', 'items' => [
    'Direct connection : Server name, Port, Database type, Database name et Source table / view indiquent où lire les données.',
    'Connection timeout : limite la durée d’essai de connexion, afin d’éviter de bloquer l’interface.',
    'SSL / TLS mode : Disabled, TLS required ou Verify server identity ; en production, privilégier la vérification du serveur.',
    'API URL : adresse du service distant, par exemple un endpoint DHIS2 ou une API nationale.',
    'Authentication : None, Bearer token, API key, Username/password ou OAuth2 client credentials.',
    'Les secrets (password, token, API key, client secret) sont masqués et enregistrés comme informations sensibles.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Mapping – rôle de chaque champ', 'items' => [
    'Local field : champ attendu dans le DCT Laravel, par exemple location_id, indicator_id, period ou value_received.',
    'External field(s) : champ venant de la source externe ; plusieurs champs peuvent être séparés par des virgules.',
    'Mapping type : façon dont la valeur externe est transformée avant d’alimenter le DCT.',
    'Reference matching : méthode de résolution pour les référentiels comme pays, indicateur, catégorie, source ou méthode.',
    'Required : rend la correspondance obligatoire avant validation de la configuration.',
    'Default value, Transformation rule et Notes : valeur de secours, règle métier et commentaire de documentation.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Mapping type – signification des valeurs', 'items' => [
    'Direct mapping : copie la valeur source vers le champ local ; utile pour année, période, valeur, numérateur, dénominateur.',
    'Reference lookup : cherche le code ou libellé source dans un référentiel local et conserve l’identifiant interne.',
    'Computed value : calcule une valeur locale à partir d’un ou plusieurs champs sources, par exemple une période ou un ratio.',
    'Conditional rule : applique une règle selon le contenu source, par exemple âge + sexe vers une seule catégorie locale.',
    'Skip : ignore volontairement un champ source ; utile pour documenter une colonne reçue mais non utilisée.',
    'Toujours vérifier les mappings proposés automatiquement avant d’enregistrer.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Reference matching – comment résoudre les référentiels', 'items' => [
    'Automatic : essaie d’abord un code stable, puis le libellé ; c’est l’option la plus sûre pour commencer.',
    'Stable code : utilise un code officiel comme ISO pays, AFRO code, code source ou code méthode.',
    'Name / label : utilise le nom affiché ; à éviter si plusieurs libellés peuvent se ressembler.',
    'Source identifier : utilise l’identifiant technique de la source externe lorsqu’il est stable et documenté.',
    'Les champs concernés sont location_id, indicator_id, categoryoption_id, datasource_id et measuremethod_id.',
    'Si aucune correspondance fiable n’est trouvée, la ligne doit être corrigée avant validation ou approbation.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Boutons et actions du module', 'items' => [
    'Mapping : ouvre la page de correspondance des champs pour la connexion sélectionnée.',
    'Load source fields : relit la source enregistrée et charge les colonnes/champs disponibles.',
    'Suggest mappings : propose des correspondances selon les noms de champs détectés.',
    'Add mapping : ajoute une ligne manuelle lorsque la proposition automatique ne suffit pas.',
    'Save mapping / Back to connections : enregistre la configuration ou revient à la liste des connexions.',
    'Validate configuration vérifie la connexion et le mapping ; Sync DHIS2 importe les données DHIS2 en Pending.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Après import : contrôle et approbation', 'items' => [
    'Les données importées ne doivent pas devenir automatiquement approuvées.',
    'Elles apparaissent dans Indicators > Values avec le statut Pending.',
    'L’administrateur vérifie le pays, l’indicateur, la période, la source, la méthode et la valeur.',
    'Si la donnée est correcte, il approuve ; si elle est incohérente, il corrige ou rejette.',
    'Ce workflow garde la traçabilité : source externe, date d’import, utilisateur et statut.',
    'La finalité du module est d’accélérer l’import sans perdre le contrôle qualité.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Data Integration – end-to-end procedure', 'items' => [
    '1. Open Data Integration > Connections, then create a new connection.',
    '2. Describe the source: name, country, provider, method, status and sync frequency.',
    '3. Enter either direct database settings or API URL and authentication details.',
    '4. Save, open Mapping, load source fields and generate suggested mappings.',
    '5. Review every mapping row, save it, then run Validate configuration.',
    '6. When valid, synchronize/import; imported values remain Pending until approval.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Data Integration – connection fields', 'items' => [
    'Name: readable connection label, for example DHIS2 Kenya monthly import.',
    'Country: target country; country administrators are automatically scoped to their country.',
    'Provider: external source type: DHIS2, DataBank data, WHO DataHub, AHO warehouse or Other source.',
    'Integration method: Direct connection reads a database; API reads a web service.',
    'Status: Draft for setup, Active for use, Paused to suspend, Error when the connection fails.',
    'Sync frequency: Manual, Hourly, Daily, Weekly or Monthly according to the expected rhythm.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Direct connection, API and security', 'items' => [
    'Direct connection: Server name, Port, Database type, Database name and Source table/view define where to read data.',
    'Connection timeout: limits connection attempts so the interface does not hang.',
    'SSL / TLS mode: Disabled, TLS required or Verify server identity; production should verify the server identity.',
    'API URL: remote service address, such as a DHIS2 endpoint or a national API.',
    'Authentication: None, Bearer token, API key, Username/password or OAuth2 client credentials.',
    'Passwords, tokens, API keys and client secrets are hidden and stored as sensitive information.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Mapping – field roles', 'items' => [
    'Local field: DCT Laravel target field, such as location_id, indicator_id, period or value_received.',
    'External field(s): source-side field; several source fields may be separated by commas.',
    'Mapping type: explains how the external value is transformed before feeding the DCT.',
    'Reference matching: matching method for countries, indicators, categories, sources and measure methods.',
    'Required: makes the mapping mandatory before configuration validation.',
    'Default value, Transformation rule and Notes: fallback value, business rule and documentation note.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Mapping type – meaning of each value', 'items' => [
    'Direct mapping: copies the source value to the local field; useful for year, period, value, numerator and denominator.',
    'Reference lookup: resolves a source code or label against a local reference table and stores the internal ID.',
    'Computed value: calculates a local value from one or more source fields, for example a period or ratio.',
    'Conditional rule: applies logic based on source content, for example age + sex into one local category option.',
    'Skip: intentionally ignores a source field while documenting that it was received but not used.',
    'Always review automatic mapping suggestions before saving them.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Reference matching – resolving reference data', 'items' => [
    'Automatic: tries a stable code first, then the label; it is the safest starting option.',
    'Stable code: uses an official code such as country ISO, AFRO code, source code or measure method code.',
    'Name / label: uses the displayed name; avoid it when several labels may be similar.',
    'Source identifier: uses the external technical identifier when it is stable and documented.',
    'Reference matching applies to location_id, indicator_id, categoryoption_id, datasource_id and measuremethod_id.',
    'Unmatched or ambiguous values must be corrected before validation or approval.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. Module buttons and workflow actions', 'items' => [
    'Mapping: opens the field mapping page for the selected connection.',
    'Load source fields: reads the saved source and loads available fields or columns.',
    'Suggest mappings: prepares automatic mappings from detected field names.',
    'Add mapping: adds a manual mapping row when automatic suggestions are not enough.',
    'Save mapping / Back to connections: saves the configuration or returns to the connection list.',
    'Validate configuration checks connection and mapping; Sync DHIS2 imports DHIS2 data as Pending.',
]];

$slides[] = ['layout' => 'content', 'title' => '9. After import: review and approval', 'items' => [
    'Imported values should not become automatically approved.',
    'They appear in Indicators > Values with the Pending status.',
    'The administrator checks country, indicator, period, source, method and value.',
    'If the value is correct, approve it; if inconsistent, correct it or reject it.',
    'The workflow preserves traceability: external source, import date, user and status.',
    'The module speeds up import while keeping data quality control in place.',
]];

$slides[] = ['layout' => 'module', 'title' => '10. Qualité des données', 'shots' => [
    ['10-dataquality-checks', 'Vérifications des indicateurs : balayage automatique (valeurs négatives, pourcentages > 100, indices hors plage…).'],
    ['10-dataquality-failed-imports', 'Lignes d’import en échec : supervision des erreurs de chargement.'],
]];

$slides[] = ['layout' => 'module', 'title' => '11. API & Jetons', 'shots' => [
    ['11-apitokens-status', 'Jetons API : création (préfixe dct_), expiration, révocation, statuts Actif/Expiré/Révoqué.'],
    ['11-apidocs', 'Documentation des endpoints V1 : lecture/écriture des valeurs d’indicateurs (Bearer token).'],
]];

$slides[] = ['layout' => 'module', 'title' => '12. Authentification / Utilisateurs', 'shots' => [
    ['12-authentication-users', 'Gestion des utilisateurs : pays, rôles, permissions, historique des visites, synchronisation Django.'],
]];

$slides[] = ['layout' => 'content', 'title' => 'Points clés & conclusion', 'items' => [
    'Contexte pays correct + données saisies en « En attente » puis approuvées après contrôle.',
    'La qualité des données commence à la saisie (valeurs plausibles, unités, années valides).',
    'Pour les gros volumes : import Excel ; l’assistant IA « AI » guide pas à pas.',
    'N’approuver qu’après contrôle ; tout est tracé (qui, quand).',
    'Exporter régulièrement ; utiliser l’API pour les intégrations automatisées.',
    'Merci de votre participation – Session pratique et questions ouvertes.',
]];

// =============================================================
// OOXML PART BUILDERS (mostly reused)
// =============================================================

function esc(string $s): string {
    return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function content_types_xml(int $slideCount, int $mediaCount): string {
    $slides = '';
    for ($i = 1; $i <= $slideCount; $i++) {
        $slides .= '<Override PartName="/ppt/slides/slide' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>';
    }
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
        '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
        '<Default Extension="xml" ContentType="application/xml"/>' .
        '<Default Extension="jpg" ContentType="image/jpeg"/>' .
        '<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>' .
        '<Override PartName="/ppt/presProps.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presProps+xml"/>' .
        '<Override PartName="/ppt/tableStyles.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.tableStyles+xml"/>' .
        '<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>' .
        '<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>' .
        '<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>' .
        '<Override PartName="/ppt/slideLayouts/slideLayout2.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>' .
        '<Override PartName="/ppt/slideLayouts/slideLayout3.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>' .
        '<Override PartName="/ppt/slideLayouts/slideLayout4.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>' .
        '<Override PartName="/ppt/slideLayouts/slideLayout5.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>' .
        '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>' .
        '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>' .
        $slides .
        '</Types>';
}

function rels_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/meta/core-properties" Target="docProps/core.xml"/>' .
        '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>' .
        '</Relationships>';
}

function core_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" ' .
        'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" ' .
        'xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' .
        '<dc:title>Formation Data Capture Tool</dc:title>' .
        '<dc:subject>Formation des utilisateurs</dc:subject>' .
        '<dc:creator>AHO - WHO Regional Office for Africa</dc:creator>' .
        '<cp:lastModifiedBy>opencode</cp:lastModifiedBy>' .
        '<dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:created>' .
        '<dcterms:modified xsi:type="dcterms:W3CDTF">' . gmdate('Y-m-d\TH:i:s\Z') . '</dcterms:modified>' .
        '</cp:coreProperties>';
}

function app_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" ' .
        'xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">' .
        '<Application>Microsoft Office PowerPoint</Application>' .
        '<PresentationFormat>Widescreen</PresentationFormat>' .
        '</Properties>';
}

function presentation_rels_xml(int $slideCount): string {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="presProps.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/tableStyles" Target="tableStyles.xml"/>' .
        '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>' .
        '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>';
    for ($i = 1; $i <= $slideCount; $i++) {
        $xml .= '<Relationship Id="rId' . (10 + $i) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide' . $i . '.xml"/>';
    }
    $xml .= '</Relationships>';
    return $xml;
}

function presentation_xml(int $slideCount): string {
    $slideIds = '';
    for ($i = 1; $i <= $slideCount; $i++) {
        $slideIds .= '<p:sldId id="' . (256 + $i) . '" r:id="rId' . (10 + $i) . '"/>';
    }
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
        'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' .
        '<p:sldMasterIdLst><p:sldMasterId id="2147483648" r:id="rId4"/></p:sldMasterIdLst>' .
        '<p:sldIdLst>' . $slideIds . '</p:sldIdLst>' .
        '<p:sldSz cx="12192000" cy="6858000" type="screen16x9"/>' .
        '<p:notesSz cx="6858000" cy="9144000"/>' .
        '<p:defaultTextStyle><a:defPPr><a:defRPr lang="fr-FR"/></a:defPPr></p:defaultTextStyle>' .
        '</p:presentation>';
}

function presprops_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:presentationPr xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
        'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"/>';
}

function table_styles_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<a:tblStyleLst xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
        'def="{5C22544A-7EE6-4342-B048-85BDC9FD1C3A}"/>';
}

function theme_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Office">' .
        '<a:themeElements>' .
        '<a:clrScheme name="Office">' .
        '<a:dk1><a:srgbClr val="1F4E79"/></a:dk1><a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>' .
        '<a:dk2><a:srgbClr val="0093D5"/></a:dk2><a:lt2><a:srgbClr val="E6F4FB"/></a:lt2>' .
        '<a:accent1><a:srgbClr val="0093D5"/></a:accent1><a:accent2><a:srgbClr val="1F4E79"/></a:accent2>' .
        '<a:accent3><a:srgbClr val="4CB749"/></a:accent3><a:accent4><a:srgbClr val="FFBE3D"/></a:accent4>' .
        '<a:accent5><a:srgbClr val="E5533C"/></a:accent5><a:accent6><a:srgbClr val="8E44AD"/></a:accent6>' .
        '<a:hlink><a:srgbClr val="0563C1"/></a:hlink><a:folHlink><a:srgbClr val="954F72"/></a:folHlink>' .
        '</a:clrScheme>' .
        '<a:fontScheme name="Office"><a:majorFont><a:latin typeface="Calibri Light"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>' .
        '<a:minorFont><a:latin typeface="Calibri"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont></a:fontScheme>' .
        '<a:fmtScheme name="Office">' .
        '<a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' .
        '<a:gradFill rotWithShape="1"><a:gsLst>' .
        '<a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="50000"/><a:satMod val="300000"/></a:schemeClr></a:gs>' .
        '<a:gs pos="35000"><a:schemeClr val="phClr"><a:tint val="37000"/><a:satMod val="300000"/></a:schemeClr></a:gs>' .
        '<a:gs pos="100000"><a:schemeClr val="phClr"><a:tint val="15000"/><a:satMod val="350000"/></a:schemeClr></a:gs>' .
        '</a:gsLst><a:lin ang="16200000" scaled="1"/></a:gradFill>' .
        '<a:gradFill rotWithShape="1"><a:gsLst>' .
        '<a:gs pos="0"><a:schemeClr val="phClr"><a:shade val="51000"/><a:satMod val="130000"/></a:schemeClr></a:gs>' .
        '<a:gs pos="80000"><a:schemeClr val="phClr"><a:shade val="93000"/><a:satMod val="130000"/></a:schemeClr></a:gs>' .
        '<a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="94000"/><a:satMod val="135000"/></a:schemeClr></a:gs>' .
        '</a:gsLst><a:lin ang="16200000" scaled="0"/></a:gradFill>' .
        '</a:fillStyleLst>' .
        '<a:lnStyleLst>' .
        '<a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"><a:shade val="95000"/><a:satMod val="105000"/></a:schemeClr></a:solidFill><a:prstDash val="solid"/></a:ln>' .
        '<a:ln w="25400" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>' .
        '<a:ln w="38100" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln>' .
        '</a:lnStyleLst>' .
        '<a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst>' .
        '<a:bgFillStyleLst>' .
        '<a:solidFill><a:schemeClr val="phClr"/></a:solidFill>' .
        '<a:solidFill><a:schemeClr val="phClr"><a:tint val="95000"/><a:satMod val="170000"/></a:schemeClr></a:solidFill>' .
        '<a:gradFill rotWithShape="1"><a:gsLst>' .
        '<a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="93000"/><a:satMod val="150000"/></a:schemeClr></a:gs>' .
        '<a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="98000"/><a:satMod val="130000"/></a:schemeClr></a:gs>' .
        '</a:gsLst><a:lin ang="16200000" scaled="0"/></a:gradFill>' .
        '</a:bgFillStyleLst>' .
        '</a:fmtScheme>' .
        '</a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>';
}

function slide_master_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
        'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' .
        '<p:cSld><p:bg><p:bgPr><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill><a:effectLst/></p:bgPr></p:bg>' .
        '<p:spTree>' .
        '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>' .
        '<p:sp><p:nvSpPr><p:cNvPr id="10" name="Title"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr><p:ph type="title"/></p:nvPr></p:nvSpPr>' .
        '<p:spPr/><p:txBody><a:bodyPr/><a:lstStyle><a:lvl1pPr><a:defRPr sz="4400" b="1"><a:solidFill><a:schemeClr val="tx1"/></a:solidFill></a:defRPr></a:lvl1pPr></a:lstStyle><a:p/></p:txBody></p:sp>' .
        '</p:spTree></p:cSld>' .
        '<p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>' .
        '<p:sldLayoutIdLst>' .
        '<p:sldLayoutId id="1" r:id="rId1"/><p:sldLayoutId id="2" r:id="rId2"/><p:sldLayoutId id="3" r:id="rId3"/>' .
        '<p:sldLayoutId id="4" r:id="rId4"/><p:sldLayoutId id="5" r:id="rId5"/>' .
        '</p:sldLayoutIdLst>' .
        '</p:sldMaster>';
}

function master_rels_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>' .
        '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout2.xml"/>' .
        '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout3.xml"/>' .
        '<Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout4.xml"/>' .
        '<Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout5.xml"/>' .
        '<Relationship Id="rId100" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>' .
        '</Relationships>';
}

function slide_layout_xml(string $name): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
        'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="obj" preserve="1">' .
        '<p:cSld name="' . esc($name) . '"><p:spTree>' .
        '<p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>' .
        '<p:sp><p:nvSpPr><p:cNvPr id="10" name="Title"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr><p:ph type="title"/></p:nvPr></p:nvSpPr>' .
        '<p:spPr/><p:txBody><a:bodyPr/><a:lstStyle/><a:p/></p:txBody></p:sp>' .
        '</p:spTree></p:cSld>' .
        '<p:clrMapOvr><a:overrideClrMapping bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/></p:clrMapOvr>' .
        '</p:sldLayout>';
}

function layout_rels_xml(): string {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
        '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>' .
        '</Relationships>';
}

// =============================================================
// SHAPE BUILDERS
// =============================================================

function rect(int $id, int $x, int $y, int $cx, int $cy, string $fill, bool $rounded = false): string {
    $prst = $rounded ? 'roundRect' : 'rect';
    $adj = $rounded ? '<a:avLst><a:gd name="adj" fmla="val 8000"/></a:avLst>' : '<a:avLst/>';
    return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="rect' . $id . '"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>' .
        '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>' .
        '<a:prstGeom prst="' . $prst . '">' . $adj . '</a:prstGeom><a:ln><a:noFill/></a:ln>' .
        '<a:solidFill><a:srgbClr val="' . $fill . '"/></a:solidFill></p:spPr></p:sp>';
}

function para_xml(array $para): string {
    [$text, $sz, $bold, $color, $align, $bullet] = $para;
    $spcAft = isset($para[6]) ? $para[6] : 900;
    $rpr = '<a:rPr lang="fr-FR" sz="' . ($sz * 100) . '" b="' . ($bold ? '1' : '0') . '">' .
        '<a:solidFill><a:srgbClr val="' . $color . '"/></a:solidFill><a:latin typeface="Calibri"/></a:rPr>';
    $run = '<a:r>' . $rpr . '<a:t>' . esc($text) . '</a:t></a:r>';
    $pPr = '<a:pPr algn="' . $align . '"';
    if ($bullet) $pPr .= ' marL="342900" indent="-228600"';
    $pPr .= '><a:spcAft spc="' . ($spcAft * 100) . '"/>';
    if ($bullet) $pPr .= '<a:buFont typeface="Arial"/><a:buChar char="•"/>';
    $pPr .= '</a:pPr>';
    return '<a:p>' . $pPr . $run . '</a:p>';
}

function textbox(int $id, int $x, int $y, int $cx, int $cy, array $paragraphs, string $wrap = 'square'): string {
    $body = '<a:bodyPr wrap="' . $wrap . '" lIns="91440" tIns="45720" rIns="91440" bIns="45720" anchor="t"><a:spAutoFit/></a:bodyPr>';
    $inner = '';
    foreach ($paragraphs as $para) $inner .= para_xml($para);
    return '<p:sp><p:nvSpPr><p:cNvPr id="' . $id . '" name="text' . $id . '"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>' .
        '<p:spPr><a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $cx . '" cy="' . $cy . '"/></a:xfrm>' .
        '<a:prstGeom prst="rect"><a:avLst/></a:prstGeom><a:ln><a:noFill/></a:ln><a:noFill/></p:spPr>' .
        '<p:txBody>' . $body . $inner . '</p:txBody></p:sp>';
}

function bullets(int $id, array $items, int $sz, string $color, int $top, int $left, int $width): string {
    $paras = [];
    foreach ($items as $it) $paras[] = [$it, $sz, false, $color, 'l', true];
    return textbox($id, $left, $top, $width, 4000000, $paras);
}

// Image shape: x,y is top-left; w,h is OUTER box; imgRatio = width/height of image
function pic(int $id, string $embedRid, int $x, int $y, int $w, int $h): string {
    $frame = '<a:xfrm><a:off x="' . $x . '" y="' . $y . '"/><a:ext cx="' . $w . '" cy="' . $h . '"/></a:xfrm>';
    return '<p:pic><p:nvPicPr><p:cNvPr id="' . $id . '" name="pic' . $id . '"/><p:cNvPicPr><a:picLocks noGrp="1" noChangeAspect="1"/></p:cNvPicPr><p:nvPr/></p:nvPicPr>' .
        $frame .
        '<p:blipFill><a:blip r:embed="' . $embedRid . '"/><a:stretch><a:fillRect/></a:stretch></p:blipFill>' .
        '<p:spPr><a:prstGeom prst="rect"><a:avLst/></a:prstGeom>' .
        '<a:ln w="19050"><a:solidFill><a:srgbClr val="CCCCCC"/></a:solidFill></a:ln></p:spPr></p:pic>';
}

// =============================================================
// SLIDE COMPOSERS
// =============================================================

function header_band(string $title): string {
    global $WHO_BLUE, $WHO_DARK;
    $out = rect(1, 0, 0, 12192000, 900000, $WHO_BLUE);
    $out .= rect(2, 0, 0, 182000, 900000, $WHO_DARK);
    $out .= textbox(3, 700000, 140000, 11000000, 620000, [[$title, 30, true, 'FFFFFF', 'l', false, 300]]);
    return $out;
}

function footer_band(string $footer): string {
    global $WHO_BLUE;
    $out = rect(90, 0, 6400000, 12192000, 458000, $WHO_BLUE);
    $out .= textbox(91, 700000, 6510000, 11000000, 300000, [[$footer, 12, false, 'FFFFFF', 'l', false, 200]]);
    return $out;
}

// Build a "module" slide with a grid of screenshots + caption under each.
// Returns [spTreeXml, array of rels for images] where rels is ['rId'=>'mediaFile']
function module_slide(string $title, array $shots, string $footer): array {
    global $WHO_BLUE, $WHITE, $WHO_DARK, $WHO_LIGHT;
    $spTree = '<p:nvGrpSpPr><p:cNvPr id="9" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
    $body = header_band($title);

    $n = count($shots);
    // Grid layout: 2 columns
    $cols = 2;
    $rows = (int)ceil($n / $cols);
    $gapX = 280000;
    $gapTitle = 160000; // gap between image and its caption
    $captionH = 780000; // caption area height
    $startY = 1150000;
    $availW = 11200000; // left margin 500000 each side
    $left = 500000;
    $cellW = (int)(($availW - $gapX * ($cols - 1)) / $cols);
    // Image box: preserve 16:10 ratio (1.6). Area below title: 6400000-1150000 = ~5250000 minus caption
    $rowH = 2400000; // includes image + caption
    $imgBoxW = $cellW;
    $imgBoxH = (int)($imgBoxW / 1.6); // ratio 16:10
    if ($imgBoxH > 2050000) { $imgBoxH = 2050000; $imgBoxW = (int)($imgBoxH * 1.6); }

    $rels = [];
    $ridCount = 0;
    $imgIdx = 0;

    foreach ($shots as $si => $shot) {
        list($imgFile, $caption) = $shot;
        if ($caption === '') continue; // skip empty captions (placeholder)
        $imgIdx++;
        $rid = 'rIdImg' . $imgIdx;
        $rels[$rid] = "media/$imgFile.jpg";

        $row = (int)($si / $cols);
        $col = $si % $cols;
        $cx = $left + $col * ($cellW + $gapX);
        $cy = $startY + $row * $rowH;
        // center image horizontally within cell
        $imgX = $cx + (int)(($cellW - $imgBoxW) / 2);
        $imgY = $cy;
        $body .= pic(100 + $imgIdx, $rid, $imgX, $imgY, $imgBoxW, $imgBoxH);
        // caption below
        $body .= textbox(200 + $imgIdx, $cx, $imgY + $imgBoxH + 60000, $cellW, $captionH,
            [[$caption, 12, false, '404040', 'l', false, 400]]);
    }

    $body .= footer_band($footer);
    $spTree .= $body;
    return [$spTree, $rels];
}

function title_slide(string $title, string $subtitle, string $footer): string {
    global $WHO_BLUE, $WHITE;
    $spTree = '<p:nvGrpSpPr><p:cNvPr id="9" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
    $body = rect(1, 0, 0, 12192000, 6858000, $WHO_BLUE);
    $body .= rect(2, 0, 5650000, 182000, 1208000, $WHITE);
    $body .= textbox(3, 800000, 700000, 10600000, 400000, [['FORMATION & GUIDE UTILISATEUR', 16, true, 'FFFFFF', 'l', false, 300]]);
    $body .= textbox(4, 800000, 1950000, 10600000, 1300000, [[$title, 48, true, $WHITE, 'l', false, 800]]);
    $subParas = [];
    foreach (explode("\n", $subtitle) as $line) $subParas[] = [$line, 22, false, 'D0ECF9', 'l', false, 400];
    $body .= textbox(5, 800000, 3450000, 10500000, 900000, $subParas);
    $body .= textbox(6, 800000, 6150000, 10600000, 500000, [[$footer, 14, false, 'B8D9EE', 'l', false, 300]]);
    $spTree .= $body;
    return $spTree;
}

function agenda_slide(string $title, array $items, string $footer): string {
    $spTree = '<p:nvGrpSpPr><p:cNvPr id="9" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
    $body = header_band($title);
    $half = (int)ceil(count($items) / 2);
    $col1 = array_slice($items, 0, $half);
    $col2 = array_slice($items, $half);
    $body .= bullets(4, $col1, 18, '404040', 1700000, 700000, 5400000);
    $body .= bullets(5, $col2, 18, '404040', 1700000, 6200000, 5400000);
    $body .= footer_band($footer);
    $spTree .= $body;
    return $spTree;
}

function menu_slide(string $title, array $cols, string $footer): string {
    global $WHO_BLUE;
    $spTree = '<p:nvGrpSpPr><p:cNvPr id="9" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
    $body = header_band($title);
    $colH = 800000;
    $y = 1750000;
    $x = 700000;
    $colW = 5300000;
    $rowGap = 260000;
    $idx = 0;
    foreach ($cols as $rowIdx => $rowItems) {
        $yy = $y + $rowIdx * ($rowGap + $colH);
        $xx = $x;
        foreach ($rowItems as $cell) {
            $idx++;
            $body .= rect(10 + $idx, $xx, $yy, $colW, $colH, $WHO_BLUE, true);
            $body .= textbox(30 + $idx, $xx + 140000, $yy + 160000, $colW - 280000, $colH - 320000, [[$cell, 16, true, 'FFFFFF', 'l', false, 300]]);
            $xx += $colW + 120000;
        }
    }
    $body .= footer_band($footer);
    $spTree .= $body;
    return $spTree;
}

function content_slide(string $title, array $items, string $footer): string {
    $spTree = '<p:nvGrpSpPr><p:cNvPr id="9" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>' .
        '<p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>';
    $body = header_band($title);
    $body .= bullets(4, $items, 18, '404040', 1700000, 700000, 10800000);
    if ($footer) $body .= footer_band($footer);
    $spTree .= $body;
    return $spTree;
}

// =============================================================
// BUILD THE ZIP
// =============================================================
$zip = new ZipArchive();
if ($zip->open($outFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot create pptx\n");
    exit(1);
}

$slideCount = count($slides);

// Determine total unique media used
$mediaList = []; // img-file-no-ext -> media index
foreach ($slides as $s) {
    if ($s['layout'] === 'module') {
        foreach ($s['shots'] as $shot) {
            if ($shot[1] === '') continue;
            $f = $shot[0];
            if (!isset($mediaList[$f])) $mediaList[$f] = count($mediaList) + 1;
        }
    }
}
$mediaCount = count($mediaList);

$zip->addFromString('[Content_Types].xml', content_types_xml($slideCount, $mediaCount));
$zip->addFromString('_rels/.rels', rels_xml());
$zip->addFromString('docProps/core.xml', core_xml());
$zip->addFromString('docProps/app.xml', app_xml());
$zip->addFromString('ppt/presentation.xml', presentation_xml($slideCount));
$zip->addFromString('ppt/_rels/presentation.xml.rels', presentation_rels_xml($slideCount));
$zip->addFromString('ppt/presProps.xml', presprops_xml());
$zip->addFromString('ppt/tableStyles.xml', table_styles_xml());
$zip->addFromString('ppt/theme/theme1.xml', theme_xml());
$zip->addFromString('ppt/slideMasters/slideMaster1.xml', slide_master_xml());
$zip->addFromString('ppt/slideMasters/_rels/slideMaster1.xml.rels', master_rels_xml());

for ($l = 1; $l <= 5; $l++) {
    $zip->addFromString("ppt/slideLayouts/slideLayout$l.xml", slide_layout_xml("Layout$l"));
    $zip->addFromString("ppt/slideLayouts/_rels/slideLayout$l.xml.rels", layout_rels_xml());
}

// Add media files (stored under their original filename so slide rels match)
foreach ($mediaList as $f => $idx) {
    $src = $imgDir . $f . '.jpg';
    if (!is_file($src)) { fwrite(STDERR, "Missing image: $src\n"); exit(2); }
    $zip->addFile($src, "ppt/media/$f.jpg");
}

$footer = 'OMS AFRO – Data Capture Tool';

foreach ($slides as $i => $slide) {
    $num = $i + 1;
    $layoutNum = 4;
    $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $mediaRels = [];

    switch ($slide['layout']) {
        case 'title':
            $layoutNum = 1;
            $spTree = title_slide($slide['title'], $slide['subtitle'], $slide['footer']);
            break;
        case 'agenda':
            $layoutNum = 2;
            $spTree = agenda_slide($slide['title'], $slide['items'], $footer);
            break;
        case 'menu':
            $layoutNum = 3;
            $spTree = menu_slide($slide['title'], $slide['cols'], $footer);
            break;
        case 'module':
            $layoutNum = 5;
            [$spTree, $mediaRels] = module_slide($slide['title'], $slide['shots'], $footer);
            break;
        case 'content':
        default:
            $layoutNum = 4;
            $spTree = content_slide($slide['title'], $slide['items'], $footer);
            break;
    }

    // slide layout relationship
    $relsXml .= '<Relationship Id="rIdLayout" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout' . $layoutNum . '.xml"/>';
    // image relationships
    foreach ($mediaRels as $rid => $mediaPath) {
        $relsXml .= '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="../' . $mediaPath . '"/>';
    }
    $relsXml .= '</Relationships>';
    $zip->addFromString("ppt/slides/_rels/slide$num.xml.rels", $relsXml);

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
        'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
        'xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">' .
        '<p:cSld><p:spTree>' . $spTree . '</p:spTree></p:cSld>' .
        '<p:clrMapOvr><a:overrideClrMapping bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/></p:clrMapOvr>' .
        '</p:sld>';
    $zip->addFromString("ppt/slides/slide$num.xml", $xml);
    echo "slide $num [" . $slide['layout'] . "] $layoutNum  " . ($mediaRels ? count($mediaRels) . ' img' : '') . "\n";
}

$zip->close();
echo "OK: $outFile (" . $slideCount . " diapositives, " . $mediaCount . " images)\n";
