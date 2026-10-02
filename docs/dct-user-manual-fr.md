# Manuel utilisateur - Data Capture Tool Laravel

Derniere mise a jour: 2 octobre 2026

## 1. Objectif de l'outil

Le Data Capture Tool permet aux equipes pays et regionales de saisir, importer, verifier, approuver, archiver et exploiter les donnees de sante. L'application Laravel garde la meme base de donnees de reference, mais ajoute une interface plus structuree, des permissions plus fines, des notifications et une integration de donnees via API/fichiers.

## 2. Acces et connexion

1. Ouvrir l'URL de production du DCT.
2. Sur la page Microsoft, saisir l'adresse email professionnelle OMS.
3. Cliquer Next, puis suivre les instructions Microsoft: mot de passe, validation multifactorielle ou approbation mobile si demandee.
4. Apres connexion, verifier le pays actif dans l'en-tete.
5. Utiliser le menu utilisateur pour changer de langue, consulter le profil ou se deconnecter.

## 3. Tableau de bord

Le tableau de bord donne une vue rapide sur les volumes de donnees:

- locations;
- indicateurs;
- valeurs d'indicateurs approuvees, en attente, rejetees;
- archives;
- publications;
- utilisateurs;
- graphiques de synthese.

Les utilisateurs pays ne voient que les donnees de leur pays. Les administrateurs regionaux et super administrateurs peuvent suivre l'ensemble regional selon leurs permissions.

## 4. Recherche globale et navigation

1. Utiliser la barre de recherche en haut pour retrouver rapidement un menu ou une ressource.
2. Utiliser le menu lateral pour ouvrir les grands modules.
3. Dans un module, utiliser les sous-menus pour passer entre donnees, references et outils.
4. Les tableaux larges gardent la premiere colonne visible pendant le defilement horizontal afin de conserver le contexte.

## 5. Messages et notifications

Deux zones sont disponibles dans l'en-tete:

- Messages: communications adressees aux utilisateurs concernes.
- Notifications: evenements systeme, nouvelles donnees, elements en attente de validation, creation ou mise a jour importante.

Procedure:

1. Cliquer sur l'icone message ou notification.
2. Cliquer sur un element pour l'ouvrir.
3. Apres lecture, l'element est retire de la liste active.
4. En production, les memes alertes peuvent aussi etre envoyees par email aux administrateurs regionaux et pays concernes.

## 6. Indicateurs

Le module Indicators sert a gerer les definitions et les valeurs des indicateurs.

### Ajouter une valeur indicateur

1. Ouvrir Indicators > Indicator values.
2. Cliquer sur Add indicator value.
3. Selectionner l'indicateur, le pays, la periode, la source, la methode de mesure et les options de desagregation.
4. Saisir la valeur.
5. Enregistrer.
6. La donnee reste en attente si elle doit etre validee.

### Importer plusieurs valeurs

1. Ouvrir Indicators > Indicator values.
2. Cliquer sur Import data.
3. Telecharger le template Excel.
4. Remplir le template. Les champs avec references proposent des listes de selection.
5. Charger le fichier.
6. Corriger les erreurs signalees si le systeme detecte des doublons ou des valeurs invalides.
7. Les valeurs importees arrivent en attente d'approbation.

### Approuver ou rejeter

1. Ouvrir l'enregistrement.
2. Cliquer sur Approve pour passer automatiquement le statut a Approved.
3. Cliquer sur Reject si la donnee doit etre refusee.
4. Les notifications informent les utilisateurs concernes.

### Archives

1. Ouvrir Indicators > Archives.
2. Consulter les donnees archivees depuis `fact_data_archive`.
3. Le super administrateur peut modifier une donnee archivee si une correction est necessaire.
4. Les champs affiches restent alignes sur ceux des valeurs d'indicateurs.

## 7. Data Integration

Le module Data Integration sert a importer des donnees depuis des systemes externes qui n'ont pas toujours la meme structure que le DCT.

### Creer une connexion

1. Ouvrir Data Integration > Connections.
2. Cliquer sur Create.
3. Choisir le fournisseur: DHIS2, DataBank, WHO DataHub ou autre source.
4. Choisir la methode: serveur direct ou API.
5. Renseigner l'URL du serveur, le type d'authentification, le nom d'utilisateur, le token API ou la cle API selon le cas.
6. Enregistrer la connexion.

Les mots de passe, tokens, cles API et secrets sont chiffres dans la base de donnees.

### Correspondance des champs

Avant validation, chaque connexion doit definir comment les champs externes correspondent aux champs DCT.

Types de mapping:

| Type | Role | Exemple |
| --- | --- | --- |
| Direct | Copier la valeur externe telle quelle | `value` vers `value_received` |
| Lookup | Rechercher une reference DCT a partir d'un code ou libelle externe | `dx` DHIS2 vers `indicator_id` |
| Computed | Calculer ou deduire une valeur | extraire `start_period` depuis `pe` |
| Default value | Utiliser une valeur fixe si la source ne fournit rien | source de donnees par defaut |
| Transform | Appliquer une conversion | diviser par 1000, normaliser une annee |

Procedure:

1. Ouvrir la connexion.
2. Aller dans Field mappings.
3. Pour chaque champ local obligatoire, choisir le champ externe correspondant.
4. Definir le type de mapping.
5. Ajouter une valeur par defaut si necessaire.
6. Enregistrer.
7. Tester l'import.

### Import DHIS2

1. Creer une connexion DHIS2.
2. Renseigner l'URL, l'utilisateur ou le token API.
3. Mapper les champs essentiels: indicateur, pays, periode, valeur, source de donnees, categorie option et methode de mesure.
4. Lancer l'import.
5. Verifier les lignes creees dans Indicator values.
6. L'administrateur approuve ensuite les donnees.

## 8. Data Quality

Le module Data Quality aide a detecter et corriger les problemes:

- valeurs manquantes;
- incoherences internes;
- incoherences externes;
- mesures multiples;
- sources invalides;
- categories invalides;
- periodes invalides.

Procedure:

1. Ouvrir Data Quality.
2. Selectionner le controle souhaite.
3. Utiliser les filtres pour trouver les anomalies.
4. Cliquer sur Correct lorsque l'action est disponible.
5. Modifier la donnee ou la reference.
6. Enregistrer et refaire le controle.

## 9. Publications

Le module Publications gere les produits de connaissance, documents analytiques et ressources.

Procedure:

1. Ouvrir Publications > Knowledge products.
2. Cliquer sur Create.
3. Renseigner le titre, type, categorie, domaine, pays, auteurs, date, resume et autres champs requis.
4. Charger le fichier interne et l'image de couverture depuis la machine locale.
5. Enregistrer.

Les fichiers publies sont stockes dans l'espace de stockage configure pour le DCT.

## 10. Facilities

Le module Facilities gere les etablissements et leurs donnees associees.

Sous-menus principaux:

- Health facilities;
- Service capacity;
- Service readiness;
- Service availability;
- Facility ownership;
- Facility types;
- Service areas;
- Service domains;
- Service interventions;
- Provision units.

Procedure d'import:

1. Ouvrir Health facilities.
2. Telecharger le template Excel.
3. Remplir les colonnes. Les colonnes liees a des references proposent des listes de selection.
4. Charger le fichier.
5. Corriger les doublons ou erreurs signales.
6. Enregistrer les donnees valides.

## 11. Health Workforce

Le module Health Workforce gere les effectifs, cadres, institutions, formations et produits de connaissance lies aux ressources humaines en sante.

1. Ouvrir Health Workforce.
2. Choisir le sous-menu.
3. Ajouter ou importer les donnees.
4. Utiliser les filtres pour controler les donnees par pays, periode ou categorie.
5. Approuver les donnees si le workflow le demande.

## 12. UHC Clock

Le module UHC Clock suit les indicateurs prioritaires de couverture sanitaire universelle.

Fonctionnement:

1. Les administrateurs definissent les themes, groupes et indicateurs UHC.
2. Les pays selectionnent leurs indicateurs prioritaires.
3. Le systeme calcule les progres a partir des donnees disponibles dans les tables courantes et archives.
4. Les donnees nationales sont priorisees. Si aucune source nationale n'est disponible, une source internationale peut etre utilisee.
5. La page Progress affiche les resultats par pays ou seulement le pays de l'utilisateur connecte.
6. Cliquer sur un pays pour voir le detail des indicateurs evalues, les niveaux du modele UHC Clock et la source de donnees.

## 13. Regions et locations

Ce module gere la structure geographique:

- region;
- pays;
- niveaux administratifs;
- codes;
- groupes de revenu;
- statuts speciaux.

Les champs de type reference doivent etre selectionnes dans les listes deroulantes plutot que saisis comme nombres.

## 14. Authentication, roles et permissions

Ce module gere les utilisateurs, roles et permissions.

Procedure:

1. Ouvrir Authentication > Users pour creer ou modifier un utilisateur.
2. Assigner un pays si l'utilisateur est rattache a un pays.
3. Ouvrir Authentication > Roles and permissions.
4. Definir les modules visibles et les actions autorisees: view, create, update, delete, approve, import, export.
5. Enregistrer.
6. Tester avec un compte non super admin pour confirmer que les menus non autorises sont caches.

## 15. API Tokens

Le module API Tokens permet de preparer l'acces API.

1. Ouvrir API Tokens.
2. Creer un token pour l'integration autorisee.
3. Definir le perimetre d'utilisation.
4. Conserver le token de maniere securisee.
5. Revoquer le token lorsqu'il n'est plus utilise.

## 16. Bonnes pratiques

- Toujours importer les donnees d'abord en attente de validation.
- Approuver uniquement apres verification.
- Utiliser les listes deroulantes pour eviter les erreurs de codes.
- Eviter les doublons en utilisant les templates officiels.
- Verifier les notifications apres chaque import important.
- Ne jamais partager les jetons API, mots de passe ou liens prives en dehors des canaux autorises.
