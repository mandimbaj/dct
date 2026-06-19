<?php

namespace Database\Seeders;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FacilityReferenceDataSeeder extends Seeder
{
    /**
     * Seed facility provision units and multilingual facility-owner labels.
     *
     * Provision units follow the WHO SARA v2.2 Core Instrument sections on
     * beds, infrastructure, available services, diagnostics and equipment.
     */
    public function run(): void
    {
        $connection = DB::connection('warehouse');

        $connection->transaction(function () use ($connection): void {
            $domainIds = $this->domainIds($connection);

            foreach ($this->provisionUnits() as $unit) {
                $unitId = $this->upsertProvisionUnit(
                    connection: $connection,
                    sourceCode: $unit['code'],
                    domainId: $domainIds[$unit['domain']],
                );

                $this->upsertTranslations(
                    connection: $connection,
                    table: 'stg_facility_service_units_translation',
                    masterId: $unitId,
                    sourceCode: $unit['code'],
                    translations: $unit['translations'],
                    descriptions: [
                        'en' => 'Quantifiable service provision unit aligned with the WHO SARA v2.2 facility assessment.',
                        'fr' => "Unité quantifiable de prestation de services alignée sur l'évaluation OMS SARA v2.2.",
                        'pt' => 'Unidade quantificável de prestação de serviços alinhada com a avaliação OMS SARA v2.2.',
                    ],
                );
            }

            foreach ($this->ownerTranslations() as $ownerCode => $translations) {
                $ownerId = $connection->table('stg_facility_owner')
                    ->where('code', $ownerCode)
                    ->value('owner_id');

                if ($ownerId === null) {
                    throw new RuntimeException("Facility owner {$ownerCode} was not found.");
                }

                $this->upsertTranslations(
                    connection: $connection,
                    table: 'stg_facility_owner_translation',
                    masterId: (int) $ownerId,
                    sourceCode: $ownerCode,
                    translations: $translations,
                    descriptions: array_map(
                        fn (array $translation): string => $translation['description'],
                        $translations,
                    ),
                );
            }

            foreach ($this->facilityTypeTranslations() as $typeCode => $translations) {
                $typeId = $connection->table('stg_facility_type')
                    ->where('code', $typeCode)
                    ->value('type_id');

                if ($typeId === null) {
                    throw new RuntimeException("Facility type {$typeCode} was not found.");
                }

                $this->upsertTranslations(
                    connection: $connection,
                    table: 'stg_facility_type_translation',
                    masterId: (int) $typeId,
                    sourceCode: $typeCode,
                    translations: $translations,
                    descriptions: array_map(
                        fn (array $translation): string => $translation['description'],
                        $translations,
                    ),
                );
            }
        });
    }

    /**
     * @return array<string, int>
     */
    private function domainIds(ConnectionInterface $connection): array
    {
        $domainCodes = [
            'SARA-DOM-RMNCH',
            'SARA-DOM-CHILD-ADOLESCENT',
            'SARA-DOM-COMMUNICABLE',
            'SARA-DOM-NCD',
            'SARA-DOM-SURGERY-BLOOD',
        ];

        $domainIds = [];

        foreach ($domainCodes as $domainCode) {
            $domainId = $connection->table('stg_facility_services_translation')
                ->where('language_code', 'en')
                ->where('shortname', $domainCode.'-EN')
                ->value('master_id');

            if ($domainId === null) {
                throw new RuntimeException("Service domain {$domainCode} was not found. Run FacilityServicesSeeder first.");
            }

            $domainIds[$domainCode] = (int) $domainId;
        }

        return $domainIds;
    }

    private function upsertProvisionUnit(
        ConnectionInterface $connection,
        string $sourceCode,
        int $domainId,
    ): int {
        $unitId = $connection->table('stg_facility_service_units_translation')
            ->where('language_code', 'en')
            ->where('shortname', $sourceCode.'-EN')
            ->value('master_id');

        if ($unitId === null) {
            return (int) $connection->table('stg_facility_service_units')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'code' => $sourceCode,
                'domain_id' => $domainId,
                'date_created' => now(),
                'date_lastupdated' => now(),
            ], 'infra_id');
        }

        $connection->table('stg_facility_service_units')
            ->where('infra_id', $unitId)
            ->update([
                'domain_id' => $domainId,
                'date_lastupdated' => now(),
            ]);

        return (int) $unitId;
    }

    /**
     * @param  array<string, string|array{name: string, description: string}>  $translations
     * @param  array<string, string>  $descriptions
     */
    private function upsertTranslations(
        ConnectionInterface $connection,
        string $table,
        int $masterId,
        string $sourceCode,
        array $translations,
        array $descriptions,
    ): void {
        foreach ($translations as $language => $translation) {
            $name = is_array($translation) ? $translation['name'] : $translation;

            $connection->table($table)->updateOrInsert(
                [
                    'master_id' => $masterId,
                    'language_code' => $language,
                ],
                [
                    'name' => $name,
                    'shortname' => $sourceCode.'-'.strtoupper($language),
                    'description' => $descriptions[$language],
                ],
            );
        }
    }

    private function provisionUnits(): array
    {
        return [
            $this->unit('SARA-DOM-RMNCH', 'SARA-UNIT-ANC-ROOM', 'Antenatal care consultation room', 'Salle de consultation prénatale', 'Sala de consulta pré-natal'),
            $this->unit('SARA-DOM-RMNCH', 'SARA-UNIT-DELIVERY-ROOM', 'Delivery room', "Salle d'accouchement", 'Sala de partos'),
            $this->unit('SARA-DOM-RMNCH', 'SARA-UNIT-DELIVERY-BED', 'Delivery bed', "Lit d'accouchement", 'Cama de parto'),
            $this->unit('SARA-DOM-RMNCH', 'SARA-UNIT-MATERNITY-BED', 'Maternity inpatient bed', "Lit d'hospitalisation en maternité", 'Cama de internamento de maternidade'),
            $this->unit('SARA-DOM-RMNCH', 'SARA-UNIT-CAESAREAN-THEATRE', 'Caesarean operating theatre', "Bloc opératoire pour césarienne", 'Bloco operatório para cesariana'),
            $this->unit('SARA-DOM-RMNCH', 'SARA-UNIT-NEONATAL-RESUSCITATION', 'Neonatal resuscitation station', 'Poste de réanimation néonatale', 'Posto de reanimação neonatal'),

            $this->unit('SARA-DOM-CHILD-ADOLESCENT', 'SARA-UNIT-PAEDIATRIC-ROOM', 'Paediatric consultation room', 'Salle de consultation pédiatrique', 'Sala de consulta pediátrica'),
            $this->unit('SARA-DOM-CHILD-ADOLESCENT', 'SARA-UNIT-PAEDIATRIC-BED', 'Paediatric inpatient bed', "Lit d'hospitalisation pédiatrique", 'Cama de internamento pediátrico'),
            $this->unit('SARA-DOM-CHILD-ADOLESCENT', 'SARA-UNIT-IMMUNIZATION-POINT', 'Immunization service point', 'Point de prestation de vaccination', 'Ponto de prestação de vacinação'),
            $this->unit('SARA-DOM-CHILD-ADOLESCENT', 'SARA-UNIT-VACCINE-REFRIGERATOR', 'Functional vaccine refrigerator', 'Réfrigérateur à vaccins fonctionnel', 'Frigorífico de vacinas funcional'),
            $this->unit('SARA-DOM-CHILD-ADOLESCENT', 'SARA-UNIT-ADOLESCENT-ROOM', 'Adolescent-friendly service room', 'Salle de services adaptés aux adolescents', 'Sala de serviços adaptados aos adolescentes'),

            $this->unit('SARA-DOM-COMMUNICABLE', 'SARA-UNIT-HIV-TESTING-ROOM', 'HIV counselling and testing room', 'Salle de conseil et dépistage du VIH', 'Sala de aconselhamento e teste do VIH'),
            $this->unit('SARA-DOM-COMMUNICABLE', 'SARA-UNIT-ART-POINT', 'Antiretroviral treatment service point', 'Point de prestation du traitement antirétroviral', 'Ponto de prestação de tratamento antirretroviral'),
            $this->unit('SARA-DOM-COMMUNICABLE', 'SARA-UNIT-TB-DIAGNOSTIC', 'Tuberculosis diagnostic station', 'Poste de diagnostic de la tuberculose', 'Posto de diagnóstico da tuberculose'),
            $this->unit('SARA-DOM-COMMUNICABLE', 'SARA-UNIT-GENEXPERT', 'GeneXpert machine', 'Appareil GeneXpert', 'Equipamento GeneXpert'),
            $this->unit('SARA-DOM-COMMUNICABLE', 'SARA-UNIT-MALARIA-DIAGNOSTIC', 'Malaria diagnostic station', 'Poste de diagnostic du paludisme', 'Posto de diagnóstico da malária'),
            $this->unit('SARA-DOM-COMMUNICABLE', 'SARA-UNIT-ISOLATION-BED', 'Isolation bed', "Lit d'isolement", 'Cama de isolamento'),

            $this->unit('SARA-DOM-NCD', 'SARA-UNIT-NCD-ROOM', 'Noncommunicable disease consultation room', 'Salle de consultation des maladies non transmissibles', 'Sala de consulta de doenças não transmissíveis'),
            $this->unit('SARA-DOM-NCD', 'SARA-UNIT-BLOOD-PRESSURE', 'Blood pressure measurement station', 'Poste de mesure de la pression artérielle', 'Posto de medição da pressão arterial'),
            $this->unit('SARA-DOM-NCD', 'SARA-UNIT-BLOOD-GLUCOSE', 'Blood glucose testing station', 'Poste de mesure de la glycémie', 'Posto de medição da glicemia'),
            $this->unit('SARA-DOM-NCD', 'SARA-UNIT-RESPIRATORY-ASSESSMENT', 'Respiratory assessment station', "Poste d'évaluation respiratoire", 'Posto de avaliação respiratória'),
            $this->unit('SARA-DOM-NCD', 'SARA-UNIT-CERVICAL-SCREENING', 'Cervical cancer screening station', 'Poste de dépistage du cancer du col de l’utérus', 'Posto de rastreio do cancro do colo do útero'),

            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-MINOR-SURGERY-ROOM', 'Minor surgery room', 'Salle de petite chirurgie', 'Sala de pequena cirurgia'),
            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-OPERATING-THEATRE', 'Operating theatre', 'Bloc opératoire', 'Bloco operatório'),
            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-ANAESTHESIA-MACHINE', 'Anaesthesia machine', "Appareil d'anesthésie", 'Máquina de anestesia'),
            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-OXYGEN-DELIVERY', 'Functional oxygen delivery unit', "Unité fonctionnelle d'administration d'oxygène", 'Unidade funcional de administração de oxigénio'),
            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-RECOVERY-BED', 'Post-anaesthesia recovery bed', 'Lit de salle de réveil post-anesthésique', 'Cama de recobro pós-anestésico'),
            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-BLOOD-REFRIGERATOR', 'Blood bank refrigerator', 'Réfrigérateur de banque de sang', 'Frigorífico de banco de sangue'),
            $this->unit('SARA-DOM-SURGERY-BLOOD', 'SARA-UNIT-TRANSFUSION-POINT', 'Blood transfusion service point', 'Point de prestation de transfusion sanguine', 'Ponto de prestação de transfusão de sangue'),
        ];
    }

    private function ownerTranslations(): array
    {
        return [
            'DOWN0001' => $this->owner('Établissement public - Ministère de la Santé', 'Hôpitaux et établissements publics gérés ou financés par le gouvernement via le ministère de la Santé.', 'Unidade pública - Ministério da Saúde', 'Hospitais e unidades públicas geridos ou financiados pelo governo através do Ministério da Saúde.'),
            'DOWN0002' => $this->owner('Établissement public - Organisme parapublic ou autonome', 'Établissements appartenant à un organisme parapublic ou gouvernemental semi-autonome.', 'Unidade pública - Organismo paraestatal ou autónomo', 'Unidades pertencentes a um organismo paraestatal ou governamental semiautónomo.'),
            'DOWN0003' => $this->owner('Établissement privé confessionnel - Chrétien', 'Établissements de santé appartenant à des églises ou organisations chrétiennes.', 'Unidade privada confessional - Cristã', 'Unidades de saúde pertencentes a igrejas ou organizações cristãs.'),
            'DOWN0004' => $this->owner('Établissement privé confessionnel - Musulman', 'Établissements de santé appartenant à des organisations confessionnelles musulmanes.', 'Unidade privada confessional - Muçulmana', 'Unidades de saúde pertencentes a organizações confessionais muçulmanas.'),
            'DOWN0005' => $this->owner('Établissement privé confessionnel - Autre', "Établissements appartenant à d'autres organisations confessionnelles ou traditionnelles.", 'Unidade privada confessional - Outra', 'Unidades pertencentes a outras organizações confessionais ou tradicionais.'),
            'DOWN0006' => $this->owner('Établissement privé à but lucratif', 'Établissement de santé créé par une organisation commerciale privée.', 'Unidade privada com fins lucrativos', 'Unidade de saúde criada por uma organização comercial privada.'),
            'DOWN0007' => $this->owner('Établissement privé à but non lucratif', 'Établissement de santé créé pour fournir des services sans objectif commercial.', 'Unidade privada sem fins lucrativos', 'Unidade de saúde criada para prestar serviços sem objetivo comercial.'),
            'DOWN0008' => $this->owner('Organisation non gouvernementale', 'Établissement de santé créé ou géré par une organisation non gouvernementale.', 'Organização não governamental', 'Unidade de saúde criada ou gerida por uma organização não governamental.'),
            'DOWN0009' => $this->owner('Établissement public - Autre ministère', "Établissement créé par une autre administration publique, par exemple le ministère de la Défense.", 'Unidade pública - Outro ministério', 'Unidade criada por outra administração pública, por exemplo o Ministério da Defesa.'),
            'DOWN0010' => $this->owner('Organisation communautaire', 'Établissement géré par la communauté locale.', 'Organização de base comunitária', 'Unidade gerida pela comunidade local.'),
            'DOWN0011' => $this->owner('Autre propriétaire', "Type de propriété de l'établissement non défini dans les catégories précédentes.", 'Outro proprietário', 'Tipo de propriedade da unidade não definido nas categorias anteriores.'),
            'DOWN0012' => $this->owner('Organisation confessionnelle ou non gouvernementale', 'Établissement appartenant à une organisation confessionnelle ou non gouvernementale.', 'Organização confessional ou não governamental', 'Unidade pertencente a uma organização confessional ou não governamental.'),
        ];
    }

    private function facilityTypeTranslations(): array
    {
        return [
            'DFTY0001' => $this->owner(
                'Établissement de soins primaires',
                'Présence de services généraux de consultation externe.',
                'Unidade de cuidados de saúde primários',
                'Presença de serviços gerais de consulta externa.',
            ),
            'DFTY0002' => $this->owner(
                'Établissement de référence de premier niveau',
                "Présence d'au moins une des capacités spécialisées suivantes : médecine interne, chirurgie générale, pédiatrie ou obstétrique-gynécologie.",
                'Unidade de referência de primeiro nível',
                'Presença de pelo menos uma das seguintes capacidades especializadas: medicina interna, cirurgia geral, pediatria ou obstetrícia-ginecologia.',
            ),
            'DFTY0003' => $this->owner(
                'Établissement de référence de deuxième niveau',
                "Présence d'au moins une des composantes suivantes : services médicaux spécialisés, formation diplômante ou spécialisée avant l'emploi, stage de cursus diplômant ou recherche opérationnelle.",
                'Unidade de referência de segundo nível',
                'Presença de pelo menos uma das seguintes componentes: serviços médicos especializados, formação pré-serviço superior ou especializada, estágio de curso superior ou investigação operacional.',
            ),
            'DFTY0004' => $this->owner(
                'Établissement de référence de troisième niveau',
                "Établissement réunissant les quatre composantes suivantes : services médicaux spécialisés, formation diplômante ou spécialisée avant l'emploi, stage de cursus diplômant et recherche opérationnelle.",
                'Unidade de referência de terceiro nível',
                'Unidade que reúne as quatro componentes seguintes: serviços médicos especializados, formação pré-serviço superior ou especializada, estágio de curso superior e investigação operacional.',
            ),
            'DFTY0005' => $this->owner(
                'Autre établissement de santé',
                'Absence de service général de consultation externe.',
                'Outra unidade de saúde',
                'Ausência de serviço geral de consulta externa.',
            ),
        ];
    }

    private function unit(string $domain, string $code, string $en, string $fr, string $pt): array
    {
        return [
            'domain' => $domain,
            'code' => $code,
            'translations' => compact('en', 'fr', 'pt'),
        ];
    }

    private function owner(string $frName, string $frDescription, string $ptName, string $ptDescription): array
    {
        return [
            'fr' => ['name' => $frName, 'description' => $frDescription],
            'pt' => ['name' => $ptName, 'description' => $ptDescription],
        ];
    }
}
