from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
from textwrap import wrap

from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.utils import ImageReader
from reportlab.pdfgen import canvas


BASE_DIR = Path(__file__).resolve().parents[1]
SCREENSHOTS_DIR = BASE_DIR / "docs" / "screenshots"
OUTPUT_DIR = BASE_DIR / "output" / "pdf"

PAGE_WIDTH, PAGE_HEIGHT = landscape(A4)
MARGIN = 34
BLUE = (0, 59, 113)
CYAN = (0, 147, 213)
LIGHT = (232, 245, 252)
TEXT = (31, 41, 55)
MUTED = (91, 105, 124)
RED = (214, 68, 68)
GREEN = (18, 128, 85)


@dataclass(frozen=True)
class Topic:
    module_fr: str
    module_en: str
    title_fr: str
    title_en: str
    path_fr: str
    path_en: str
    screenshot: str | None
    purpose_fr: tuple[str, ...]
    purpose_en: tuple[str, ...]
    steps_fr: tuple[str, ...]
    steps_en: tuple[str, ...]
    checks_fr: tuple[str, ...]
    checks_en: tuple[str, ...]
    tips_fr: tuple[str, ...] = ()
    tips_en: tuple[str, ...] = ()


def value_topic(module_fr: str, module_en: str, title_fr: str, title_en: str, path: str, screenshot: str | None, entity_fr: str, entity_en: str) -> Topic:
    return Topic(
        module_fr,
        module_en,
        title_fr,
        title_en,
        path,
        path,
        screenshot,
        (
            f"Ce sous-menu sert a consulter, creer, modifier et suivre les {entity_fr}.",
            "Il est utilise pour les donnees operationnelles qui peuvent etre filtrees par pays, periode, source, statut ou categorie.",
        ),
        (
            f"This submenu is used to view, create, edit and monitor {entity_en}.",
            "It is used for operational data that can be filtered by country, period, source, status or category.",
        ),
        (
            f"Ouvrir {path}.",
            "Utiliser la recherche et les filtres pour verifier si l'enregistrement existe deja.",
            "Pour consulter, cliquer la ligne ou utiliser l'action View/Edit selon les droits.",
            "Pour ajouter, cliquer Add ou New, remplir tous les champs obligatoires, puis cliquer Save.",
            "Si le bouton Import data existe, telecharger le template, remplir les colonnes, puis charger le fichier.",
            "Si un workflow d'approbation existe, laisser la donnee en Pending jusqu'a verification par un administrateur ou validateur.",
            "Apres validation, utiliser Export pour produire un fichier CSV ou Excel des lignes filtrees.",
        ),
        (
            f"Open {path}.",
            "Use search and filters to check whether the record already exists.",
            "To review a record, click the row or use View/Edit according to permissions.",
            "To add a record, click Add or New, fill in all required fields, then click Save.",
            "If Import data is available, download the template, fill in the columns, then upload the file.",
            "If an approval workflow exists, keep the record as Pending until an administrator or validator reviews it.",
            "After validation, use Export to produce a CSV or Excel file from the filtered rows.",
        ),
        (
            "Verifier que le pays est correct avant d'enregistrer.",
            "Verifier la periode, la source de donnees et les champs de desagregation.",
            "Eviter les doublons: une meme donnee ne doit pas etre creee deux fois pour le meme contexte.",
            "Lire les notifications apres import ou creation pour suivre les elements en attente.",
        ),
        (
            "Confirm that the country is correct before saving.",
            "Check the period, data source and disaggregation fields.",
            "Avoid duplicates: the same data must not be created twice for the same context.",
            "Read notifications after import or creation to monitor pending items.",
        ),
        (
            "Quand une liste deroulante est longue, taper les premieres lettres du libelle pour trouver plus vite l'element.",
            "Les utilisateurs pays ne doivent voir que leur pays; si d'autres pays apparaissent, verifier les permissions.",
        ),
        (
            "When a dropdown is long, type the first letters of the label to find the item faster.",
            "Country users should only see their country; if other countries appear, check permissions.",
        ),
    )


def reference_topic(module_fr: str, module_en: str, title_fr: str, title_en: str, path: str, screenshot: str | None, entity_fr: str, entity_en: str) -> Topic:
    return Topic(
        module_fr,
        module_en,
        title_fr,
        title_en,
        path,
        path,
        screenshot,
        (
            f"Ce sous-menu gere les references {entity_fr} utilisees dans les listes deroulantes et les validations.",
            "Une reference bien tenue evite les erreurs de saisie, les doublons et les donnees impossibles a analyser.",
        ),
        (
            f"This submenu manages {entity_en} references used in dropdown lists and validations.",
            "Well maintained references prevent input errors, duplicates and data that cannot be analyzed.",
        ),
        (
            f"Ouvrir {path}.",
            "Rechercher d'abord le libelle ou le code pour eviter de creer un doublon.",
            "Cliquer Add ou New pour creer une reference.",
            "Renseigner le libelle, le code si le systeme le demande, la description et les relations avec les autres references.",
            "Cliquer Save.",
            "Si une reference est deja utilisee par des donnees, preferer la modifier avec prudence plutot que la supprimer.",
        ),
        (
            f"Open {path}.",
            "Search first by label or code to avoid creating a duplicate.",
            "Click Add or New to create a reference.",
            "Enter the label, code if requested, description and links to other references.",
            "Click Save.",
            "If a reference is already used by data, edit it carefully instead of deleting it.",
        ),
        (
            "Le libelle doit etre clair pour l'utilisateur final.",
            "Le code doit rester stable s'il est utilise par un import ou une API.",
            "Verifier les traductions si l'application est utilisee en francais, anglais et portugais.",
        ),
        (
            "The label must be clear for the end user.",
            "The code must remain stable if it is used by imports or APIs.",
            "Check translations when the application is used in French, English and Portuguese.",
        ),
        (
            "Ne jamais remplacer une reference par une autre sans verifier les donnees existantes.",
        ),
        (
            "Never replace a reference with another one without checking existing data.",
        ),
    )


def quality_topic(title_fr: str, title_en: str, path: str, screenshot: str | None, issue_fr: str, issue_en: str) -> Topic:
    return Topic(
        "Data Quality",
        "Data Quality",
        title_fr,
        title_en,
        path,
        path,
        screenshot,
        (
            f"Ce controle sert a identifier {issue_fr}.",
            "Il aide l'utilisateur a corriger les anomalies avant validation ou exploitation des donnees.",
        ),
        (
            f"This check identifies {issue_en}.",
            "It helps users correct anomalies before validation or data use.",
        ),
        (
            f"Ouvrir {path}.",
            "Filtrer par pays, indicateur, periode, source ou type d'anomalie.",
            "Lire la raison du probleme dans la ligne affichee.",
            "Cliquer Correct lorsque le bouton est disponible.",
            "Modifier la valeur ou la reference liee, puis cliquer Save.",
            "Revenir au controle et verifier que l'anomalie a disparu.",
        ),
        (
            f"Open {path}.",
            "Filter by country, indicator, period, source or anomaly type.",
            "Read the problem reason displayed on the row.",
            "Click Correct when the button is available.",
            "Update the related value or reference, then click Save.",
            "Return to the check and confirm that the anomaly has disappeared.",
        ),
        (
            "Ne pas corriger une donnee sans comprendre la source du probleme.",
            "Si l'erreur vient du fichier importe, corriger le fichier puis reimporter.",
            "Documenter le commentaire si la valeur semble inhabituelle mais correcte.",
        ),
        (
            "Do not correct data without understanding the source of the issue.",
            "If the error comes from the imported file, correct the file and import again.",
            "Add a comment when a value looks unusual but is correct.",
        ),
    )


TOPICS: list[Topic] = []


def add(topic: Topic) -> None:
    TOPICS.append(topic)


def build_topics() -> None:
    # Getting started and common operations.
    add(Topic(
        "Demarrage",
        "Getting started",
        "Connexion, langue et contexte pays",
        "Sign-in, language and country context",
        "Login page, header, language selector",
        "Login page, header, language selector",
        "00-login.png",
        (
            "Cette partie explique comment entrer dans le DCT en production avec le compte Microsoft de l'OMS.",
            "La page de connexion redirige vers Microsoft Entra ID, qui verifie l'identite de l'utilisateur avant d'ouvrir le DCT.",
        ),
        (
            "This section explains how to access the DCT in production with the WHO Microsoft account.",
            "The sign-in page redirects to Microsoft Entra ID, which verifies the user's identity before opening the DCT.",
        ),
        (
            "Ouvrir l'URL de production du DCT.",
            "Sur la page Microsoft, saisir l'adresse email professionnelle OMS.",
            "Cliquer Next, puis suivre les instructions Microsoft: mot de passe, validation multifactorielle ou approbation mobile si demandee.",
            "Apres connexion, verifier le pays affiche dans le tableau de bord ou le menu lateral.",
            "Changer la langue depuis le selecteur de l'en-tete si necessaire.",
        ),
        (
            "Open the production DCT URL.",
            "On the Microsoft page, enter the WHO work email address.",
            "Click Next, then follow the Microsoft instructions: password, multifactor verification or mobile approval when requested.",
            "After sign-in, check the country displayed on the dashboard or sidebar.",
            "Change the language from the header selector if needed.",
        ),
        (
            "Si l'utilisateur voit le mauvais pays, verifier son compte dans Authentication > Users.",
            "Si la connexion Microsoft echoue, contacter l'administrateur DCT ou le support informatique OMS.",
        ),
        (
            "If the user sees the wrong country, check the account in Authentication > Users.",
            "If Microsoft sign-in fails, contact the DCT administrator or WHO IT support.",
        ),
    ))

    add(Topic(
        "Demarrage",
        "Getting started",
        "Dashboard, recherche globale, messages et notifications",
        "Dashboard, global search, messages and notifications",
        "Dashboard",
        "Dashboard",
        "00-dashboard.png",
        (
            "Le dashboard donne la vue d'ensemble des donnees, des utilisateurs, des archives et des graphiques.",
            "L'en-tete contient la recherche, les messages, les notifications et le choix de langue.",
        ),
        (
            "The dashboard gives an overview of data, users, archives and charts.",
            "The header contains search, messages, notifications and language selection.",
        ),
        (
            "Utiliser la barre Search pour retrouver un menu, une page ou une action.",
            "Cliquer l'icone Messages pour lire les messages recus.",
            "Cliquer l'icone Notifications pour voir les donnees en attente ou les alertes systeme.",
            "Cliquer un message ou une notification pour l'ouvrir; apres lecture, l'element est retire de la liste active.",
            "Lire les cartes du dashboard pour suivre les volumes: locations, indicators, indicator data, archive, sources/methods et users.",
        ),
        (
            "Use the Search bar to find a menu, page or action.",
            "Click the Messages icon to read received messages.",
            "Click the Notifications icon to view pending data or system alerts.",
            "Click a message or notification to open it; after reading, it leaves the active list.",
            "Read dashboard cards to monitor volumes: locations, indicators, indicator data, archive, sources/methods and users.",
        ),
        (
            "Les notifications importantes peuvent aussi etre recues par email selon le profil utilisateur.",
            "Un utilisateur pays doit voir uniquement les donnees de son pays.",
        ),
        (
            "Important notifications may also be received by email depending on the user profile.",
            "A country user should only see data from their country.",
        ),
    ))

    add(Topic(
        "Operations communes",
        "Common operations",
        "Utiliser les tableaux: recherche, filtres, tri et export",
        "Using tables: search, filters, sorting and export",
        "Any table",
        "Any table",
        "01-indicators-values.png",
        (
            "La plupart des pages du DCT utilisent les memes tableaux Filament.",
            "Ces controles permettent de trouver, filtrer, trier et exporter les donnees.",
        ),
        (
            "Most DCT pages use the same Filament tables.",
            "These controls let users find, filter, sort and export data.",
        ),
        (
            "Saisir un mot dans Search pour rechercher un libelle, un code, un pays, une source ou une periode.",
            "Cliquer l'icone filtre pour limiter les resultats par pays, statut, periode ou reference.",
            "Cliquer l'en-tete d'une colonne pour trier.",
            "Utiliser les cases a cocher pour selectionner plusieurs lignes si une action groupee est disponible.",
            "Cliquer Export puis choisir CSV ou Excel.",
            "Pour les grands tableaux, faire defiler vers la droite; la premiere colonne reste visible pour garder le contexte.",
        ),
        (
            "Type a word in Search to find a label, code, country, source or period.",
            "Click the filter icon to limit results by country, status, period or reference.",
            "Click a column header to sort.",
            "Use checkboxes to select several rows when a bulk action is available.",
            "Click Export then choose CSV or Excel.",
            "For wide tables, scroll to the right; the first column remains visible to keep context.",
        ),
        (
            "Avant export, verifier que les filtres affichent bien les lignes attendues.",
            "Si le resultat est vide, enlever les filtres un par un.",
        ),
        (
            "Before exporting, confirm that filters display the expected rows.",
            "If the result is empty, remove filters one by one.",
        ),
    ))

    add(Topic(
        "Operations communes",
        "Common operations",
        "Creer, modifier, sauvegarder et supprimer un enregistrement",
        "Create, edit, save and delete a record",
        "Any create/edit page",
        "Any create/edit page",
        "12-authentication-users.png",
        (
            "Les formulaires du DCT suivent le meme principe: ouvrir, remplir les champs, sauvegarder, puis verifier le resultat.",
            "Certaines actions comme Delete ou Approve dependent des permissions.",
        ),
        (
            "DCT forms follow the same principle: open, fill fields, save, then verify the result.",
            "Some actions such as Delete or Approve depend on permissions.",
        ),
        (
            "Depuis la liste, cliquer Add/New pour creer ou cliquer Edit sur une ligne existante.",
            "Remplir les champs obligatoires; les champs avec asterisque ou message de validation sont requis.",
            "Pour les listes deroulantes, taper les premieres lettres du libelle puis selectionner l'element.",
            "Cliquer Save ou Save changes.",
            "Revenir a la liste, rechercher l'enregistrement et verifier qu'il apparait correctement.",
            "Pour supprimer, cliquer Delete seulement si l'enregistrement ne doit plus etre conserve; confirmer la suppression.",
        ),
        (
            "From the list, click Add/New to create or click Edit on an existing row.",
            "Fill required fields; fields with a star or validation message are mandatory.",
            "For dropdown lists, type the first letters of the label and select the item.",
            "Click Save or Save changes.",
            "Return to the list, search for the record and confirm that it appears correctly.",
            "To delete, click Delete only when the record should no longer be kept; confirm deletion.",
        ),
        (
            "Ne pas saisir de code manuel lorsque le systeme genere le code automatiquement.",
            "Ne pas supprimer une reference deja utilisee dans des donnees sans validation de l'administrateur.",
        ),
        (
            "Do not type a manual code when the system generates the code automatically.",
            "Do not delete a reference already used by data without administrator validation.",
        ),
    ))

    # Indicators.
    add(value_topic("Indicators", "Indicators", "Indicator values", "Indicator values", "Indicators > Data > Indicator values", "01-indicators-values.png", "valeurs d'indicateurs", "indicator values"))
    add(value_topic("Indicators", "Indicators", "Archives", "Archives", "Indicators > Data > Archives", "01-indicators-archives.png", "donnees indicateurs archivees", "archived indicator values"))
    add(value_topic("Indicators", "Indicators", "Imports", "Imports", "Indicators > Data wizard > Imports", "13-datawizard-imports.png", "imports de donnees", "data imports"))
    add(value_topic("Indicators", "Indicators", "Exports", "Exports", "Indicators > Data wizard > Exports", "13-datawizard-exports.png", "exports de donnees", "data exports"))
    add(reference_topic("Indicators", "Indicators", "Indicator definitions", "Indicator definitions", "Indicators > References > Indicators", "01-indicators-definitions.png", "d'indicateurs", "indicator"))
    add(reference_topic("Indicators", "Indicators", "Indicator domains", "Indicator domains", "Indicators > References > Indicator domains", "01-indicators-domains.png", "de domaines d'indicateurs", "indicator domain"))
    add(reference_topic("Indicators", "Indicators", "Indicator references", "Indicator references", "Indicators > References > Indicator references", "01-indicators-references.png", "bibliographiques et metadonnees", "bibliographic and metadata"))
    add(reference_topic("Indicators", "Indicators", "Disaggregation categories", "Disaggregation categories", "Indicators > References > Disaggregation categories", "01-indicators-disaggregation-categories.png", "de categories de desagregation", "disaggregation category"))
    add(reference_topic("Indicators", "Indicators", "Disaggregation options", "Disaggregation options", "Indicators > References > Disaggregation options", "01-indicators-disaggregation-options.png", "d'options de desagregation", "disaggregation option"))
    add(reference_topic("Indicators", "Indicators", "Sources", "Sources", "Indicators > References > Sources", "01-indicators-sources.png", "de sources de donnees", "data source"))
    add(reference_topic("Indicators", "Indicators", "Measure methods", "Measure methods", "Indicators > References > Measure methods", "01-indicators-measure-methods.png", "de methodes de mesure", "measure method"))
    add(reference_topic("Indicators", "Indicators", "Periods", "Periods", "Indicators > References > Periods", "01-indicators-periods.png", "de periodes", "period"))

    # UHC Clock.
    add(value_topic("UHC Clock", "UHC Clock", "UHC progress", "UHC progress", "UHC Clock > Progress", "02-uhc-progress.png", "resultats de progression UHC", "UHC progress results"))
    add(value_topic("UHC Clock", "UHC Clock", "Priority indicators", "Priority indicators", "UHC Clock > Data > Priority indicators", "02-uhc-priority.png", "indicateurs prioritaires par pays", "priority indicators by country"))
    add(reference_topic("UHC Clock", "UHC Clock", "UHC themes", "UHC themes", "UHC Clock > References > Themes", "02-uhc-themes.png", "de themes UHC", "UHC theme"))
    add(reference_topic("UHC Clock", "UHC Clock", "UHC groups", "UHC groups", "UHC Clock > References > Groups", "02-uhc-groups.png", "de groupes UHC", "UHC group"))
    add(reference_topic("UHC Clock", "UHC Clock", "UHC indicators", "UHC indicators", "UHC Clock > References > Indicators", "02-uhc-indicators.png", "d'indicateurs UHC", "UHC indicator"))

    # Facilities.
    add(value_topic("Facilities", "Facilities", "Health facilities", "Health facilities", "Facilities > Data > Health facilities", "03-facilities-facilities.png", "etablissements sanitaires", "health facilities"))
    add(value_topic("Facilities", "Facilities", "Service capacity", "Service capacity", "Facilities > Data > Service capacity", "03-facilities-service-capacity.png", "donnees de capacite de service", "service capacity data"))
    add(value_topic("Facilities", "Facilities", "Service readiness", "Service readiness", "Facilities > Data > Service readiness", "03-facilities-service-readiness.png", "donnees de preparation de service", "service readiness data"))
    add(value_topic("Facilities", "Facilities", "Service availability", "Service availability", "Facilities > Data > Service availability", "03-facilities-service-availability.png", "donnees de disponibilite de service", "service availability data"))
    add(reference_topic("Facilities", "Facilities", "Facility owners", "Facility owners", "Facilities > References > Facility owners", "03-facilities-owners.png", "de proprietaires d'etablissements", "facility owner"))
    add(reference_topic("Facilities", "Facilities", "Facility types", "Facility types", "Facilities > References > Facility types", "03-facilities-types.png", "de types d'etablissements", "facility type"))
    add(reference_topic("Facilities", "Facilities", "Service areas", "Service areas", "Facilities > References > Service areas", "03-facilities-service-areas.png", "d'aires de service", "service area"))
    add(reference_topic("Facilities", "Facilities", "Service domains", "Service domains", "Facilities > References > Service domains", "03-facilities-service-domains.png", "de domaines de service", "service domain"))
    add(reference_topic("Facilities", "Facilities", "Service interventions", "Service interventions", "Facilities > References > Service interventions", "03-facilities-service-interventions.png", "d'interventions de service", "service intervention"))
    add(reference_topic("Facilities", "Facilities", "Provision units", "Provision units", "Facilities > References > Provision units", "03-facilities-provision-units.png", "d'unites de prestation", "provision unit"))

    # Health Workforce.
    add(value_topic("Health Workforce", "Health Workforce", "Workforce values", "Workforce values", "Health workforce > Data > Workforce values", "04-workforce-values.png", "donnees de workforce", "workforce values"))
    add(value_topic("Health Workforce", "Health Workforce", "Resources / guides", "Resources / guides", "Health workforce > Data > Resources / guides", "04-workforce-resources-guides.png", "ressources et guides workforce", "workforce resources and guides"))
    add(reference_topic("Health Workforce", "Health Workforce", "Health cadres", "Health cadres", "Health workforce > References > Health cadres", "04-workforce-cadres.png", "de cadres de sante", "health cadre"))
    add(reference_topic("Health Workforce", "Health Workforce", "Training institutions", "Training institutions", "Health workforce > References > Training institutions", "04-workforce-training.png", "d'institutions de formation", "training institution"))
    add(reference_topic("Health Workforce", "Health Workforce", "Institution types", "Institution types", "Health workforce > References > Institution types", "04-workforce-institution-types.png", "de types d'institution", "institution type"))
    add(reference_topic("Health Workforce", "Health Workforce", "Training programmes", "Training programmes", "Health workforce > References > Training programmes", "04-workforce-training-programmes.png", "de programmes de formation", "training programme"))
    add(reference_topic("Health Workforce", "Health Workforce", "Resource types", "Resource types", "Health workforce > References > Resource types", "04-workforce-resource-types.png", "de types de ressources workforce", "workforce resource type"))
    add(reference_topic("Health Workforce", "Health Workforce", "Resource categories", "Resource categories", "Health workforce > References > Resource categories", "04-workforce-resource-categories.png", "de categories de ressources workforce", "workforce resource category"))

    # Health services.
    add(value_topic("Health Services", "Health Services", "Service values", "Service values", "Health services > Data > Service values", "05-services-values.png", "valeurs de services de sante", "health service values"))
    add(reference_topic("Health Services", "Health Services", "HSC indicators", "HSC indicators", "Health services > References > HSC indicators", "05-services-hsc-indicators.png", "d'indicateurs de services", "health service indicator"))
    add(reference_topic("Health Services", "Health Services", "HSC programmes", "HSC programmes", "Health services > References > HSC programmes", "05-services-hsc-programmes.png", "de programmes de services", "health service programme"))
    add(reference_topic("Health Services", "Health Services", "HSC programmes lookup", "HSC programmes lookup", "Health services > References > HSC programmes lookup", "05-services-programmes-lookup.png", "de correspondances de programmes", "programme lookup"))

    # Data elements.
    add(value_topic("Data Elements", "Data Elements", "Data element values", "Data element values", "Data elements > Data > Values", "06-dataelements-values.png", "valeurs de data elements", "data element values"))
    add(reference_topic("Data Elements", "Data Elements", "Data element definitions", "Data element definitions", "Data elements > References > Definitions", "06-dataelements-definitions.png", "de data elements", "data element"))
    add(reference_topic("Data Elements", "Data Elements", "Data element groups", "Data element groups", "Data elements > References > Groups", "06-dataelements-groups.png", "de groupes de data elements", "data element group"))

    # Publications.
    add(value_topic("Publications", "Publications", "Knowledge products", "Knowledge products", "Publications > Data > Products", "07-publications-products.png", "publications et knowledge products", "publications and knowledge products"))
    add(reference_topic("Publications", "Publications", "Publication domains", "Publication domains", "Publications > References > Domains", "07-publications-domains.png", "de domaines de publication", "publication domain"))
    add(reference_topic("Publications", "Publications", "Resource types", "Resource types", "Publications > References > Types", "07-publications-types.png", "de types de ressources", "resource type"))
    add(reference_topic("Publications", "Publications", "Resource categories", "Resource categories", "Publications > References > Categories", "07-publications-categories.png", "de categories de ressources", "resource category"))

    # National Observatory.
    add(value_topic("National Observatory", "National Observatory", "National observatories", "National observatories", "National observatory > National observatories", "08-national-observatories.png", "observatoires nationaux", "national observatories"))

    # Locations.
    add(reference_topic("Locations", "Locations", "Locations / countries", "Locations / countries", "Locations > References > Locations", "08-regions-locations.png", "de pays et locations", "country and location"))
    add(reference_topic("Locations", "Locations", "Level 2 locations", "Level 2 locations", "Locations > References > Level 2 locations", "08-regions-level-2-locations.png", "de locations niveau 2", "level 2 location"))
    add(reference_topic("Locations", "Locations", "Location levels", "Location levels", "Locations > References > Levels", "08-regions-levels.png", "de niveaux administratifs", "administrative level"))
    add(reference_topic("Locations", "Locations", "Income groups", "Income groups", "Locations > References > Income groups", "08-regions-income-groups.png", "de groupes de revenu", "income group"))
    add(reference_topic("Locations", "Locations", "Economic blocks", "Economic blocks", "Locations > References > Economic blocks", "08-regions-economic-zones.png", "de blocs economiques", "economic block"))
    add(reference_topic("Locations", "Locations", "Special categorizations", "Special categorizations", "Locations > References > Special categorizations", "08-regions-special-categorizations.png", "de statuts speciaux", "special categorization"))
    add(reference_topic("Locations", "Locations", "Dial codes", "Dial codes", "Locations > References > Dial codes", "08-regions-dial-codes.png", "de codes telephoniques", "dial code"))

    # Data integration with custom details.
    add(Topic(
        "Data Integration",
        "Data Integration",
        "Connections",
        "Connections",
        "Data integration > Sources > Connections",
        "Data integration > Sources > Connections",
        "09-dataintegration-connections.png",
        (
            "Connections definit les systemes externes qui alimentent le DCT: DHIS2, DataBank, WHO DataHub, AHO warehouse ou autres sources.",
            "Une connexion peut etre directe vers une base de donnees ou passer par une API.",
        ),
        (
            "Connections defines external systems feeding the DCT: DHIS2, DataBank, WHO DataHub, AHO warehouse or other sources.",
            "A connection can be direct to a database or through an API.",
        ),
        (
            "Ouvrir Data integration > Connections.",
            "Cliquer New data integration connection.",
            "Choisir le pays si la connexion est nationale, ou regional/unassigned pour une source regionale.",
            "Choisir Provider et Integration method.",
            "Pour Direct connection: renseigner server, port, database type, database name, source table/view et SSL/TLS.",
            "Pour API: renseigner API URL, Authentication, token, username/password, API key ou OAuth2 selon la source.",
            "Cliquer Save, puis Check configuration.",
            "Si la connexion est valide, passer a Field mapping avant de synchroniser.",
        ),
        (
            "Open Data integration > Connections.",
            "Click New data integration connection.",
            "Choose the country for a national connection, or regional/unassigned for a regional source.",
            "Choose Provider and Integration method.",
            "For Direct connection: enter server, port, database type, database name, source table/view and SSL/TLS.",
            "For API: enter API URL, Authentication, token, username/password, API key or OAuth2 depending on the source.",
            "Click Save, then Check configuration.",
            "If the connection is valid, configure Field mapping before synchronizing.",
        ),
        (
            "Les secrets sont chiffres dans la base; ne pas les copier dans un document non securise.",
            "En production, preferer TLS required ou Verify server identity.",
            "Tester avec un petit echantillon avant d'activer un flux recurrent.",
        ),
        (
            "Secrets are encrypted in the database; do not copy them into unsecured documents.",
            "In production, prefer TLS required or Verify server identity.",
            "Test with a small sample before enabling a recurring flow.",
        ),
    ))
    add(Topic(
        "Data Integration",
        "Data Integration",
        "Field mapping",
        "Field mapping",
        "Data integration > Connections > Mapping",
        "Data integration > Connections > Mapping",
        "09-dataintegration-field-mapping.png",
        (
            "Field mapping transforme les champs externes en champs attendus par le DCT.",
            "Il est indispensable car les autres systemes peuvent separer age et sexe, utiliser d'autres codes ou nommer autrement les periodes.",
        ),
        (
            "Field mapping transforms external fields into the fields expected by the DCT.",
            "It is required because other systems may separate age and sex, use different codes or name periods differently.",
        ),
        (
            "Ouvrir la connexion puis cliquer Mapping.",
            "Cliquer Load source fields pour detecter les champs externes.",
            "Cliquer Suggest mappings pour obtenir une proposition automatique, puis la relire.",
            "Pour chaque local field obligatoire, choisir l'external field.",
            "Choisir le mapping type: Direct, Lookup, Computed, Conditional ou Skip.",
            "Pour Lookup, definir comment trouver la reference: code, name, id ou automatic.",
            "Renseigner Default value si la source ne fournit pas le champ.",
            "Cliquer Save mapping puis relancer Check configuration.",
        ),
        (
            "Open the connection and click Mapping.",
            "Click Load source fields to detect external fields.",
            "Click Suggest mappings to get an automatic proposal, then review it.",
            "For every required local field, choose the external field.",
            "Choose the mapping type: Direct, Lookup, Computed, Conditional or Skip.",
            "For Lookup, define how to resolve the reference: code, name, id or automatic.",
            "Enter Default value if the source does not provide the field.",
            "Click Save mapping then run Check configuration again.",
        ),
        (
            "Ne jamais valider un mapping sans verifier indicator, location, period, value, datasource, category option et measure method.",
            "Si plusieurs sources renseignent le meme indicateur, garder la regle de priorite: source nationale d'abord, source internationale ensuite.",
        ),
        (
            "Never validate a mapping without checking indicator, location, period, value, datasource, category option and measure method.",
            "When several sources provide the same indicator, keep the priority rule: national source first, international source second.",
        ),
    ))

    # Data quality.
    add(quality_topic("Indicator checks", "Indicator checks", "Data quality > Indicator checks", "10-dataquality-checks.png", "les problemes principaux sur les valeurs d'indicateurs", "main issues on indicator values"))
    add(quality_topic("Failed import rows", "Failed import rows", "Data quality > Failed rows", "10-dataquality-failed-imports.png", "les lignes rejetees pendant un import", "rows rejected during import"))
    add(quality_topic("Facts dataset", "Facts dataset", "Data quality > Facts dataset", "10-dataquality-facts-dataset.png", "les donnees factuelles a controler", "fact records to review"))
    add(quality_topic("Facts filter", "Facts filter", "Data quality > Facts filter", "10-dataquality-facts-filter.png", "les filtres utilises pour cibler les controles", "filters used to target checks"))
    add(quality_topic("Missing values", "Missing values", "Data quality > Missing values", "10-dataquality-missing-values.png", "les valeurs manquantes", "missing values"))
    add(quality_topic("Multiple measures", "Multiple measures", "Data quality > Multiple measures", "10-dataquality-multiple-measures.png", "les mesures multiples ou ambigues", "multiple or ambiguous measures"))
    add(quality_topic("Internal consistencies", "Internal consistencies", "Data quality > Internal consistencies", "10-dataquality-internal-consistencies.png", "les incoherences internes", "internal inconsistencies"))
    add(quality_topic("External consistencies", "External consistencies", "Data quality > External consistencies", "10-dataquality-external-consistencies.png", "les incoherences avec une source externe", "inconsistencies with an external source"))
    add(quality_topic("Value type checks", "Value type checks", "Data quality > Value type checks", "10-dataquality-value-type-consistencies.png", "les types de valeurs incoherents", "inconsistent value types"))
    add(quality_topic("Check categories", "Check categories", "Data quality > Check categories", "10-dataquality-check-categories.png", "les categories non valides", "invalid categories"))
    add(quality_topic("Check measures", "Check measures", "Data quality > Check measures", "10-dataquality-check-measures.png", "les methodes de mesure non valides", "invalid measure methods"))
    add(quality_topic("Check periods", "Check periods", "Data quality > Check periods", "10-dataquality-check-periods.png", "les periodes non valides", "invalid periods"))
    add(quality_topic("Check sources", "Check sources", "Data quality > Check sources", "10-dataquality-check-sources.png", "les sources non valides", "invalid sources"))
    add(quality_topic("Valid category options", "Valid category options", "Data quality > Category options", "10-dataquality-categoryoptions.png", "les categories option autorisees", "allowed category options"))
    add(quality_topic("Valid data sources", "Valid data sources", "Data quality > Datasources", "10-dataquality-datasources.png", "les sources autorisees", "allowed data sources"))
    add(quality_topic("Valid measure types", "Valid measure types", "Data quality > Measure types", "10-dataquality-measuretypes.png", "les types de mesure autorises", "allowed measure types"))

    # API tokens and authentication.
    add(Topic(
        "API Tokens",
        "API Tokens",
        "Token status, endpoints and examples",
        "Token status, endpoints and examples",
        "API tokens > Token status",
        "API tokens > Token status",
        "11-apitokens-status.png",
        (
            "API tokens permet a une application externe de lire ou envoyer certaines donnees.",
            "Le token est un secret: il doit etre copie une seule fois puis conserve dans un coffre securise.",
        ),
        (
            "API tokens lets an external application read or send selected data.",
            "The token is a secret: it must be copied once and stored in a secure vault.",
        ),
        (
            "Ouvrir API tokens.",
            "Verifier le statut des tokens existants: active, expired ou revoked.",
            "Creer un token avec un nom clair et une date d'expiration si l'acces est temporaire.",
            "Copier le token immediatement.",
            "Dans Postman ou l'application externe, envoyer Authorization: Bearer <token>.",
            "Tester un endpoint GET avant d'envoyer des donnees.",
            "Pour creer une valeur indicateur, utiliser l'endpoint indicator-values avec les champs requis.",
        ),
        (
            "Open API tokens.",
            "Check existing token status: active, expired or revoked.",
            "Create a token with a clear name and an expiry date when access is temporary.",
            "Copy the token immediately.",
            "In Postman or the external application, send Authorization: Bearer <token>.",
            "Test a GET endpoint before sending data.",
            "To create an indicator value, use the indicator-values endpoint with required fields.",
        ),
        (
            "Revoquer un token qui n'est plus utilise.",
            "Ne jamais envoyer un token par email non securise.",
        ),
        (
            "Revoke a token that is no longer used.",
            "Never send a token through unsecured email.",
        ),
    ))

    add(value_topic("Authentication", "Authentication", "Users", "Users", "Authentication > Users", "12-authentication-users.png", "utilisateurs", "users"))
    add(reference_topic("Authentication", "Authentication", "Roles and permissions", "Roles and permissions", "Authentication > Roles and permissions", "12-authentication-roles.png", "de roles et permissions", "roles and permissions"))
    add(value_topic("Authentication", "Authentication", "User history", "User history", "Authentication > User history", "12-authentication-user-history.png", "historiques de navigation utilisateur", "user navigation history"))



def ensure_output() -> None:
    OUTPUT_DIR.mkdir(parents=True, exist_ok=True)


def language_values(topic: Topic, lang: str) -> dict[str, object]:
    if lang == "fr":
        return {
            "module": topic.module_fr,
            "title": topic.title_fr,
            "path": topic.path_fr,
            "purpose_title": "Role du sous-menu",
            "steps_title": "Comment faire",
            "checks_title": "A verifier avant de terminer",
            "tips_title": "Conseils pratiques",
            "purpose": topic.purpose_fr,
            "steps": topic.steps_fr,
            "checks": topic.checks_fr,
            "tips": topic.tips_fr,
            "manual": "Manuel utilisateur complet",
            "toc": "Sommaire",
            "generated": "Genere le 2 octobre 2026",
        }
    return {
        "module": topic.module_en,
        "title": topic.title_en,
        "path": topic.path_en,
        "purpose_title": "Submenu purpose",
        "steps_title": "How to do it",
        "checks_title": "What to check before finishing",
        "tips_title": "Practical tips",
        "purpose": topic.purpose_en,
        "steps": topic.steps_en,
        "checks": topic.checks_en,
        "tips": topic.tips_en,
        "manual": "Complete user manual",
        "toc": "Table of contents",
        "generated": "Generated on 2 October 2026",
    }


def set_rgb(c: canvas.Canvas, color: tuple[int, int, int]) -> None:
    c.setFillColorRGB(color[0] / 255, color[1] / 255, color[2] / 255)


def draw_header(c: canvas.Canvas, lang: str, section: str, page_no: int) -> None:
    c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
    c.rect(0, PAGE_HEIGHT - 44, PAGE_WIDTH, 44, fill=1, stroke=0)
    c.setFillColorRGB(1, 1, 1)
    c.setFont("Helvetica-Bold", 13)
    c.drawString(MARGIN, PAGE_HEIGHT - 28, "Data Capture Tool")
    c.setFont("Helvetica", 8.5)
    c.drawCentredString(PAGE_WIDTH / 2, PAGE_HEIGHT - 28, section)
    c.drawRightString(PAGE_WIDTH - MARGIN, PAGE_HEIGHT - 28, f"{'Page' if lang == 'en' else 'Page'} {page_no}")


def draw_footer(c: canvas.Canvas, lang: str) -> None:
    c.setStrokeColorRGB(210 / 255, 220 / 255, 230 / 255)
    c.line(MARGIN, 23, PAGE_WIDTH - MARGIN, 23)
    set_rgb(c, MUTED)
    c.setFont("Helvetica", 8)
    c.drawString(MARGIN, 11, "DCT Laravel - " + ("Manuel utilisateur complet" if lang == "fr" else "Complete user manual"))
    c.drawRightString(PAGE_WIDTH - MARGIN, 11, "WHO AFRO iAHO")


def wrap_lines(text: str, max_chars: int) -> list[str]:
    lines: list[str] = []
    for part in text.splitlines():
        lines.extend(wrap(part, width=max_chars) or [""])
    return lines


def draw_text(c: canvas.Canvas, text: str, x: float, y: float, width: float, size: float = 9, leading: float = 11, color: tuple[int, int, int] = TEXT, bold: bool = False) -> float:
    c.setFont("Helvetica-Bold" if bold else "Helvetica", size)
    set_rgb(c, color)
    chars = max(28, int(width / (size * 0.48)))
    for line in wrap_lines(text, chars):
        c.drawString(x, y, line)
        y -= leading
    return y


def draw_bullets(c: canvas.Canvas, items: tuple[str, ...], x: float, y: float, width: float, size: float = 8.6) -> float:
    for item in items:
        set_rgb(c, CYAN)
        c.circle(x + 3, y + 3.5, 2.1, fill=1, stroke=0)
        y = draw_text(c, item, x + 12, y, width - 12, size=size, leading=size + 2.2)
        y -= 2.5
    return y


def draw_numbered(c: canvas.Canvas, items: tuple[str, ...], x: float, y: float, width: float, size: float = 8.6) -> float:
    for idx, item in enumerate(items, start=1):
        set_rgb(c, RED)
        c.circle(x + 7, y + 4, 7, fill=1, stroke=0)
        c.setFillColorRGB(1, 1, 1)
        c.setFont("Helvetica-Bold", 6.8)
        c.drawCentredString(x + 7, y + 1.5, str(idx))
        y = draw_text(c, item, x + 20, y, width - 20, size=size, leading=size + 2.3)
        y -= 2.5
    return y


def draw_section(c: canvas.Canvas, heading: str, items: tuple[str, ...], x: float, y: float, width: float, numbered: bool = False) -> float:
    set_rgb(c, BLUE)
    c.setFont("Helvetica-Bold", 11)
    c.drawString(x, y, heading)
    y -= 15
    if numbered:
        return draw_numbered(c, items, x, y, width)
    return draw_bullets(c, items, x, y, width)


def draw_screenshot(c: canvas.Canvas, screenshot: str | None, x: float, y: float, width: float, height: float, lang: str = "fr") -> None:
    if not screenshot:
        return

    path = SCREENSHOTS_DIR / screenshot
    if not path.exists():
        return

    c.setFillColorRGB(1, 1, 1)
    c.setStrokeColorRGB(214 / 255, 224 / 255, 235 / 255)
    c.roundRect(x - 5, y - height - 5, width + 10, height + 10, 8, fill=1, stroke=1)
    c.drawImage(ImageReader(str(path)), x, y - height, width=width, height=height, preserveAspectRatio=True, anchor="n")


def draw_cover(c: canvas.Canvas, lang: str) -> None:
    c.setFillColorRGB(BLUE[0] / 255, BLUE[1] / 255, BLUE[2] / 255)
    c.rect(0, 0, PAGE_WIDTH, PAGE_HEIGHT, fill=1, stroke=0)
    c.setFillColorRGB(1, 1, 1)
    c.setFont("Helvetica-Bold", 30)
    c.drawString(MARGIN + 10, PAGE_HEIGHT - 115, "Data Capture Tool Laravel")
    c.setFont("Helvetica-Bold", 24)
    c.drawString(MARGIN + 10, PAGE_HEIGHT - 153, "Manuel utilisateur complet" if lang == "fr" else "Complete User Manual")
    c.setFont("Helvetica", 12)
    subtitle = (
        "Guide detaille menu par menu et sous-menu par sous-menu: consultation, saisie, import, validation, export et administration."
        if lang == "fr"
        else "Detailed guide by menu and submenu: viewing, data entry, import, validation, export and administration."
    )
    draw_text(c, subtitle, MARGIN + 10, PAGE_HEIGHT - 190, 620, size=12, leading=15, color=(255, 255, 255))
    draw_screenshot(c, "00-dashboard.png", MARGIN + 10, PAGE_HEIGHT - 245, 520, 325, lang=lang)
    c.setFont("Helvetica", 10)
    c.drawString(MARGIN + 10, 42, "Generated: 2 October 2026")
    c.drawRightString(PAGE_WIDTH - MARGIN, 42, "WHO AFRO iAHO")


def draw_toc(c: canvas.Canvas, lang: str, start_page: int) -> int:
    page_no = start_page
    draw_header(c, lang, "Sommaire" if lang == "fr" else "Table of contents", page_no)
    y = PAGE_HEIGHT - 82
    set_rgb(c, TEXT)
    c.setFont("Helvetica-Bold", 18)
    c.drawString(MARGIN, y, "Sommaire" if lang == "fr" else "Table of contents")
    y -= 28

    current_module = ""
    for idx, topic in enumerate(TOPICS, start=3):
        values = language_values(topic, lang)
        module = str(values["module"])
        title = str(values["title"])
        if y < 56:
            draw_footer(c, lang)
            c.showPage()
            page_no += 1
            draw_header(c, lang, "Sommaire" if lang == "fr" else "Table of contents", page_no)
            y = PAGE_HEIGHT - 82
        if module != current_module:
            current_module = module
            set_rgb(c, BLUE)
            c.setFont("Helvetica-Bold", 11)
            c.drawString(MARGIN, y, module)
            y -= 15
        set_rgb(c, TEXT)
        c.setFont("Helvetica", 8.5)
        c.drawString(MARGIN + 14, y, title[:86])
        c.drawRightString(PAGE_WIDTH - MARGIN, y, str(idx))
        y -= 11
    draw_footer(c, lang)
    c.showPage()
    return page_no + 1


def draw_topic(c: canvas.Canvas, topic: Topic, lang: str, page_no: int) -> None:
    v = language_values(topic, lang)
    title = str(v["title"])
    module = str(v["module"])
    path = str(v["path"])
    draw_header(c, lang, module, page_no)

    c.setFont("Helvetica-Bold", 17)
    set_rgb(c, TEXT)
    c.drawString(MARGIN, PAGE_HEIGHT - 76, title)
    c.setFont("Helvetica", 8.5)
    set_rgb(c, MUTED)
    c.drawString(MARGIN, PAGE_HEIGHT - 94, path)

    text_x = MARGIN
    text_w = 385
    image_x = MARGIN + text_w + 24
    image_w = PAGE_WIDTH - image_x - MARGIN
    image_h = 242

    draw_screenshot(c, topic.screenshot, image_x, PAGE_HEIGHT - 112, image_w, image_h, lang=lang)

    y = PAGE_HEIGHT - 120
    y = draw_section(c, str(v["purpose_title"]), tuple(v["purpose"]), text_x, y, text_w)
    y -= 4
    y = draw_section(c, str(v["steps_title"]), tuple(v["steps"]), text_x, y, text_w, numbered=True)
    y -= 4
    if y < 145:
        c.setFont("Helvetica-Bold", 9)
        set_rgb(c, GREEN)
        c.drawString(text_x, 130, "Suite: controles et conseils dans le bloc de droite." if lang == "fr" else "Continued: checks and tips in the right block.")
    else:
        y = draw_section(c, str(v["checks_title"]), tuple(v["checks"]), text_x, y, text_w)

    right_y = PAGE_HEIGHT - 378
    if y < 145:
        right_y = draw_section(c, str(v["checks_title"]), tuple(v["checks"]), image_x, right_y, image_w)
    if tuple(v["tips"]):
        draw_section(c, str(v["tips_title"]), tuple(v["tips"]), image_x, right_y - 6, image_w)

    draw_footer(c, lang)


def build_manual(lang: str, filename: str) -> Path:
    output = OUTPUT_DIR / filename
    c = canvas.Canvas(str(output), pagesize=landscape(A4))
    c.setTitle("Data Capture Tool - " + ("Manuel utilisateur complet" if lang == "fr" else "Complete user manual"))
    draw_cover(c, lang)
    c.showPage()
    next_page = draw_toc(c, lang, 2)
    page_no = next_page
    for topic in TOPICS:
        draw_topic(c, topic, lang, page_no)
        c.showPage()
        page_no += 1
    c.save()
    return output


def main() -> None:
    ensure_output()
    build_topics()
    fr = build_manual("fr", "dct-laravel-manuel-utilisateur-complet-fr.pdf")
    en = build_manual("en", "dct-laravel-complete-user-manual-en.pdf")
    print(fr)
    print(en)


if __name__ == "__main__":
    main()
