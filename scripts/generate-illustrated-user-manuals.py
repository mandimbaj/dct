from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
from textwrap import wrap

from PIL import Image, ImageDraw, ImageFont
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.utils import ImageReader
from reportlab.pdfgen import canvas


BASE_DIR = Path(__file__).resolve().parents[1]
SCREENSHOTS_DIR = BASE_DIR / "docs" / "screenshots"
OUTPUT_DIR = BASE_DIR / "output" / "pdf"
TMP_DIR = BASE_DIR / "tmp" / "pdfs" / "manual-assets"

PAGE_WIDTH, PAGE_HEIGHT = landscape(A4)
MARGIN = 30
BLUE = (0, 59, 113)
CYAN = (0, 147, 213)
LIGHT_BLUE = (229, 244, 252)
TEXT = (31, 41, 55)
MUTED = (93, 107, 126)
RED = (213, 63, 63)
GREEN = (26, 127, 55)


@dataclass(frozen=True)
class Callout:
    number: int
    x: float
    y: float
    fr: str
    en: str


@dataclass(frozen=True)
class ManualPage:
    key: str
    title_fr: str
    title_en: str
    screenshot: str | None
    callouts: tuple[Callout, ...]
    sections_fr: tuple[tuple[str, tuple[str, ...]], ...]
    sections_en: tuple[tuple[str, tuple[str, ...]], ...]


PAGES: tuple[ManualPage, ...] = (
    ManualPage(
        key="navigation",
        title_fr="Navigation generale, recherche, langue et alertes",
        title_en="General navigation, search, language and alerts",
        screenshot="00-dashboard.png",
        callouts=(
            Callout(1, 105, 28, "Nom de l'application et logos.", "Application name and logos."),
            Callout(2, 715, 28, "Recherche globale.", "Global search."),
            Callout(3, 1081, 28, "Messages.", "Messages."),
            Callout(4, 1140, 28, "Notifications systeme.", "System notifications."),
            Callout(5, 1320, 28, "Choix de langue.", "Language selector."),
            Callout(6, 75, 88, "Menu principal.", "Main menu."),
        ),
        sections_fr=(
            ("A quoi sert cette page", (
                "La barre superieure permet de rechercher une page, changer la langue, lire les messages et consulter les notifications.",
                "Le menu de gauche donne acces aux modules autorises pour l'utilisateur connecte.",
            )),
            ("Comment utiliser", (
                "Cliquer dans Search pour trouver rapidement un module ou une page.",
                "Cliquer sur Messages ou Notifications pour ouvrir un element; apres lecture, il disparait de la liste active.",
                "Choisir English, Francais ou Portugues dans Language pour changer la langue de l'interface.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "The top bar lets users search pages, change language, read messages and review system notifications.",
                "The left navigation shows only the modules allowed for the signed-in user.",
            )),
            ("How to use", (
                "Click Search to quickly find a module or page.",
                "Click Messages or Notifications to open an item; once read, it leaves the active list.",
                "Choose English, French or Portuguese in Language to switch the interface language.",
            )),
        ),
    ),
    ManualPage(
        key="dashboard",
        title_fr="Dashboard - suivre les volumes et les validations",
        title_en="Dashboard - monitor volumes and validation status",
        screenshot="00-dashboard.png",
        callouts=(
            Callout(1, 69, 87, "Retour au tableau de bord.", "Return to the dashboard."),
            Callout(2, 260, 126, "Cartes de synthese.", "Summary cards."),
            Callout(3, 570, 360, "Graphiques interactifs.", "Interactive charts."),
            Callout(4, 990, 355, "Valeurs et tendances.", "Values and trends."),
        ),
        sections_fr=(
            ("A quoi sert ce module", (
                "Le dashboard resume les locations, les valeurs indicateurs, les archives, les publications, les utilisateurs et les graphiques.",
                "Les utilisateurs pays voient leur pays; les administrateurs regionaux voient le perimetre regional selon leurs permissions.",
            )),
            ("Comment utiliser", (
                "Lire les cartes en haut pour connaitre le nombre d'enregistrements.",
                "Utiliser les graphiques pour identifier rapidement les tendances et les indicateurs les plus utilises.",
                "Cliquer dans un menu de gauche pour ouvrir le module detaille.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "The dashboard summarizes locations, indicator values, archives, publications, users and charts.",
                "Country users see their own country; regional administrators see the regional scope according to permissions.",
            )),
            ("How to use", (
                "Read the top cards to understand the number of records.",
                "Use charts to identify trends and the most used indicators.",
                "Click a left-side menu item to open the detailed module.",
            )),
        ),
    ),
    ManualPage(
        key="indicators-values",
        title_fr="Indicators - consulter, ajouter, importer et approuver les valeurs",
        title_en="Indicators - view, add, import and approve values",
        screenshot="01-indicators-values.png",
        callouts=(
            Callout(1, 90, 132, "Ouvrir le module Indicators.", "Open the Indicators module."),
            Callout(2, 305, 248, "Sous-menu Indicator values.", "Indicator values submenu."),
            Callout(3, 1200, 134, "Importer un fichier Excel.", "Import an Excel file."),
            Callout(4, 1345, 134, "Ajouter une valeur indicateur.", "Add an indicator value."),
            Callout(5, 460, 214, "Exporter les donnees.", "Export data."),
            Callout(6, 1180, 213, "Rechercher ou filtrer.", "Search or filter."),
            Callout(7, 445, 428, "Selectionner une ligne.", "Select a row."),
        ),
        sections_fr=(
            ("Consulter les donnees", (
                "Cliquer Indicators, puis Indicator values.",
                "Utiliser Search, les filtres ou le tri des colonnes pour trouver l'indicateur.",
                "Faire defiler horizontalement si le tableau contient beaucoup de colonnes; la premiere colonne reste visible.",
            )),
            ("Ajouter une donnee", (
                "Cliquer Add indicator value.",
                "Selectionner indicateur, location, periode, source, methode de mesure et options de desagregation.",
                "Saisir la valeur et les commentaires si necessaire.",
                "Cliquer Save. La valeur est creee en Pending jusqu'a validation.",
            )),
            ("Importer et approuver", (
                "Cliquer Import data, telecharger le template, remplir les champs puis charger le fichier.",
                "Verifier les lignes importees dans Indicator values.",
                "Ouvrir une ligne, cliquer Approve pour passer automatiquement le statut a Approved, ou Reject si la donnee est incorrecte.",
            )),
        ),
        sections_en=(
            ("View data", (
                "Click Indicators, then Indicator values.",
                "Use Search, filters or column sorting to find an indicator.",
                "Scroll horizontally when the table has many columns; the first column remains visible.",
            )),
            ("Add data", (
                "Click Add indicator value.",
                "Select indicator, location, period, source, measure method and disaggregation options.",
                "Enter the value and comments if needed.",
                "Click Save. The value is created as Pending until validation.",
            )),
            ("Import and approve", (
                "Click Import data, download the template, fill in the fields and upload the file.",
                "Review imported rows in Indicator values.",
                "Open a row, click Approve to automatically set the status to Approved, or Reject if the value is incorrect.",
            )),
        ),
    ),
    ManualPage(
        key="indicators-references",
        title_fr="Indicators - references, sources, periodes et archives",
        title_en="Indicators - references, sources, periods and archives",
        screenshot="01-indicators-references.png",
        callouts=(
            Callout(1, 285, 570, "Definitions d'indicateurs.", "Indicator definitions."),
            Callout(2, 292, 615, "Domaines et references.", "Domains and references."),
            Callout(3, 292, 725, "Options de desagregation.", "Disaggregation options."),
            Callout(4, 292, 842, "Sources et methodes.", "Sources and methods."),
        ),
        sections_fr=(
            ("A quoi servent les references", (
                "Les references alimentent les listes deroulantes des formulaires indicateurs.",
                "Elles comprennent les indicateurs, domaines, categories, options de desagregation, sources, methodes de mesure et periodes.",
            )),
            ("Comment utiliser", (
                "Ouvrir le sous-menu voulu.",
                "Rechercher l'element par libelle; les listes sont triees alphabetiquement.",
                "Modifier uniquement les references validees par l'equipe metier.",
                "Utiliser Archives pour consulter ou corriger les donnees deja archivees lorsque le profil l'autorise.",
            )),
        ),
        sections_en=(
            ("Purpose of references", (
                "References populate dropdowns in indicator forms.",
                "They include indicators, domains, categories, disaggregation options, sources, measure methods and periods.",
            )),
            ("How to use", (
                "Open the required submenu.",
                "Search by label; lists are sorted alphabetically.",
                "Edit only references validated by the business team.",
                "Use Archives to review or correct archived data when the profile allows it.",
            )),
        ),
    ),
    ManualPage(
        key="uhc-clock",
        title_fr="UHC Clock - progres, themes et indicateurs prioritaires",
        title_en="UHC Clock - progress, themes and priority indicators",
        screenshot="02-uhc-progress.png",
        callouts=(
            Callout(1, 86, 178, "Ouvrir UHC Clock.", "Open UHC Clock."),
            Callout(2, 292, 248, "Progress.", "Progress."),
            Callout(3, 470, 215, "Filtrer les resultats.", "Filter results."),
            Callout(4, 700, 360, "Cliquer un pays pour le detail.", "Click a country for details."),
        ),
        sections_fr=(
            ("Role du module", (
                "UHC Clock suit l'atteinte des indicateurs UHC selectionnes par pays.",
                "Le calcul utilise les donnees courantes et archives; les sources nationales sont priorisees avant les sources internationales.",
            )),
            ("Comment utiliser", (
                "Ouvrir UHC Clock, puis Progress.",
                "Verifier le pays, le niveau du modele UHC Clock et le pourcentage d'atteinte.",
                "Cliquer sur un pays pour ouvrir le detail des indicateurs, valeurs, periodes et sources.",
                "Un utilisateur pays ne voit que son pays; les administrateurs regionaux voient le perimetre autorise.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "UHC Clock tracks progress against selected UHC indicators by country.",
                "The calculation uses current and archived data; national sources are prioritized before international sources.",
            )),
            ("How to use", (
                "Open UHC Clock, then Progress.",
                "Review the country, UHC Clock model level and attainment percentage.",
                "Click a country to open indicator details, values, periods and sources.",
                "A country user sees only their country; regional administrators see their authorized scope.",
            )),
        ),
    ),
    ManualPage(
        key="facilities",
        title_fr="Facilities - etablissements, capacite, preparation et disponibilite",
        title_en="Facilities - facilities, capacity, readiness and availability",
        screenshot="03-facilities-facilities.png",
        callouts=(
            Callout(1, 88, 224, "Ouvrir Facilities.", "Open Facilities."),
            Callout(2, 306, 248, "Health facilities.", "Health facilities."),
            Callout(3, 1160, 134, "Importer depuis Excel.", "Import from Excel."),
            Callout(4, 1340, 134, "Ajouter un etablissement.", "Add a facility."),
            Callout(5, 1175, 213, "Recherche et filtres.", "Search and filters."),
        ),
        sections_fr=(
            ("Sous-menus", (
                "Health facilities, Service capacity, Service readiness, Service availability, Facility ownership, Facility types, Service areas, Service domains, Service interventions et Provision units.",
                "Capacity, readiness et availability affichent seulement les facilities qui ont deja ces donnees.",
            )),
            ("Comment utiliser", (
                "Cliquer Health facilities pour consulter ou ajouter un etablissement.",
                "Cliquer Import data pour charger plusieurs lignes via le template Excel.",
                "Dans le template, les colonnes Owner, Type et autres references proposent des listes de selection.",
                "Si un doublon existe deja, le systeme le signale avant validation.",
            )),
        ),
        sections_en=(
            ("Submenus", (
                "Health facilities, Service capacity, Service readiness, Service availability, Facility ownership, Facility types, Service areas, Service domains, Service interventions and Provision units.",
                "Capacity, readiness and availability display only facilities that already have those records.",
            )),
            ("How to use", (
                "Click Health facilities to view or add a facility.",
                "Click Import data to upload several rows through the Excel template.",
                "In the template, Owner, Type and other reference columns provide dropdown lists.",
                "If a duplicate already exists, the system reports it before validation.",
            )),
        ),
    ),
    ManualPage(
        key="health-workforce",
        title_fr="Health Workforce - effectifs, cadres et institutions",
        title_en="Health Workforce - workforce values, cadres and institutions",
        screenshot="04-workforce-values.png",
        callouts=(
            Callout(1, 105, 269, "Ouvrir Health workforce.", "Open Health workforce."),
            Callout(2, 302, 248, "Workforce values.", "Workforce values."),
            Callout(3, 1175, 213, "Rechercher/filtrer.", "Search/filter."),
            Callout(4, 1340, 134, "Ajouter une donnee.", "Add a record."),
        ),
        sections_fr=(
            ("Role", (
                "Le module gere les donnees de ressources humaines en sante, les cadres, institutions, formations et produits de connaissance.",
                "Il respecte les memes permissions que les autres modules.",
            )),
            ("Comment utiliser", (
                "Ouvrir Health workforce puis choisir Values ou une reference.",
                "Ajouter ou modifier une ligne selon les droits de l'utilisateur.",
                "Utiliser les filtres par pays, periode, cadre ou institution.",
                "Enregistrer; les donnees sensibles doivent etre validees selon le workflow defini.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "This module manages health workforce data, cadres, institutions, training and workforce knowledge products.",
                "It follows the same permission model as the other modules.",
            )),
            ("How to use", (
                "Open Health workforce and choose Values or a reference page.",
                "Add or edit a row according to user rights.",
                "Use filters by country, period, cadre or institution.",
                "Save; sensitive data follows the defined validation workflow.",
            )),
        ),
    ),
    ManualPage(
        key="health-services",
        title_fr="Health Services - programmes, indicateurs et valeurs",
        title_en="Health Services - programmes, indicators and values",
        screenshot="05-services-values.png",
        callouts=(
            Callout(1, 105, 315, "Ouvrir Health services.", "Open Health services."),
            Callout(2, 303, 248, "Service values.", "Service values."),
            Callout(3, 1175, 213, "Recherche et filtres.", "Search and filters."),
            Callout(4, 1340, 134, "Ajouter une valeur.", "Add a value."),
        ),
        sections_fr=(
            ("Role", (
                "Health Services organise les programmes, indicateurs de services et valeurs associees.",
                "Les champs de reference sont choisis dans des listes afin de garder la coherence avec la base.",
            )),
            ("Comment utiliser", (
                "Ouvrir le sous-menu Values pour consulter les donnees.",
                "Cliquer Add pour ajouter une ligne.",
                "Selectionner programme, indicateur, location, periode et valeur.",
                "Cliquer Save puis verifier le statut de validation si applicable.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "Health Services organizes programmes, service indicators and related values.",
                "Reference fields are selected from lists to keep database consistency.",
            )),
            ("How to use", (
                "Open the Values submenu to review data.",
                "Click Add to add a row.",
                "Select programme, indicator, location, period and value.",
                "Click Save and check validation status when applicable.",
            )),
        ),
    ),
    ManualPage(
        key="data-elements",
        title_fr="Data Elements - definitions, groupes et valeurs",
        title_en="Data Elements - definitions, groups and values",
        screenshot="06-dataelements-values.png",
        callouts=(
            Callout(1, 105, 360, "Ouvrir Data elements.", "Open Data elements."),
            Callout(2, 302, 248, "Data element values.", "Data element values."),
            Callout(3, 460, 214, "Exporter.", "Export."),
            Callout(4, 1175, 213, "Recherche.", "Search."),
        ),
        sections_fr=(
            ("Role", (
                "Les data elements sont des donnees structurees qui completent les indicateurs.",
                "Le module separe definitions, groupes et valeurs.",
            )),
            ("Comment utiliser", (
                "Ouvrir Definitions pour gerer le catalogue.",
                "Ouvrir Groups pour organiser les elements.",
                "Ouvrir Values pour saisir, filtrer ou exporter les donnees.",
                "Cliquer Save apres toute creation ou modification.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "Data elements are structured data points that complement indicators.",
                "The module separates definitions, groups and values.",
            )),
            ("How to use", (
                "Open Definitions to manage the catalogue.",
                "Open Groups to organize elements.",
                "Open Values to enter, filter or export data.",
                "Click Save after creating or editing a record.",
            )),
        ),
    ),
    ManualPage(
        key="publications",
        title_fr="Publications - produits de connaissance et fichiers",
        title_en="Publications - knowledge products and files",
        screenshot="07-publications-products.png",
        callouts=(
            Callout(1, 105, 405, "Ouvrir Publications.", "Open Publications."),
            Callout(2, 305, 248, "Knowledge products.", "Knowledge products."),
            Callout(3, 1340, 134, "Ajouter une publication.", "Add a publication."),
            Callout(4, 1175, 213, "Recherche et filtres.", "Search and filters."),
        ),
        sections_fr=(
            ("Role", (
                "Le module gere les produits de connaissance, les ressources analytiques, les categories, types et domaines de publication.",
                "Les fichiers internes et images de couverture peuvent etre charges depuis la machine locale.",
            )),
            ("Comment utiliser", (
                "Cliquer Knowledge products pour consulter la liste.",
                "Cliquer Add pour creer une publication.",
                "Remplir titre, type, categorie, domaine, pays, date, auteur et resume.",
                "Charger Internal file et Cover image, puis cliquer Save.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "The module manages knowledge products, analytical resources, categories, types and publication domains.",
                "Internal files and cover images can be uploaded from the local machine.",
            )),
            ("How to use", (
                "Click Knowledge products to review the list.",
                "Click Add to create a publication.",
                "Fill in title, type, category, domain, country, date, author and summary.",
                "Upload Internal file and Cover image, then click Save.",
            )),
        ),
    ),
    ManualPage(
        key="locations",
        title_fr="Locations - pays, niveaux et classifications",
        title_en="Locations - countries, levels and classifications",
        screenshot="08-regions-locations.png",
        callouts=(
            Callout(1, 105, 497, "Ouvrir Locations.", "Open Locations."),
            Callout(2, 305, 248, "Countries/locations.", "Countries/locations."),
            Callout(3, 1175, 213, "Recherche.", "Search."),
            Callout(4, 1340, 134, "Ajouter une location.", "Add a location."),
        ),
        sections_fr=(
            ("Role", (
                "Locations gere les pays, niveaux administratifs, codes, groupes de revenu et statuts speciaux.",
                "Les champs comme Income group et Special status doivent etre selectionnes dans des listes deroulantes.",
            )),
            ("Comment utiliser", (
                "Ouvrir Locations pour consulter ou modifier les pays.",
                "Utiliser Levels pour gerer les niveaux administratifs.",
                "Choisir les valeurs dans les listes plutot que saisir des nombres.",
                "Cliquer Save apres verification des codes et libelles.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "Locations manages countries, administrative levels, codes, income groups and special statuses.",
                "Fields such as Income group and Special status must be selected from dropdown lists.",
            )),
            ("How to use", (
                "Open Locations to review or edit countries.",
                "Use Levels to manage administrative levels.",
                "Choose values from lists instead of typing numeric ids.",
                "Click Save after checking codes and labels.",
            )),
        ),
    ),
    ManualPage(
        key="data-integration",
        title_fr="Data Integration - connexions, mapping et API",
        title_en="Data Integration - connections, mapping and APIs",
        screenshot="09-dataintegration-connections.png",
        callouts=(
            Callout(1, 105, 543, "Ouvrir Data Integration.", "Open Data Integration."),
            Callout(2, 305, 248, "Connections.", "Connections."),
            Callout(3, 1340, 134, "Creer une connexion.", "Create a connection."),
            Callout(4, 1175, 213, "Rechercher une connexion.", "Search a connection."),
        ),
        sections_fr=(
            ("Role", (
                "Ce module integre des bases externes comme DHIS2, DataBank, WHO DataHub ou d'autres APIs.",
                "Il sert aussi a faire correspondre les champs externes avec les champs du DCT avant validation.",
            )),
            ("Comment utiliser", (
                "Cliquer Create pour creer une connexion.",
                "Renseigner fournisseur, URL serveur, methode, type d'authentification, username, token ou cle API.",
                "Ouvrir Field mappings pour associer chaque champ externe au champ local.",
                "Tester l'import; les donnees arrivent ensuite dans Indicator values pour approbation.",
            )),
            ("Mapping type", (
                "Direct copie la valeur; Lookup cherche une reference; Computed calcule une valeur; Default value applique une constante; Transform convertit la valeur.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "This module integrates external databases such as DHIS2, DataBank, WHO DataHub or other APIs.",
                "It also maps external fields to DCT fields before validation.",
            )),
            ("How to use", (
                "Click Create to create a connection.",
                "Enter provider, server URL, method, authentication type, username, token or API key.",
                "Open Field mappings to match each external field with the local field.",
                "Test the import; data then appears in Indicator values for approval.",
            )),
            ("Mapping type", (
                "Direct copies the value; Lookup finds a reference; Computed calculates a value; Default value applies a constant; Transform converts the value.",
            )),
        ),
    ),
    ManualPage(
        key="data-quality",
        title_fr="Data Quality - detecter et corriger les anomalies",
        title_en="Data Quality - detect and correct anomalies",
        screenshot="10-dataquality-checks.png",
        callouts=(
            Callout(1, 105, 589, "Ouvrir Data quality.", "Open Data quality."),
            Callout(2, 305, 248, "Quality checks.", "Quality checks."),
            Callout(3, 1175, 213, "Filtrer les problemes.", "Filter issues."),
            Callout(4, 1330, 290, "Actions de correction.", "Correction actions."),
        ),
        sections_fr=(
            ("Role", (
                "Data Quality detecte les valeurs manquantes, incoherences, sources invalides, categories invalides et imports echoues.",
                "Le bouton Correct ouvre la correction lorsque l'action est disponible.",
            )),
            ("Comment utiliser", (
                "Ouvrir le controle souhaite.",
                "Filtrer par pays, periode, source ou type d'erreur.",
                "Cliquer Correct, modifier la donnee ou la reference, puis Save.",
                "Relancer le controle pour verifier que l'anomalie est resolue.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "Data Quality detects missing values, inconsistencies, invalid sources, invalid categories and failed imports.",
                "The Correct button opens the correction workflow when available.",
            )),
            ("How to use", (
                "Open the required check.",
                "Filter by country, period, source or error type.",
                "Click Correct, update the data or reference, then Save.",
                "Run the check again to confirm the anomaly is resolved.",
            )),
        ),
    ),
    ManualPage(
        key="api-tokens",
        title_fr="API Tokens - acces technique controle",
        title_en="API Tokens - controlled technical access",
        screenshot="11-apitokens-status.png",
        callouts=(
            Callout(1, 105, 634, "Ouvrir API tokens.", "Open API tokens."),
            Callout(2, 305, 248, "Statut API.", "API status."),
            Callout(3, 640, 330, "Verifier les endpoints.", "Review endpoints."),
        ),
        sections_fr=(
            ("Role", (
                "API Tokens permet de preparer les acces API pour des integrations controlees.",
                "Un token doit etre garde secret et limite au perimetre autorise.",
            )),
            ("Comment utiliser", (
                "Ouvrir API tokens.",
                "Verifier le statut et les endpoints disponibles.",
                "Creer ou copier un token actif selon les droits.",
                "Utiliser l'en-tete Authorization: Bearer <token> dans l'application externe.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "API Tokens prepares controlled API access for integrations.",
                "A token must be kept secret and limited to its authorized scope.",
            )),
            ("How to use", (
                "Open API tokens.",
                "Check status and available endpoints.",
                "Create or copy an active token according to permissions.",
                "Use the Authorization: Bearer <token> header in the external application.",
            )),
        ),
    ),
    ManualPage(
        key="authentication",
        title_fr="Authentication - utilisateurs, roles et permissions",
        title_en="Authentication - users, roles and permissions",
        screenshot="12-authentication-users.png",
        callouts=(
            Callout(1, 105, 680, "Ouvrir Authentication.", "Open Authentication."),
            Callout(2, 305, 248, "Users.", "Users."),
            Callout(3, 1340, 134, "Creer un utilisateur.", "Create a user."),
            Callout(4, 1175, 213, "Recherche.", "Search."),
        ),
        sections_fr=(
            ("Role", (
                "Authentication gere les utilisateurs, roles et permissions par module.",
                "Les menus Health Workforce et National Observatory suivent les memes regles de permission que les autres modules.",
            )),
            ("Comment utiliser", (
                "Ouvrir Users pour creer ou modifier un utilisateur.",
                "Assigner pays, role et permissions.",
                "Ouvrir Roles and permissions pour definir view, create, update, delete, approve, import et export.",
                "Tester avec un utilisateur pays pour confirmer que les modules non autorises sont caches.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "Authentication manages users, roles and module permissions.",
                "Health Workforce and National Observatory follow the same permission rules as other modules.",
            )),
            ("How to use", (
                "Open Users to create or edit a user.",
                "Assign country, role and permissions.",
                "Open Roles and permissions to define view, create, update, delete, approve, import and export.",
                "Test with a country user to confirm unauthorized modules are hidden.",
            )),
        ),
    ),
    ManualPage(
        key="data-wizard",
        title_fr="Data Wizard - historique des imports et exports",
        title_en="Data Wizard - import and export history",
        screenshot="13-datawizard-imports.png",
        callouts=(
            Callout(1, 305, 407, "Imports.", "Imports."),
            Callout(2, 305, 453, "Exports.", "Exports."),
            Callout(3, 1175, 213, "Recherche.", "Search."),
            Callout(4, 460, 214, "Exporter la liste.", "Export the list."),
        ),
        sections_fr=(
            ("Role", (
                "Data Wizard garde l'historique des imports et exports lies aux indicateurs et autres jeux de donnees.",
                "Il ne remplace pas Data Integration; Data Integration sert aux connexions API et mappings de sources externes.",
            )),
            ("Comment utiliser", (
                "Ouvrir Imports pour consulter les fichiers charges, leur statut et les erreurs.",
                "Ouvrir Exports pour consulter l'historique des exports.",
                "Utiliser Failed import rows dans Data Quality pour corriger les lignes rejetees.",
            )),
        ),
        sections_en=(
            ("Purpose", (
                "Data Wizard keeps the history of imports and exports related to indicators and other datasets.",
                "It does not replace Data Integration; Data Integration handles API connections and external source mappings.",
            )),
            ("How to use", (
                "Open Imports to review uploaded files, status and errors.",
                "Open Exports to review export history.",
                "Use Failed import rows in Data Quality to correct rejected rows.",
            )),
        ),
    ),
    ManualPage(
        key="production",
        title_fr="Production - variables d'environnement et emails",
        title_en="Production - environment variables and emails",
        screenshot=None,
        callouts=(),
        sections_fr=(
            ("Variables principales", (
                "APP_ENV=production, APP_DEBUG=false, APP_URL=https://dct.afro.who.int.",
                "DB_* et WAREHOUSE_DB_* pour les connexions bases de donnees.",
                "CACHE_STORE=redis, SESSION_DRIVER=redis et QUEUE_CONNECTION=redis pour la charge.",
                "MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_SCHEME=tls et MAIL_FROM_ADDRESS pour les emails.",
                "AHO_ADMIN_EMAIL_NOTIFICATIONS=true pour envoyer messages et notifications par email.",
                "MICROSOFT_ENTRA_* pour la connexion Microsoft Entra ID.",
            )),
            ("Checklist de mise en production", (
                "Configurer les variables dans Azure App Service ou un coffre de secrets.",
                "Executer php artisan migrate --force.",
                "Executer php artisan optimize.",
                "Executer php artisan app:production-readiness --fail.",
                "Tester connexion, notifications email, import, approbation, export et chatbot.",
            )),
        ),
        sections_en=(
            ("Main variables", (
                "APP_ENV=production, APP_DEBUG=false, APP_URL=https://dct.afro.who.int.",
                "DB_* and WAREHOUSE_DB_* for database connections.",
                "CACHE_STORE=redis, SESSION_DRIVER=redis and QUEUE_CONNECTION=redis for load.",
                "MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_SCHEME=tls and MAIL_FROM_ADDRESS for emails.",
                "AHO_ADMIN_EMAIL_NOTIFICATIONS=true to send messages and notifications by email.",
                "MICROSOFT_ENTRA_* for Microsoft Entra ID sign-in.",
            )),
            ("Go-live checklist", (
                "Configure variables in Azure App Service or a secrets vault.",
                "Run php artisan migrate --force.",
                "Run php artisan optimize.",
                "Run php artisan app:production-readiness --fail.",
                "Test sign-in, email notifications, import, approval, export and chatbot.",
            )),
        ),
    ),
)


def text_for(lang: str, page: ManualPage) -> tuple[str, tuple[tuple[str, tuple[str, ...]], ...]]:
    if lang == "fr":
        return page.title_fr, page.sections_fr
    return page.title_en, page.sections_en


def label_for(lang: str, callout: Callout) -> str:
    return callout.fr if lang == "fr" else callout.en


def ensure_dirs() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)
    TMP_DIR.mkdir(parents=True, exist_ok=True)


def font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        "C:/Windows/Fonts/arialbd.ttf" if bold else "C:/Windows/Fonts/arial.ttf",
        "C:/Windows/Fonts/segoeuib.ttf" if bold else "C:/Windows/Fonts/segoeui.ttf",
    ]
    for candidate in candidates:
        if Path(candidate).exists():
            return ImageFont.truetype(candidate, size=size)
    return ImageFont.load_default()


def annotate_screenshot(page: ManualPage, lang: str) -> Path | None:
    if page.screenshot is None:
        return None

    source = SCREENSHOTS_DIR / page.screenshot
    if not source.exists():
        return None

    image = Image.open(source).convert("RGB")
    draw = ImageDraw.Draw(image, "RGBA")
    w, h = image.size
    label_font = font(20, bold=True)

    for callout in page.callouts:
        x = int(callout.x / 1440 * w)
        y = int(callout.y / 900 * h)
        radius = max(16, int(20 / 1440 * w))
        draw.ellipse((x - radius, y - radius, x + radius, y + radius), fill=RED + (235,), outline=(255, 255, 255, 255), width=4)
        text = str(callout.number)
        bbox = draw.textbbox((0, 0), text, font=label_font)
        draw.text((x - (bbox[2] - bbox[0]) / 2, y - (bbox[3] - bbox[1]) / 2 - 2), text, fill=(255, 255, 255), font=label_font)

    output = TMP_DIR / f"{page.key}-{lang}.jpg"
    image.save(output, quality=88, optimize=True)
    return output


def wrap_text(text: str, max_chars: int) -> list[str]:
    result: list[str] = []
    for paragraph in text.split("\n"):
        result.extend(wrap(paragraph, width=max_chars) or [""])
    return result


def draw_wrapped(c: canvas.Canvas, text: str, x: float, y: float, max_width: float, size: int = 9.5, leading: float = 12, color: tuple[int, int, int] = TEXT) -> float:
    c.setFillColorRGB(color[0] / 255, color[1] / 255, color[2] / 255)
    c.setFont("Helvetica", size)
    chars = max(32, int(max_width / (size * 0.47)))
    for line in wrap_text(text, chars):
        c.drawString(x, y, line)
        y -= leading
    return y


def draw_header(c: canvas.Canvas, lang: str, title: str, page_no: int) -> None:
    c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
    c.rect(0, PAGE_HEIGHT - 45, PAGE_WIDTH, 45, fill=1, stroke=0)
    c.setFillColorRGB(1, 1, 1)
    c.setFont("Helvetica-Bold", 13)
    c.drawString(MARGIN, PAGE_HEIGHT - 28, "Data Capture Tool")
    c.setFont("Helvetica", 9)
    c.drawRightString(PAGE_WIDTH - MARGIN, PAGE_HEIGHT - 28, f"{'Manuel utilisateur' if lang == 'fr' else 'User manual'} - {page_no}")
    c.setFillColorRGB(TEXT[0] / 255, TEXT[1] / 255, TEXT[2] / 255)
    c.setFont("Helvetica-Bold", 18)
    c.drawString(MARGIN, PAGE_HEIGHT - 75, title)


def draw_footer(c: canvas.Canvas, lang: str) -> None:
    c.setStrokeColorRGB(210 / 255, 220 / 255, 230 / 255)
    c.line(MARGIN, 24, PAGE_WIDTH - MARGIN, 24)
    c.setFillColorRGB(MUTED[0] / 255, MUTED[1] / 255, MUTED[2] / 255)
    c.setFont("Helvetica", 8)
    note = "Document de formation - Data Capture Tool Laravel" if lang == "fr" else "Training document - Data Capture Tool Laravel"
    c.drawString(MARGIN, 12, note)
    c.drawRightString(PAGE_WIDTH - MARGIN, 12, "WHO AFRO iAHO")


def draw_callout_legend(c: canvas.Canvas, page: ManualPage, lang: str, x: float, y: float, width: float) -> float:
    if not page.callouts:
        return y

    c.setFont("Helvetica-Bold", 10)
    c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
    c.drawString(x, y, "Reperes" if lang == "fr" else "Callouts")
    y -= 15

    for callout in page.callouts:
        c.setFillColorRGB(RED[0] / 255, RED[1] / 255, RED[2] / 255)
        c.circle(x + 7, y + 3, 7, fill=1, stroke=0)
        c.setFillColorRGB(1, 1, 1)
        c.setFont("Helvetica-Bold", 7)
        c.drawCentredString(x + 7, y, str(callout.number))
        label = label_for(lang, callout)
        y = draw_wrapped(c, label, x + 20, y, width - 20, size=8, leading=10)
        y -= 2
    return y


def draw_sections(c: canvas.Canvas, sections: tuple[tuple[str, tuple[str, ...]], ...], lang: str, x: float, y: float, width: float) -> float:
    for heading, bullets in sections:
        c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
        c.setFont("Helvetica-Bold", 10.5)
        c.drawString(x, y, heading)
        y -= 14
        for bullet in bullets:
            c.setFillColorRGB(CYAN[0] / 255, CYAN[1] / 255, CYAN[2] / 255)
            c.circle(x + 4, y + 4, 2.2, fill=1, stroke=0)
            y = draw_wrapped(c, bullet, x + 13, y, width - 13, size=8.6, leading=10.5)
            y -= 3
        y -= 4
    return y


def draw_process_diagram(c: canvas.Canvas, lang: str, x: float, y: float, width: float, mode: str = "record") -> None:
    if mode == "production":
        labels_fr = ["Configurer ENV", "Optimiser", "Verifier readiness", "Tester emails"]
        labels_en = ["Set env vars", "Optimize", "Run readiness", "Test emails"]
    else:
        labels_fr = ["Creer / ouvrir", "Remplir les champs", "Cliquer Save", "Valider / approuver"]
        labels_en = ["Create / open", "Fill fields", "Click Save", "Validate / approve"]

    labels = labels_fr if lang == "fr" else labels_en
    box_w = (width - 45) / 4
    for i, label in enumerate(labels):
        bx = x + i * (box_w + 15)
        c.setFillColorRGB(LIGHT_BLUE[0] / 255, LIGHT_BLUE[1] / 255, LIGHT_BLUE[2] / 255)
        c.setStrokeColorRGB(CYAN[0] / 255, CYAN[1] / 255, CYAN[2] / 255)
        c.roundRect(bx, y, box_w, 45, 6, fill=1, stroke=1)
        c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
        c.setFont("Helvetica-Bold", 8.5)
        c.drawCentredString(bx + box_w / 2, y + 25, label)
        if i < len(labels) - 1:
            c.setStrokeColorRGB(CYAN[0] / 255, CYAN[1] / 255, CYAN[2] / 255)
            c.line(bx + box_w + 3, y + 22, bx + box_w + 12, y + 22)
            c.line(bx + box_w + 12, y + 22, bx + box_w + 8, y + 26)
            c.line(bx + box_w + 12, y + 22, bx + box_w + 8, y + 18)


def draw_cover(c: canvas.Canvas, lang: str) -> None:
    c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
    c.rect(0, 0, PAGE_WIDTH, PAGE_HEIGHT, fill=1, stroke=0)
    c.setFillColorRGB(1, 1, 1)
    title = "Manuel illustre d'utilisation" if lang == "fr" else "Illustrated User Manual"
    subtitle = "Data Capture Tool Laravel" if lang == "fr" else "Data Capture Tool Laravel"
    c.setFont("Helvetica-Bold", 30)
    c.drawString(MARGIN + 10, PAGE_HEIGHT - 110, title)
    c.setFont("Helvetica-Bold", 22)
    c.drawString(MARGIN + 10, PAGE_HEIGHT - 145, subtitle)
    c.setFont("Helvetica", 12)
    detail = (
        "Guide pas a pas avec captures annotees, procedures de saisie, import, validation et configuration production."
        if lang == "fr"
        else "Step-by-step guide with annotated screenshots, data entry, import, validation and production setup procedures."
    )
    draw_wrapped(c, detail, MARGIN + 10, PAGE_HEIGHT - 180, 510, size=12, leading=16, color=(255, 255, 255))

    dashboard = SCREENSHOTS_DIR / "00-dashboard.png"
    if dashboard.exists():
        img = ImageReader(str(dashboard))
        c.drawImage(img, MARGIN + 10, 80, width=500, height=312, preserveAspectRatio=True, mask="auto")

    c.setFillColorRGB(1, 1, 1)
    c.setFont("Helvetica", 10)
    c.drawString(MARGIN + 10, 40, "Generated: 2 October 2026")
    c.drawRightString(PAGE_WIDTH - MARGIN, 40, "WHO AFRO iAHO")


def draw_page(c: canvas.Canvas, page: ManualPage, lang: str, page_no: int) -> None:
    title, sections = text_for(lang, page)
    draw_header(c, lang, title, page_no)

    left_x = MARGIN
    left_w = 275
    right_x = left_x + left_w + 20
    right_w = PAGE_WIDTH - right_x - MARGIN
    content_top = PAGE_HEIGHT - 100

    annotated = annotate_screenshot(page, lang)
    if annotated:
        img = Image.open(annotated)
        img_ratio = img.width / img.height
        target_w = right_w
        target_h = target_w / img_ratio
        max_h = PAGE_HEIGHT - 165
        if target_h > max_h:
            target_h = max_h
            target_w = target_h * img_ratio
        c.setFillColorRGB(1, 1, 1)
        c.setStrokeColorRGB(214 / 255, 224 / 255, 235 / 255)
        c.roundRect(right_x - 6, content_top - target_h - 6, target_w + 12, target_h + 12, 8, fill=1, stroke=1)
        c.drawImage(ImageReader(str(annotated)), right_x, content_top - target_h, width=target_w, height=target_h, preserveAspectRatio=True)
        draw_process_diagram(c, lang, right_x, 58, min(target_w, right_w))
    else:
        c.setFillColorRGB(LIGHT_BLUE[0] / 255, LIGHT_BLUE[1] / 255, LIGHT_BLUE[2] / 255)
        c.setStrokeColorRGB(CYAN[0] / 255, CYAN[1] / 255, CYAN[2] / 255)
        c.roundRect(right_x, 170, right_w, 260, 10, fill=1, stroke=1)
        c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
        c.setFont("Helvetica-Bold", 20)
        c.drawCentredString(right_x + right_w / 2, 390, "ENV")
        c.setFont("Helvetica-Bold", 14)
        c.drawCentredString(right_x + right_w / 2, 355, "Azure App Service")
        draw_process_diagram(c, lang, right_x + 30, 265, right_w - 60, mode="production")

    y = content_top
    y = draw_sections(c, sections, lang, left_x, y, left_w)
    if y > 55:
        draw_callout_legend(c, page, lang, left_x, y - 4, left_w)
    draw_footer(c, lang)


def build_manual(lang: str, output_name: str) -> Path:
    output = OUTPUT_DIR / output_name
    c = canvas.Canvas(str(output), pagesize=landscape(A4))
    c.setTitle("Data Capture Tool - " + ("Manuel illustre" if lang == "fr" else "Illustrated user manual"))
    draw_cover(c, lang)
    c.showPage()

    for idx, page in enumerate(PAGES, start=2):
        draw_page(c, page, lang, idx)
        c.showPage()

    c.save()
    return output


def main() -> None:
    ensure_dirs()
    fr = build_manual("fr", "dct-laravel-manuel-illustre-fr.pdf")
    en = build_manual("en", "dct-laravel-illustrated-user-manual-en.pdf")
    print(fr)
    print(en)


if __name__ == "__main__":
    main()
