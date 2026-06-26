<?php

namespace Database\Seeders;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FontAwesomeIconSeeder extends Seeder
{
    public function run(): void
    {
        $connection = DB::connection('warehouse');

        $connection->transaction(function () use ($connection): void {
            foreach ($this->icons() as $icon) {
                $iconId = $this->upsertIcon($connection, $icon);

                foreach (['en', 'fr', 'pt'] as $language) {
                    $connection->table('stg_fontawesome_icons_translation')->updateOrInsert(
                        [
                            'master_id' => $iconId,
                            'language_code' => $language,
                        ],
                        [
                            'name' => $icon[$language],
                            'shortname' => $icon['shortname'],
                            'description' => $this->description($language, $icon),
                        ],
                    );
                }
            }
        });
    }

    /**
     * @param  array<string, string>  $icon
     */
    private function upsertIcon(ConnectionInterface $connection, array $icon): int
    {
        $now = now();

        $existingId = $connection->table('stg_fontawesome_icons')
            ->where('code', $icon['code'])
            ->value('icon_id');

        if ($existingId !== null) {
            $connection->table('stg_fontawesome_icons')
                ->where('icon_id', $existingId)
                ->update([
                    'unicode' => $icon['unicode'],
                    'version' => $icon['version'],
                    'date_lastupdated' => $now,
                ]);

            return (int) $existingId;
        }

        return (int) $connection->table('stg_fontawesome_icons')->insertGetId([
            'unicode' => $icon['unicode'],
            'code' => $icon['code'],
            'version' => $icon['version'],
            'date_created' => $now,
            'date_lastupdated' => $now,
        ]);
    }

    /**
     * @param  array<string, string>  $icon
     */
    private function description(string $language, array $icon): string
    {
        return match ($language) {
            'fr' => "Icône Font Awesome {$icon['version']} pour {$icon['fr']}. Usage recommandé : {$icon['usage_fr']}.",
            'pt' => "Ícone Font Awesome {$icon['version']} para {$icon['pt']}. Uso recomendado: {$icon['usage_pt']}.",
            default => "Font Awesome {$icon['version']} icon for {$icon['en']}. Recommended use: {$icon['usage_en']}.",
        };
    }

    /**
     * Coherent Font Awesome Free icon set for health data capture, references,
     * dashboards, integrations, security and common admin actions.
     *
     * @return array<int, array<string, string>>
     */
    private function icons(): array
    {
        $version = '6 Free';

        return [
            ['shortname' => 'hospital', 'code' => 'fa-solid fa-hospital', 'unicode' => 'f0f8', 'version' => $version, 'en' => 'Hospital', 'fr' => 'Hôpital', 'pt' => 'Hospital', 'usage_en' => 'health facilities and hospital services', 'usage_fr' => 'établissements de santé et services hospitaliers', 'usage_pt' => 'unidades de saúde e serviços hospitalares'],
            ['shortname' => 'user-doctor', 'code' => 'fa-solid fa-user-doctor', 'unicode' => 'f0f0', 'version' => $version, 'en' => 'Doctor', 'fr' => 'Médecin', 'pt' => 'Médico', 'usage_en' => 'medical staff and clinical workforce', 'usage_fr' => 'personnel médical et effectifs cliniques', 'usage_pt' => 'profissionais médicos e força de trabalho clínica'],
            ['shortname' => 'stethoscope', 'code' => 'fa-solid fa-stethoscope', 'unicode' => 'f0f1', 'version' => $version, 'en' => 'Stethoscope', 'fr' => 'Stéthoscope', 'pt' => 'Estetoscópio', 'usage_en' => 'clinical services and consultations', 'usage_fr' => 'services cliniques et consultations', 'usage_pt' => 'serviços clínicos e consultas'],
            ['shortname' => 'kit-medical', 'code' => 'fa-solid fa-kit-medical', 'unicode' => 'f479', 'version' => $version, 'en' => 'Medical kit', 'fr' => 'Trousse médicale', 'pt' => 'Kit médico', 'usage_en' => 'emergency readiness and first aid', 'usage_fr' => 'préparation aux urgences et premiers secours', 'usage_pt' => 'prontidão para emergências e primeiros socorros'],
            ['shortname' => 'syringe', 'code' => 'fa-solid fa-syringe', 'unicode' => 'f48e', 'version' => $version, 'en' => 'Syringe', 'fr' => 'Seringue', 'pt' => 'Seringa', 'usage_en' => 'vaccination and injections', 'usage_fr' => 'vaccination et injections', 'usage_pt' => 'vacinação e injeções'],
            ['shortname' => 'pills', 'code' => 'fa-solid fa-pills', 'unicode' => 'f484', 'version' => $version, 'en' => 'Medicines', 'fr' => 'Médicaments', 'pt' => 'Medicamentos', 'usage_en' => 'pharmaceutical services and medicines', 'usage_fr' => 'services pharmaceutiques et médicaments', 'usage_pt' => 'serviços farmacêuticos e medicamentos'],
            ['shortname' => 'vial', 'code' => 'fa-solid fa-vial', 'unicode' => 'f492', 'version' => $version, 'en' => 'Laboratory vial', 'fr' => 'Flacon de laboratoire', 'pt' => 'Frasco de laboratório', 'usage_en' => 'laboratory tests and samples', 'usage_fr' => 'tests de laboratoire et échantillons', 'usage_pt' => 'testes laboratoriais e amostras'],
            ['shortname' => 'microscope', 'code' => 'fa-solid fa-microscope', 'unicode' => 'f610', 'version' => $version, 'en' => 'Microscope', 'fr' => 'Microscope', 'pt' => 'Microscópio', 'usage_en' => 'laboratory capacity and diagnostics', 'usage_fr' => 'capacité de laboratoire et diagnostics', 'usage_pt' => 'capacidade laboratorial e diagnósticos'],
            ['shortname' => 'heart-pulse', 'code' => 'fa-solid fa-heart-pulse', 'unicode' => 'f21e', 'version' => $version, 'en' => 'Health pulse', 'fr' => 'Pouls de santé', 'pt' => 'Pulso de saúde', 'usage_en' => 'health indicators and vital statistics', 'usage_fr' => 'indicateurs de santé et statistiques vitales', 'usage_pt' => 'indicadores de saúde e estatísticas vitais'],
            ['shortname' => 'bed-pulse', 'code' => 'fa-solid fa-bed-pulse', 'unicode' => 'f487', 'version' => $version, 'en' => 'Hospital bed', 'fr' => 'Lit d’hôpital', 'pt' => 'Leito hospitalar', 'usage_en' => 'beds, inpatient capacity and service readiness', 'usage_fr' => 'lits, capacité d’hospitalisation et préparation des services', 'usage_pt' => 'leitos, capacidade de internamento e prontidão dos serviços'],
            ['shortname' => 'notes-medical', 'code' => 'fa-solid fa-notes-medical', 'unicode' => 'f481', 'version' => $version, 'en' => 'Medical notes', 'fr' => 'Notes médicales', 'pt' => 'Notas médicas', 'usage_en' => 'clinical forms and medical records', 'usage_fr' => 'formulaires cliniques et dossiers médicaux', 'usage_pt' => 'formulários clínicos e registos médicos'],
            ['shortname' => 'file-medical', 'code' => 'fa-solid fa-file-medical', 'unicode' => 'f477', 'version' => $version, 'en' => 'Medical file', 'fr' => 'Fichier médical', 'pt' => 'Ficheiro médico', 'usage_en' => 'medical documents and health records', 'usage_fr' => 'documents médicaux et dossiers de santé', 'usage_pt' => 'documentos médicos e registos de saúde'],
            ['shortname' => 'house-medical', 'code' => 'fa-solid fa-house-medical', 'unicode' => 'e3b2', 'version' => $version, 'en' => 'Health post', 'fr' => 'Poste de santé', 'pt' => 'Posto de saúde', 'usage_en' => 'primary health care sites', 'usage_fr' => 'sites de soins de santé primaires', 'usage_pt' => 'locais de cuidados de saúde primários'],
            ['shortname' => 'truck-medical', 'code' => 'fa-solid fa-truck-medical', 'unicode' => 'f0f9', 'version' => $version, 'en' => 'Ambulance', 'fr' => 'Ambulance', 'pt' => 'Ambulância', 'usage_en' => 'emergency transport and referral services', 'usage_fr' => 'transport d’urgence et services de référence', 'usage_pt' => 'transporte de emergência e serviços de referência'],
            ['shortname' => 'briefcase-medical', 'code' => 'fa-solid fa-briefcase-medical', 'unicode' => 'f469', 'version' => $version, 'en' => 'Medical briefcase', 'fr' => 'Mallette médicale', 'pt' => 'Mala médica', 'usage_en' => 'service packages and clinical equipment', 'usage_fr' => 'paquets de services et équipement clinique', 'usage_pt' => 'pacotes de serviços e equipamento clínico'],
            ['shortname' => 'staff-snake', 'code' => 'fa-solid fa-staff-snake', 'unicode' => 'e579', 'version' => $version, 'en' => 'Medical symbol', 'fr' => 'Symbole médical', 'pt' => 'Símbolo médico', 'usage_en' => 'general health modules and medical references', 'usage_fr' => 'modules de santé généraux et références médicales', 'usage_pt' => 'módulos gerais de saúde e referências médicas'],
            ['shortname' => 'virus', 'code' => 'fa-solid fa-virus', 'unicode' => 'e074', 'version' => $version, 'en' => 'Virus', 'fr' => 'Virus', 'pt' => 'Vírus', 'usage_en' => 'communicable diseases and outbreak monitoring', 'usage_fr' => 'maladies transmissibles et surveillance des flambées', 'usage_pt' => 'doenças transmissíveis e monitorização de surtos'],
            ['shortname' => 'lungs', 'code' => 'fa-solid fa-lungs', 'unicode' => 'f604', 'version' => $version, 'en' => 'Lungs', 'fr' => 'Poumons', 'pt' => 'Pulmões', 'usage_en' => 'respiratory health indicators', 'usage_fr' => 'indicateurs de santé respiratoire', 'usage_pt' => 'indicadores de saúde respiratória'],
            ['shortname' => 'tooth', 'code' => 'fa-solid fa-tooth', 'unicode' => 'f5c9', 'version' => $version, 'en' => 'Dental health', 'fr' => 'Santé dentaire', 'pt' => 'Saúde dentária', 'usage_en' => 'oral health services', 'usage_fr' => 'services de santé bucco-dentaire', 'usage_pt' => 'serviços de saúde oral'],
            ['shortname' => 'baby', 'code' => 'fa-solid fa-baby', 'unicode' => 'f77c', 'version' => $version, 'en' => 'Child health', 'fr' => 'Santé de l’enfant', 'pt' => 'Saúde infantil', 'usage_en' => 'maternal and child health indicators', 'usage_fr' => 'indicateurs de santé maternelle et infantile', 'usage_pt' => 'indicadores de saúde materna e infantil'],
            ['shortname' => 'person-pregnant', 'code' => 'fa-solid fa-person-pregnant', 'unicode' => 'e31e', 'version' => $version, 'en' => 'Maternal health', 'fr' => 'Santé maternelle', 'pt' => 'Saúde materna', 'usage_en' => 'pregnancy and maternal health services', 'usage_fr' => 'grossesse et services de santé maternelle', 'usage_pt' => 'gravidez e serviços de saúde materna'],
            ['shortname' => 'wheelchair', 'code' => 'fa-solid fa-wheelchair', 'unicode' => 'f193', 'version' => $version, 'en' => 'Accessibility', 'fr' => 'Accessibilité', 'pt' => 'Acessibilidade', 'usage_en' => 'disability and accessibility indicators', 'usage_fr' => 'handicap et indicateurs d’accessibilité', 'usage_pt' => 'deficiência e indicadores de acessibilidade'],
            ['shortname' => 'prescription-bottle-medical', 'code' => 'fa-solid fa-prescription-bottle-medical', 'unicode' => 'f486', 'version' => $version, 'en' => 'Prescription medicine', 'fr' => 'Médicament prescrit', 'pt' => 'Medicamento prescrito', 'usage_en' => 'medicine availability and pharmacy readiness', 'usage_fr' => 'disponibilité des médicaments et préparation pharmaceutique', 'usage_pt' => 'disponibilidade de medicamentos e prontidão farmacêutica'],
            ['shortname' => 'thermometer', 'code' => 'fa-solid fa-thermometer', 'unicode' => 'f491', 'version' => $version, 'en' => 'Thermometer', 'fr' => 'Thermomètre', 'pt' => 'Termómetro', 'usage_en' => 'equipment and clinical measurements', 'usage_fr' => 'équipement et mesures cliniques', 'usage_pt' => 'equipamento e medições clínicas'],
            ['shortname' => 'weight-scale', 'code' => 'fa-solid fa-weight-scale', 'unicode' => 'f496', 'version' => $version, 'en' => 'Scale', 'fr' => 'Balance', 'pt' => 'Balança', 'usage_en' => 'anthropometry and nutrition measures', 'usage_fr' => 'anthropométrie et mesures nutritionnelles', 'usage_pt' => 'antropometria e medidas nutricionais'],
            ['shortname' => 'chart-line', 'code' => 'fa-solid fa-chart-line', 'unicode' => 'f201', 'version' => $version, 'en' => 'Trend chart', 'fr' => 'Graphique de tendance', 'pt' => 'Gráfico de tendência', 'usage_en' => 'indicator trends and time series', 'usage_fr' => 'tendances des indicateurs et séries temporelles', 'usage_pt' => 'tendências de indicadores e séries temporais'],
            ['shortname' => 'chart-pie', 'code' => 'fa-solid fa-chart-pie', 'unicode' => 'f200', 'version' => $version, 'en' => 'Pie chart', 'fr' => 'Graphique circulaire', 'pt' => 'Gráfico circular', 'usage_en' => 'distribution charts and dashboard summaries', 'usage_fr' => 'graphiques de distribution et synthèses du dashboard', 'usage_pt' => 'gráficos de distribuição e resumos do painel'],
            ['shortname' => 'chart-simple', 'code' => 'fa-solid fa-chart-simple', 'unicode' => 'e473', 'version' => $version, 'en' => 'Simple chart', 'fr' => 'Graphique simple', 'pt' => 'Gráfico simples', 'usage_en' => 'compact data visualisations', 'usage_fr' => 'visualisations compactes des données', 'usage_pt' => 'visualizações compactas de dados'],
            ['shortname' => 'database', 'code' => 'fa-solid fa-database', 'unicode' => 'f1c0', 'version' => $version, 'en' => 'Database', 'fr' => 'Base de données', 'pt' => 'Base de dados', 'usage_en' => 'data sources and warehouse tables', 'usage_fr' => 'sources de données et tables warehouse', 'usage_pt' => 'fontes de dados e tabelas do armazém'],
            ['shortname' => 'table', 'code' => 'fa-solid fa-table', 'unicode' => 'f0ce', 'version' => $version, 'en' => 'Table', 'fr' => 'Tableau', 'pt' => 'Tabela', 'usage_en' => 'tabular records and reference lists', 'usage_fr' => 'enregistrements tabulaires et listes de référence', 'usage_pt' => 'registos tabulares e listas de referência'],
            ['shortname' => 'file-csv', 'code' => 'fa-solid fa-file-csv', 'unicode' => 'f6dd', 'version' => $version, 'en' => 'CSV file', 'fr' => 'Fichier CSV', 'pt' => 'Ficheiro CSV', 'usage_en' => 'CSV imports and exports', 'usage_fr' => 'imports et exports CSV', 'usage_pt' => 'importações e exportações CSV'],
            ['shortname' => 'file-excel', 'code' => 'fa-solid fa-file-excel', 'unicode' => 'f1c3', 'version' => $version, 'en' => 'Excel file', 'fr' => 'Fichier Excel', 'pt' => 'Ficheiro Excel', 'usage_en' => 'Excel exports and spreadsheet data', 'usage_fr' => 'exports Excel et données de feuilles de calcul', 'usage_pt' => 'exportações Excel e dados em folhas de cálculo'],
            ['shortname' => 'download', 'code' => 'fa-solid fa-download', 'unicode' => 'f019', 'version' => $version, 'en' => 'Download', 'fr' => 'Téléchargement', 'pt' => 'Transferência', 'usage_en' => 'downloads and exports', 'usage_fr' => 'téléchargements et exports', 'usage_pt' => 'transferências e exportações'],
            ['shortname' => 'upload', 'code' => 'fa-solid fa-upload', 'unicode' => 'f093', 'version' => $version, 'en' => 'Upload', 'fr' => 'Chargement', 'pt' => 'Carregamento', 'usage_en' => 'uploads and data imports', 'usage_fr' => 'chargements et imports de données', 'usage_pt' => 'carregamentos e importações de dados'],
            ['shortname' => 'rotate', 'code' => 'fa-solid fa-rotate', 'unicode' => 'f2f1', 'version' => $version, 'en' => 'Refresh', 'fr' => 'Actualiser', 'pt' => 'Atualizar', 'usage_en' => 'refreshing dashboards and integrations', 'usage_fr' => 'actualisation des dashboards et intégrations', 'usage_pt' => 'atualização de painéis e integrações'],
            ['shortname' => 'filter', 'code' => 'fa-solid fa-filter', 'unicode' => 'f0b0', 'version' => $version, 'en' => 'Filter', 'fr' => 'Filtre', 'pt' => 'Filtro', 'usage_en' => 'table filters and data selection', 'usage_fr' => 'filtres de table et sélection de données', 'usage_pt' => 'filtros de tabela e seleção de dados'],
            ['shortname' => 'magnifying-glass', 'code' => 'fa-solid fa-magnifying-glass', 'unicode' => 'f002', 'version' => $version, 'en' => 'Search', 'fr' => 'Recherche', 'pt' => 'Pesquisa', 'usage_en' => 'global search and table search', 'usage_fr' => 'recherche globale et recherche dans les tables', 'usage_pt' => 'pesquisa global e pesquisa em tabelas'],
            ['shortname' => 'circle-check', 'code' => 'fa-solid fa-circle-check', 'unicode' => 'f058', 'version' => $version, 'en' => 'Approved', 'fr' => 'Approuvé', 'pt' => 'Aprovado', 'usage_en' => 'approved records and successful checks', 'usage_fr' => 'enregistrements approuvés et contrôles réussis', 'usage_pt' => 'registos aprovados e verificações bem-sucedidas'],
            ['shortname' => 'circle-xmark', 'code' => 'fa-solid fa-circle-xmark', 'unicode' => 'f057', 'version' => $version, 'en' => 'Rejected', 'fr' => 'Rejeté', 'pt' => 'Rejeitado', 'usage_en' => 'rejected records and failed checks', 'usage_fr' => 'enregistrements rejetés et contrôles échoués', 'usage_pt' => 'registos rejeitados e verificações falhadas'],
            ['shortname' => 'triangle-exclamation', 'code' => 'fa-solid fa-triangle-exclamation', 'unicode' => 'f071', 'version' => $version, 'en' => 'Warning', 'fr' => 'Avertissement', 'pt' => 'Aviso', 'usage_en' => 'data quality warnings and validation alerts', 'usage_fr' => 'alertes de qualité des données et validation', 'usage_pt' => 'avisos de qualidade de dados e validação'],
            ['shortname' => 'clipboard-check', 'code' => 'fa-solid fa-clipboard-check', 'unicode' => 'f46c', 'version' => $version, 'en' => 'Checklist', 'fr' => 'Liste de contrôle', 'pt' => 'Lista de verificação', 'usage_en' => 'quality checks and validation workflows', 'usage_fr' => 'contrôles qualité et workflows de validation', 'usage_pt' => 'verificações de qualidade e fluxos de validação'],
            ['shortname' => 'clipboard-list', 'code' => 'fa-solid fa-clipboard-list', 'unicode' => 'f46d', 'version' => $version, 'en' => 'Survey list', 'fr' => 'Liste d’enquête', 'pt' => 'Lista de inquérito', 'usage_en' => 'questionnaires, checklists and survey modules', 'usage_fr' => 'questionnaires, listes et modules d’enquête', 'usage_pt' => 'questionários, listas e módulos de inquérito'],
            ['shortname' => 'list-check', 'code' => 'fa-solid fa-list-check', 'unicode' => 'f0ae', 'version' => $version, 'en' => 'Task list', 'fr' => 'Liste de tâches', 'pt' => 'Lista de tarefas', 'usage_en' => 'pending tasks and workflow steps', 'usage_fr' => 'tâches en attente et étapes de workflow', 'usage_pt' => 'tarefas pendentes e etapas do fluxo de trabalho'],
            ['shortname' => 'pen-to-square', 'code' => 'fa-solid fa-pen-to-square', 'unicode' => 'f044', 'version' => $version, 'en' => 'Edit', 'fr' => 'Modifier', 'pt' => 'Editar', 'usage_en' => 'editing records', 'usage_fr' => 'modification des enregistrements', 'usage_pt' => 'edição de registos'],
            ['shortname' => 'trash', 'code' => 'fa-solid fa-trash', 'unicode' => 'f1f8', 'version' => $version, 'en' => 'Delete', 'fr' => 'Supprimer', 'pt' => 'Eliminar', 'usage_en' => 'deleting records with permission', 'usage_fr' => 'suppression des enregistrements avec permission', 'usage_pt' => 'eliminação de registos com permissão'],
            ['shortname' => 'eye', 'code' => 'fa-solid fa-eye', 'unicode' => 'f06e', 'version' => $version, 'en' => 'View', 'fr' => 'Voir', 'pt' => 'Ver', 'usage_en' => 'viewing record details', 'usage_fr' => 'consultation des détails d’un enregistrement', 'usage_pt' => 'visualização dos detalhes de um registo'],
            ['shortname' => 'plus', 'code' => 'fa-solid fa-plus', 'unicode' => '2b', 'version' => $version, 'en' => 'Add', 'fr' => 'Ajouter', 'pt' => 'Adicionar', 'usage_en' => 'creating new records', 'usage_fr' => 'création de nouveaux enregistrements', 'usage_pt' => 'criação de novos registos'],
            ['shortname' => 'minus', 'code' => 'fa-solid fa-minus', 'unicode' => 'f068', 'version' => $version, 'en' => 'Remove', 'fr' => 'Retirer', 'pt' => 'Remover', 'usage_en' => 'removing items from a selection', 'usage_fr' => 'retrait d’éléments d’une sélection', 'usage_pt' => 'remoção de itens de uma seleção'],
            ['shortname' => 'lock', 'code' => 'fa-solid fa-lock', 'unicode' => 'f023', 'version' => $version, 'en' => 'Locked', 'fr' => 'Verrouillé', 'pt' => 'Bloqueado', 'usage_en' => 'secured resources and restricted access', 'usage_fr' => 'ressources sécurisées et accès restreint', 'usage_pt' => 'recursos seguros e acesso restrito'],
            ['shortname' => 'unlock', 'code' => 'fa-solid fa-unlock', 'unicode' => 'f09c', 'version' => $version, 'en' => 'Unlocked', 'fr' => 'Déverrouillé', 'pt' => 'Desbloqueado', 'usage_en' => 'available or unrestricted access', 'usage_fr' => 'accès disponible ou non restreint', 'usage_pt' => 'acesso disponível ou sem restrição'],
            ['shortname' => 'key', 'code' => 'fa-solid fa-key', 'unicode' => 'f084', 'version' => $version, 'en' => 'Key', 'fr' => 'Clé', 'pt' => 'Chave', 'usage_en' => 'API tokens, credentials and permissions', 'usage_fr' => 'tokens API, identifiants et permissions', 'usage_pt' => 'tokens API, credenciais e permissões'],
            ['shortname' => 'shield-halved', 'code' => 'fa-solid fa-shield-halved', 'unicode' => 'f3ed', 'version' => $version, 'en' => 'Security shield', 'fr' => 'Bouclier de sécurité', 'pt' => 'Escudo de segurança', 'usage_en' => 'security settings and protected operations', 'usage_fr' => 'paramètres de sécurité et opérations protégées', 'usage_pt' => 'definições de segurança e operações protegidas'],
            ['shortname' => 'user', 'code' => 'fa-solid fa-user', 'unicode' => 'f007', 'version' => $version, 'en' => 'User', 'fr' => 'Utilisateur', 'pt' => 'Utilizador', 'usage_en' => 'individual user accounts', 'usage_fr' => 'comptes utilisateurs individuels', 'usage_pt' => 'contas individuais de utilizador'],
            ['shortname' => 'users', 'code' => 'fa-solid fa-users', 'unicode' => 'f0c0', 'version' => $version, 'en' => 'Users', 'fr' => 'Utilisateurs', 'pt' => 'Utilizadores', 'usage_en' => 'groups, teams and workforce counts', 'usage_fr' => 'groupes, équipes et effectifs', 'usage_pt' => 'grupos, equipas e efetivos'],
            ['shortname' => 'user-shield', 'code' => 'fa-solid fa-user-shield', 'unicode' => 'f505', 'version' => $version, 'en' => 'Protected user', 'fr' => 'Utilisateur protégé', 'pt' => 'Utilizador protegido', 'usage_en' => 'administrators and secured user roles', 'usage_fr' => 'administrateurs et rôles sécurisés', 'usage_pt' => 'administradores e funções seguras'],
            ['shortname' => 'user-gear', 'code' => 'fa-solid fa-user-gear', 'unicode' => 'f4fe', 'version' => $version, 'en' => 'User settings', 'fr' => 'Paramètres utilisateur', 'pt' => 'Definições do utilizador', 'usage_en' => 'user administration and account settings', 'usage_fr' => 'administration des utilisateurs et paramètres de compte', 'usage_pt' => 'administração de utilizadores e definições de conta'],
            ['shortname' => 'globe', 'code' => 'fa-solid fa-globe', 'unicode' => 'f0ac', 'version' => $version, 'en' => 'Globe', 'fr' => 'Globe', 'pt' => 'Globo', 'usage_en' => 'global or regional views', 'usage_fr' => 'vues globales ou régionales', 'usage_pt' => 'visões globais ou regionais'],
            ['shortname' => 'earth-africa', 'code' => 'fa-solid fa-earth-africa', 'unicode' => 'f57c', 'version' => $version, 'en' => 'Africa region', 'fr' => 'Région Afrique', 'pt' => 'Região Africana', 'usage_en' => 'AFRO regional context and Africa-wide dashboards', 'usage_fr' => 'contexte régional AFRO et dashboards Afrique', 'usage_pt' => 'contexto regional AFRO e painéis de África'],
            ['shortname' => 'location-dot', 'code' => 'fa-solid fa-location-dot', 'unicode' => 'f3c5', 'version' => $version, 'en' => 'Location marker', 'fr' => 'Marqueur de localisation', 'pt' => 'Marcador de localização', 'usage_en' => 'countries, regions and facility locations', 'usage_fr' => 'pays, régions et localisations des établissements', 'usage_pt' => 'países, regiões e localizações de unidades'],
            ['shortname' => 'map', 'code' => 'fa-solid fa-map', 'unicode' => 'f279', 'version' => $version, 'en' => 'Map', 'fr' => 'Carte', 'pt' => 'Mapa', 'usage_en' => 'geographic views and location modules', 'usage_fr' => 'vues géographiques et modules de localisation', 'usage_pt' => 'vistas geográficas e módulos de localização'],
            ['shortname' => 'map-location-dot', 'code' => 'fa-solid fa-map-location-dot', 'unicode' => 'f5a0', 'version' => $version, 'en' => 'Mapped location', 'fr' => 'Localisation cartographiée', 'pt' => 'Localização mapeada', 'usage_en' => 'mapped country or facility points', 'usage_fr' => 'points pays ou établissements cartographiés', 'usage_pt' => 'pontos de país ou unidade mapeados'],
            ['shortname' => 'building', 'code' => 'fa-solid fa-building', 'unicode' => 'f1ad', 'version' => $version, 'en' => 'Institution', 'fr' => 'Institution', 'pt' => 'Instituição', 'usage_en' => 'institutions, owners and administrative bodies', 'usage_fr' => 'institutions, propriétaires et organes administratifs', 'usage_pt' => 'instituições, proprietários e órgãos administrativos'],
            ['shortname' => 'school', 'code' => 'fa-solid fa-school', 'unicode' => 'f549', 'version' => $version, 'en' => 'Training institution', 'fr' => 'Institution de formation', 'pt' => 'Instituição de formação', 'usage_en' => 'health workforce training institutions', 'usage_fr' => 'institutions de formation du personnel de santé', 'usage_pt' => 'instituições de formação da força de trabalho em saúde'],
            ['shortname' => 'language', 'code' => 'fa-solid fa-language', 'unicode' => 'f1ab', 'version' => $version, 'en' => 'Language', 'fr' => 'Langue', 'pt' => 'Idioma', 'usage_en' => 'translations and language selection', 'usage_fr' => 'traductions et sélection de langue', 'usage_pt' => 'traduções e seleção de idioma'],
            ['shortname' => 'envelope', 'code' => 'fa-solid fa-envelope', 'unicode' => 'f0e0', 'version' => $version, 'en' => 'Message', 'fr' => 'Message', 'pt' => 'Mensagem', 'usage_en' => 'messages, emails and notifications', 'usage_fr' => 'messages, courriels et notifications', 'usage_pt' => 'mensagens, emails e notificações'],
            ['shortname' => 'bell', 'code' => 'fa-solid fa-bell', 'unicode' => 'f0f3', 'version' => $version, 'en' => 'Notification', 'fr' => 'Notification', 'pt' => 'Notificação', 'usage_en' => 'alerts and user notifications', 'usage_fr' => 'alertes et notifications utilisateur', 'usage_pt' => 'alertas e notificações do utilizador'],
            ['shortname' => 'house', 'code' => 'fa-solid fa-house', 'unicode' => 'f015', 'version' => $version, 'en' => 'Home', 'fr' => 'Accueil', 'pt' => 'Início', 'usage_en' => 'dashboard home and main entry points', 'usage_fr' => 'accueil du dashboard et points d’entrée principaux', 'usage_pt' => 'início do painel e pontos de entrada principais'],
            ['shortname' => 'book-open', 'code' => 'fa-solid fa-book-open', 'unicode' => 'f518', 'version' => $version, 'en' => 'Publication', 'fr' => 'Publication', 'pt' => 'Publicação', 'usage_en' => 'knowledge products and publications', 'usage_fr' => 'produits de connaissance et publications', 'usage_pt' => 'produtos de conhecimento e publicações'],
            ['shortname' => 'folder-open', 'code' => 'fa-solid fa-folder-open', 'unicode' => 'f07c', 'version' => $version, 'en' => 'Folder', 'fr' => 'Dossier', 'pt' => 'Pasta', 'usage_en' => 'archives, files and grouped resources', 'usage_fr' => 'archives, fichiers et ressources groupées', 'usage_pt' => 'arquivos, ficheiros e recursos agrupados'],
            ['shortname' => 'calendar-days', 'code' => 'fa-solid fa-calendar-days', 'unicode' => 'f073', 'version' => $version, 'en' => 'Calendar', 'fr' => 'Calendrier', 'pt' => 'Calendário', 'usage_en' => 'periods, dates and reporting years', 'usage_fr' => 'périodes, dates et années de rapportage', 'usage_pt' => 'períodos, datas e anos de reporte'],
            ['shortname' => 'clock', 'code' => 'fa-solid fa-clock', 'unicode' => 'f017', 'version' => $version, 'en' => 'Clock', 'fr' => 'Horloge', 'pt' => 'Relógio', 'usage_en' => 'UHC clock and time-based indicators', 'usage_fr' => 'UHC clock et indicateurs temporels', 'usage_pt' => 'UHC clock e indicadores temporais'],
            ['shortname' => 'link', 'code' => 'fa-solid fa-link', 'unicode' => 'f0c1', 'version' => $version, 'en' => 'Link', 'fr' => 'Lien', 'pt' => 'Ligação', 'usage_en' => 'external references and connected systems', 'usage_fr' => 'références externes et systèmes connectés', 'usage_pt' => 'referências externas e sistemas conectados'],
            ['shortname' => 'code', 'code' => 'fa-solid fa-code', 'unicode' => 'f121', 'version' => $version, 'en' => 'Code', 'fr' => 'Code', 'pt' => 'Código', 'usage_en' => 'API, integrations and technical identifiers', 'usage_fr' => 'API, intégrations et identifiants techniques', 'usage_pt' => 'API, integrações e identificadores técnicos'],
            ['shortname' => 'gear', 'code' => 'fa-solid fa-gear', 'unicode' => 'f013', 'version' => $version, 'en' => 'Settings', 'fr' => 'Paramètres', 'pt' => 'Definições', 'usage_en' => 'configuration screens and system settings', 'usage_fr' => 'écrans de configuration et paramètres système', 'usage_pt' => 'ecrãs de configuração e definições do sistema'],
            ['shortname' => 'sliders', 'code' => 'fa-solid fa-sliders', 'unicode' => 'f1de', 'version' => $version, 'en' => 'Controls', 'fr' => 'Contrôles', 'pt' => 'Controlos', 'usage_en' => 'filters, parameters and advanced controls', 'usage_fr' => 'filtres, paramètres et contrôles avancés', 'usage_pt' => 'filtros, parâmetros e controlos avançados'],
        ];
    }
}
