<?php

namespace Database\Seeders;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FacilityServicesSeeder extends Seeder
{
    /**
     * Seed the WHO SARA v2.2 service hierarchy used by the Facilities module.
     *
     * Source: WHO Service Availability and Readiness Assessment (SARA),
     * Reference Manual v2.2, Core Instrument, Section 5 (Available services).
     * https://www.who.int/data/data-collection-tools/service-availability-and-readiness-assessment-(sara)
     */
    public function run(): void
    {
        $connection = DB::connection('warehouse');

        $connection->transaction(function () use ($connection): void {
            $domainIds = [];

            foreach ($this->domains() as $domain) {
                $domainId = $this->upsertMaster(
                    connection: $connection,
                    table: 'stg_facility_services',
                    primaryKey: 'domain_id',
                    code: $domain['code'],
                    identityTranslationTable: 'stg_facility_services_translation',
                    attributes: [
                        'category' => 1,
                        'level' => 'Level 0',
                        'parent_id' => null,
                    ],
                );

                $domainIds[$domain['code']] = $domainId;
                $this->upsertTranslations(
                    connection: $connection,
                    table: 'stg_facility_services_translation',
                    masterId: $domainId,
                    code: $domain['code'],
                    names: $domain['names'],
                    kind: 'domain',
                );
            }

            foreach ($this->interventions() as $intervention) {
                $interventionId = $this->upsertMaster(
                    connection: $connection,
                    table: 'stg_facility_service_intervention',
                    primaryKey: 'intervention_id',
                    code: $intervention['code'],
                    identityTranslationTable: 'stg_facility_service_intervention_translation',
                    attributes: ['domain_id' => $domainIds[$intervention['domain']]],
                );

                $this->upsertTranslations(
                    connection: $connection,
                    table: 'stg_facility_service_intervention_translation',
                    masterId: $interventionId,
                    code: $intervention['code'],
                    names: $intervention['names'],
                    kind: 'intervention',
                );

                foreach ($intervention['areas'] as $area) {
                    $areaId = $this->upsertMaster(
                        connection: $connection,
                        table: 'stg_facility_service_area',
                        primaryKey: 'area_id',
                        code: $area['code'],
                        identityTranslationTable: 'stg_facility_service_area_translation',
                        attributes: ['intervention_id' => $interventionId],
                    );

                    $this->upsertTranslations(
                        connection: $connection,
                        table: 'stg_facility_service_area_translation',
                        masterId: $areaId,
                        code: $area['code'],
                        names: $area['names'],
                        kind: 'area',
                    );
                }
            }
        });
    }

    private function upsertMaster(
        ConnectionInterface $connection,
        string $table,
        string $primaryKey,
        string $code,
        string $identityTranslationTable,
        array $attributes,
    ): int {
        // The legacy warehouse has BEFORE INSERT triggers that replace supplied
        // codes with DFDO/DVEN/DARE codes. Keep the SARA source code in the
        // English translation shortname and use it as the stable seed identity.
        $recordId = $connection->table($identityTranslationTable)
            ->where('language_code', 'en')
            ->where('shortname', $code.'-EN')
            ->value('master_id');

        $recordId ??= $connection->table($table)->where('code', $code)->value($primaryKey);
        $now = now();

        if ($recordId === null) {
            return (int) $connection->table($table)->insertGetId([
                'uuid' => (string) Str::uuid(),
                'code' => $code,
                ...$attributes,
                'date_created' => $now,
                'date_lastupdated' => $now,
            ], $primaryKey);
        }

        $connection->table($table)->where($primaryKey, $recordId)->update([
            ...$attributes,
            'date_lastupdated' => $now,
        ]);

        return (int) $recordId;
    }

    private function upsertTranslations(
        ConnectionInterface $connection,
        string $table,
        int $masterId,
        string $code,
        array $names,
        string $kind,
    ): void {
        $descriptions = [
            'domain' => [
                'en' => 'WHO SARA v2.2 service domain (Core Instrument, Section 5).',
                'fr' => 'Domaine de services OMS SARA v2.2 (instrument de base, section 5).',
                'pt' => 'Domínio de serviços OMS SARA v2.2 (instrumento principal, secção 5).',
            ],
            'intervention' => [
                'en' => 'WHO SARA v2.2 service intervention.',
                'fr' => 'Intervention de service OMS SARA v2.2.',
                'pt' => 'Intervenção de serviço OMS SARA v2.2.',
            ],
            'area' => [
                'en' => 'WHO SARA v2.2 service provision area.',
                'fr' => 'Zone de prestation de services OMS SARA v2.2.',
                'pt' => 'Área de prestação de serviços OMS SARA v2.2.',
            ],
        ];

        foreach ($names as $language => $name) {
            $connection->table($table)->updateOrInsert(
                [
                    'master_id' => $masterId,
                    'language_code' => $language,
                ],
                [
                    'name' => $name,
                    'shortname' => $code.'-'.strtoupper($language),
                    'description' => $descriptions[$kind][$language],
                ],
            );
        }
    }

    private function domains(): array
    {
        return [
            ['code' => 'SARA-DOM-RMNCH', 'names' => $this->t('Reproductive, maternal and newborn health', 'Santé reproductive, maternelle et néonatale', 'Saúde reprodutiva, materna e neonatal')],
            ['code' => 'SARA-DOM-CHILD-ADOLESCENT', 'names' => $this->t('Child and adolescent health', "Santé de l'enfant et de l'adolescent", 'Saúde da criança e do adolescente')],
            ['code' => 'SARA-DOM-COMMUNICABLE', 'names' => $this->t('Communicable diseases', 'Maladies transmissibles', 'Doenças transmissíveis')],
            ['code' => 'SARA-DOM-NCD', 'names' => $this->t('Noncommunicable diseases', 'Maladies non transmissibles', 'Doenças não transmissíveis')],
            ['code' => 'SARA-DOM-SURGERY-BLOOD', 'names' => $this->t('Surgical and blood transfusion services', 'Services chirurgicaux et transfusion sanguine', 'Serviços cirúrgicos e transfusão de sangue')],
        ];
    }

    private function interventions(): array
    {
        return [
            $this->intervention('SARA-DOM-RMNCH', 'SARA-INT-FAMILY-PLANNING', $this->t('Family planning services', 'Services de planification familiale', 'Serviços de planeamento familiar'), [
                $this->area('SARA-AREA-FP-COMBINED-PILL', 'Combined oral contraceptive pills', 'Pilules contraceptives orales combinées', 'Pílulas contracetivas orais combinadas'),
                $this->area('SARA-AREA-FP-PROGESTIN-PILL', 'Progestin-only contraceptive pills', 'Pilules contraceptives progestatives', 'Pílulas contracetivas só de progestagénio'),
                $this->area('SARA-AREA-FP-INJECTABLE', 'Injectable contraceptives', 'Contraceptifs injectables', 'Contracetivos injetáveis'),
                $this->area('SARA-AREA-FP-MALE-CONDOM', 'Male condoms', 'Préservatifs masculins', 'Preservativos masculinos'),
                $this->area('SARA-AREA-FP-FEMALE-CONDOM', 'Female condoms', 'Préservatifs féminins', 'Preservativos femininos'),
                $this->area('SARA-AREA-FP-IUCD', 'Intrauterine contraceptive device', 'Dispositif contraceptif intra-utérin', 'Dispositivo contracetivo intrauterino'),
                $this->area('SARA-AREA-FP-IMPLANTS', 'Contraceptive implants', 'Implants contraceptifs', 'Implantes contracetivos'),
                $this->area('SARA-AREA-FP-CYCLE-BEADS', 'Cycle beads / standard days method', 'Collier du cycle / méthode des jours fixes', 'Colar do ciclo / método dos dias fixos'),
                $this->area('SARA-AREA-FP-EMERGENCY-PILL', 'Emergency contraceptive pills', "Pilules contraceptives d'urgence", 'Pílulas contracetivas de emergência'),
                $this->area('SARA-AREA-FP-MALE-STERILIZATION', 'Male sterilization', 'Stérilisation masculine', 'Esterilização masculina'),
                $this->area('SARA-AREA-FP-FEMALE-STERILIZATION', 'Female sterilization', 'Stérilisation féminine', 'Esterilização feminina'),
            ]),
            $this->intervention('SARA-DOM-RMNCH', 'SARA-INT-ANC', $this->t('Antenatal care services', 'Services de soins prénatals', 'Serviços de cuidados pré-natais'), [
                $this->area('SARA-AREA-ANC-IRON', 'Iron supplementation', 'Supplémentation en fer', 'Suplementação de ferro'),
                $this->area('SARA-AREA-ANC-FOLIC-ACID', 'Folic acid supplementation', 'Supplémentation en acide folique', 'Suplementação de ácido fólico'),
                $this->area('SARA-AREA-ANC-IPTP', 'Intermittent preventive treatment for malaria in pregnancy', 'Traitement préventif intermittent du paludisme pendant la grossesse', 'Tratamento preventivo intermitente da malária na gravidez'),
                $this->area('SARA-AREA-ANC-TETANUS', 'Tetanus toxoid immunization', 'Vaccination antitétanique', 'Vacinação com toxoide tetânico'),
                $this->area('SARA-AREA-ANC-HYPERTENSION', 'Monitoring for hypertensive disorders of pregnancy', 'Surveillance des troubles hypertensifs de la grossesse', 'Vigilância das perturbações hipertensivas da gravidez'),
            ]),
            $this->intervention('SARA-DOM-RMNCH', 'SARA-INT-PMTCT', $this->t('Prevention of mother-to-child transmission of HIV', 'Prévention de la transmission du VIH de la mère à l’enfant', 'Prevenção da transmissão vertical do VIH'), [
                $this->area('SARA-AREA-PMTCT-HIV-TESTING', 'HIV counselling and testing for pregnant women', 'Conseil et dépistage du VIH chez les femmes enceintes', 'Aconselhamento e teste do VIH para grávidas'),
                $this->area('SARA-AREA-PMTCT-MATERNAL-ART', 'Antiretroviral therapy for HIV-positive pregnant women', 'Traitement antirétroviral pour les femmes enceintes vivant avec le VIH', 'Terapia antirretroviral para grávidas que vivem com VIH'),
                $this->area('SARA-AREA-PMTCT-INFANT-PROPHYLAXIS', 'Antiretroviral prophylaxis for HIV-exposed infants', 'Prophylaxie antirétrovirale du nourrisson exposé au VIH', 'Profilaxia antirretroviral para bebés expostos ao VIH'),
                $this->area('SARA-AREA-PMTCT-INFANT-FEEDING', 'Infant feeding counselling for HIV-positive mothers', "Conseils sur l'alimentation du nourrisson pour les mères vivant avec le VIH", 'Aconselhamento sobre alimentação infantil para mães que vivem com VIH'),
            ]),
            $this->intervention('SARA-DOM-RMNCH', 'SARA-INT-BEMONC', $this->t('Basic obstetric and newborn care', 'Soins obstétricaux et néonatals de base', 'Cuidados obstétricos e neonatais básicos'), [
                $this->area('SARA-AREA-BEMONC-ANTIBIOTICS', 'Parenteral antibiotics', 'Antibiotiques parentéraux', 'Antibióticos parentéricos'),
                $this->area('SARA-AREA-BEMONC-UTEROTONICS', 'Parenteral uterotonic medicines', 'Utérotoniques parentéraux', 'Medicamentos uterotónicos parentéricos'),
                $this->area('SARA-AREA-BEMONC-ANTICONVULSANTS', 'Parenteral anticonvulsants', 'Anticonvulsivants parentéraux', 'Anticonvulsivantes parentéricos'),
                $this->area('SARA-AREA-BEMONC-PLACENTA', 'Manual removal of placenta', 'Extraction manuelle du placenta', 'Remoção manual da placenta'),
                $this->area('SARA-AREA-BEMONC-RETAINED-PRODUCTS', 'Removal of retained products of conception', 'Évacuation des produits résiduels de conception', 'Remoção de produtos retidos da conceção'),
                $this->area('SARA-AREA-BEMONC-ASSISTED-DELIVERY', 'Assisted vaginal delivery', 'Accouchement vaginal assisté', 'Parto vaginal assistido'),
                $this->area('SARA-AREA-BEMONC-NEWBORN-RESUSCITATION', 'Neonatal resuscitation', 'Réanimation néonatale', 'Reanimação neonatal'),
            ]),
            $this->intervention('SARA-DOM-RMNCH', 'SARA-INT-CAESAREAN', $this->t('Caesarean section services', 'Services de césarienne', 'Serviços de cesariana'), [
                $this->area('SARA-AREA-CAESAREAN-SURGERY', 'Caesarean section', 'Césarienne', 'Cesariana'),
                $this->area('SARA-AREA-CAESAREAN-ANAESTHESIA', 'Anaesthesia for caesarean section', 'Anesthésie pour césarienne', 'Anestesia para cesariana'),
            ]),

            $this->intervention('SARA-DOM-CHILD-ADOLESCENT', 'SARA-INT-IMMUNIZATION', $this->t('Immunization services', 'Services de vaccination', 'Serviços de vacinação'), [
                $this->area('SARA-AREA-IMM-BIRTH-DOSES', 'Birth-dose vaccines', 'Vaccins administrés à la naissance', 'Vacinas administradas ao nascimento'),
                $this->area('SARA-AREA-IMM-INFANT', 'Infant vaccines (under one year)', "Vaccins du nourrisson (moins d'un an)", 'Vacinas infantis (menos de um ano)'),
                $this->area('SARA-AREA-IMM-ADOLESCENT-ADULT', 'Adolescent and adult vaccines', "Vaccins de l'adolescent et de l'adulte", 'Vacinas para adolescentes e adultos'),
                $this->area('SARA-AREA-IMM-FACILITY', 'Routine immunization at the facility', "Vaccination systématique dans l'établissement", 'Vacinação de rotina na unidade sanitária'),
                $this->area('SARA-AREA-IMM-OUTREACH', 'Outreach immunization', 'Vaccination en stratégie avancée', 'Vacinação em atividade extramuros'),
            ]),
            $this->intervention('SARA-DOM-CHILD-ADOLESCENT', 'SARA-INT-CHILD-CARE', $this->t('Preventive and curative child care', "Soins préventifs et curatifs de l'enfant", 'Cuidados preventivos e curativos da criança'), [
                $this->area('SARA-AREA-CHILD-IMCI', 'Integrated management of childhood illness', "Prise en charge intégrée des maladies de l'enfant", 'Atenção integrada às doenças da infância'),
                $this->area('SARA-AREA-CHILD-GROWTH', 'Growth monitoring', 'Suivi de la croissance', 'Monitorização do crescimento'),
                $this->area('SARA-AREA-CHILD-VITAMIN-A', 'Vitamin A supplementation', 'Supplémentation en vitamine A', 'Suplementação de vitamina A'),
                $this->area('SARA-AREA-CHILD-DIARRHOEA', 'Diagnosis and treatment of childhood diarrhoea', "Diagnostic et traitement de la diarrhée de l'enfant", 'Diagnóstico e tratamento da diarreia infantil'),
                $this->area('SARA-AREA-CHILD-PNEUMONIA', 'Diagnosis and treatment of childhood pneumonia', "Diagnostic et traitement de la pneumonie de l'enfant", 'Diagnóstico e tratamento da pneumonia infantil'),
                $this->area('SARA-AREA-CHILD-MALARIA', 'Treatment of malaria in children', 'Traitement du paludisme chez les enfants', 'Tratamento da malária em crianças'),
            ]),
            $this->intervention('SARA-DOM-CHILD-ADOLESCENT', 'SARA-INT-ADOLESCENT', $this->t('Adolescent health services', "Services de santé de l'adolescent", 'Serviços de saúde do adolescente'), [
                $this->area('SARA-AREA-ADOLESCENT-SRH', 'Adolescent sexual and reproductive health', "Santé sexuelle et reproductive de l'adolescent", 'Saúde sexual e reprodutiva do adolescente'),
                $this->area('SARA-AREA-ADOLESCENT-HIV', 'HIV counselling and testing for adolescents', 'Conseil et dépistage du VIH pour les adolescents', 'Aconselhamento e teste do VIH para adolescentes'),
                $this->area('SARA-AREA-ADOLESCENT-CONTRACEPTION', 'Contraception for adolescents', 'Contraception pour les adolescents', 'Contraceção para adolescentes'),
                $this->area('SARA-AREA-ADOLESCENT-STI', 'STI diagnosis and treatment for adolescents', 'Diagnostic et traitement des IST chez les adolescents', 'Diagnóstico e tratamento de IST em adolescentes'),
            ]),

            $this->intervention('SARA-DOM-COMMUNICABLE', 'SARA-INT-HIV-TESTING', $this->t('HIV counselling and testing', 'Conseil et dépistage du VIH', 'Aconselhamento e teste do VIH'), [
                $this->area('SARA-AREA-HIV-TESTING-FACILITY', 'Facility-based HIV counselling and testing', "Conseil et dépistage du VIH dans l'établissement", 'Aconselhamento e teste do VIH na unidade sanitária'),
                $this->area('SARA-AREA-HIV-PITC', 'Provider-initiated HIV testing and counselling', 'Dépistage et conseil du VIH à l’initiative du prestataire', 'Teste e aconselhamento do VIH iniciado pelo prestador'),
                $this->area('SARA-AREA-HIV-TESTING-OUTREACH', 'Outreach HIV counselling and testing', 'Conseil et dépistage communautaire du VIH', 'Aconselhamento e teste comunitário do VIH'),
            ]),
            $this->intervention('SARA-DOM-COMMUNICABLE', 'SARA-INT-HIV-TREATMENT', $this->t('HIV treatment services', 'Services de traitement du VIH', 'Serviços de tratamento do VIH'), [
                $this->area('SARA-AREA-HIV-ART-INITIATION', 'Antiretroviral therapy initiation', 'Initiation du traitement antirétroviral', 'Iniciação da terapia antirretroviral'),
                $this->area('SARA-AREA-HIV-ART-FOLLOWUP', 'Antiretroviral therapy follow-up', 'Suivi du traitement antirétroviral', 'Seguimento da terapia antirretroviral'),
                $this->area('SARA-AREA-HIV-PAEDIATRIC-ART', 'Paediatric antiretroviral therapy', 'Traitement antirétroviral pédiatrique', 'Terapia antirretroviral pediátrica'),
            ]),
            $this->intervention('SARA-DOM-COMMUNICABLE', 'SARA-INT-HIV-CARE', $this->t('HIV care and support services', "Services de soins et d'appui liés au VIH", 'Serviços de cuidados e apoio ao VIH'), [
                $this->area('SARA-AREA-HIV-COTRIMOXAZOLE', 'Cotrimoxazole prophylaxis', 'Prophylaxie au cotrimoxazole', 'Profilaxia com cotrimoxazol'),
                $this->area('SARA-AREA-HIV-TB-SCREENING', 'Tuberculosis screening for people living with HIV', 'Dépistage de la tuberculose chez les personnes vivant avec le VIH', 'Rastreio da tuberculose em pessoas que vivem com VIH'),
                $this->area('SARA-AREA-HIV-NUTRITION', 'Nutritional support for people living with HIV', 'Appui nutritionnel aux personnes vivant avec le VIH', 'Apoio nutricional para pessoas que vivem com VIH'),
                $this->area('SARA-AREA-HIV-PALLIATIVE', 'Palliative care for people living with HIV', 'Soins palliatifs pour les personnes vivant avec le VIH', 'Cuidados paliativos para pessoas que vivem com VIH'),
                $this->area('SARA-AREA-HIV-FAMILY-PLANNING', 'Family planning counselling for people living with HIV', 'Conseil en planification familiale pour les personnes vivant avec le VIH', 'Aconselhamento em planeamento familiar para pessoas que vivem com VIH'),
            ]),
            $this->intervention('SARA-DOM-COMMUNICABLE', 'SARA-INT-STI', $this->t('Sexually transmitted infection services', 'Services relatifs aux infections sexuellement transmissibles', 'Serviços de infeções sexualmente transmissíveis'), [
                $this->area('SARA-AREA-STI-DIAGNOSIS', 'STI diagnosis', 'Diagnostic des IST', 'Diagnóstico de IST'),
                $this->area('SARA-AREA-STI-TREATMENT', 'STI treatment', 'Traitement des IST', 'Tratamento de IST'),
            ]),
            $this->intervention('SARA-DOM-COMMUNICABLE', 'SARA-INT-TB', $this->t('Tuberculosis services', 'Services de lutte contre la tuberculose', 'Serviços de tuberculose'), [
                $this->area('SARA-AREA-TB-CLINICAL', 'Clinical diagnosis of tuberculosis', 'Diagnostic clinique de la tuberculose', 'Diagnóstico clínico da tuberculose'),
                $this->area('SARA-AREA-TB-SMEAR', 'Sputum smear microscopy', 'Examen microscopique des frottis de crachats', 'Microscopia de esfregaço de expetoração'),
                $this->area('SARA-AREA-TB-CULTURE', 'Tuberculosis culture', 'Culture de la tuberculose', 'Cultura da tuberculose'),
                $this->area('SARA-AREA-TB-GENEXPERT', 'GeneXpert MTB/RIF rapid testing', 'Test rapide GeneXpert MTB/RIF', 'Teste rápido GeneXpert MTB/RIF'),
                $this->area('SARA-AREA-TB-XRAY', 'Chest X-ray for tuberculosis', 'Radiographie thoracique pour la tuberculose', 'Radiografia torácica para tuberculose'),
                $this->area('SARA-AREA-TB-TREATMENT', 'Tuberculosis treatment prescription', 'Prescription du traitement antituberculeux', 'Prescrição do tratamento da tuberculose'),
                $this->area('SARA-AREA-TB-FOLLOWUP', 'Tuberculosis treatment follow-up', 'Suivi du traitement antituberculeux', 'Seguimento do tratamento da tuberculose'),
            ]),
            $this->intervention('SARA-DOM-COMMUNICABLE', 'SARA-INT-MALARIA', $this->t('Malaria services', 'Services de lutte contre le paludisme', 'Serviços de malária'), [
                $this->area('SARA-AREA-MALARIA-CLINICAL', 'Clinical diagnosis of malaria', 'Diagnostic clinique du paludisme', 'Diagnóstico clínico da malária'),
                $this->area('SARA-AREA-MALARIA-RDT', 'Malaria rapid diagnostic testing', 'Test de diagnostic rapide du paludisme', 'Teste rápido de diagnóstico da malária'),
                $this->area('SARA-AREA-MALARIA-MICROSCOPY', 'Malaria microscopy', 'Microscopie du paludisme', 'Microscopia da malária'),
                $this->area('SARA-AREA-MALARIA-TREATMENT', 'Malaria treatment', 'Traitement du paludisme', 'Tratamento da malária'),
                $this->area('SARA-AREA-MALARIA-IPT', 'Intermittent preventive treatment for malaria', 'Traitement préventif intermittent du paludisme', 'Tratamento preventivo intermitente da malária'),
            ]),

            $this->intervention('SARA-DOM-NCD', 'SARA-INT-DIABETES', $this->t('Diabetes services', 'Services de prise en charge du diabète', 'Serviços de diabetes'), [
                $this->area('SARA-AREA-DIABETES-DIAGNOSIS', 'Diabetes diagnosis', 'Diagnostic du diabète', 'Diagnóstico da diabetes'),
                $this->area('SARA-AREA-DIABETES-MANAGEMENT', 'Diabetes management', 'Prise en charge du diabète', 'Gestão da diabetes'),
            ]),
            $this->intervention('SARA-DOM-NCD', 'SARA-INT-CARDIOVASCULAR', $this->t('Cardiovascular disease services', 'Services de prise en charge des maladies cardiovasculaires', 'Serviços de doenças cardiovasculares'), [
                $this->area('SARA-AREA-CVD-DIAGNOSIS', 'Cardiovascular disease diagnosis', 'Diagnostic des maladies cardiovasculaires', 'Diagnóstico de doenças cardiovasculares'),
                $this->area('SARA-AREA-CVD-MANAGEMENT', 'Cardiovascular disease management', 'Prise en charge des maladies cardiovasculaires', 'Gestão de doenças cardiovasculares'),
            ]),
            $this->intervention('SARA-DOM-NCD', 'SARA-INT-RESPIRATORY', $this->t('Chronic respiratory disease services', 'Services de prise en charge des maladies respiratoires chroniques', 'Serviços de doenças respiratórias crónicas'), [
                $this->area('SARA-AREA-RESP-DIAGNOSIS', 'Chronic respiratory disease diagnosis', 'Diagnostic des maladies respiratoires chroniques', 'Diagnóstico de doenças respiratórias crónicas'),
                $this->area('SARA-AREA-RESP-MANAGEMENT', 'Chronic respiratory disease management', 'Prise en charge des maladies respiratoires chroniques', 'Gestão de doenças respiratórias crónicas'),
            ]),
            $this->intervention('SARA-DOM-NCD', 'SARA-INT-CERVICAL-CANCER', $this->t('Cervical cancer prevention and control', 'Prévention et lutte contre le cancer du col de l’utérus', 'Prevenção e controlo do cancro do colo do útero'), [
                $this->area('SARA-AREA-CERVICAL-SCREENING', 'Cervical cancer screening', 'Dépistage du cancer du col de l’utérus', 'Rastreio do cancro do colo do útero'),
                $this->area('SARA-AREA-CERVICAL-VIA', 'Visual inspection with acetic acid', "Inspection visuelle à l'acide acétique", 'Inspeção visual com ácido acético'),
                $this->area('SARA-AREA-CERVICAL-REFERRAL', 'Referral for cervical precancer or cancer treatment', 'Orientation pour le traitement des lésions précancéreuses ou du cancer du col', 'Referenciação para tratamento de lesões pré-cancerosas ou cancro do colo do útero'),
            ]),

            $this->intervention('SARA-DOM-SURGERY-BLOOD', 'SARA-INT-BASIC-SURGERY', $this->t('Basic surgical services', 'Services chirurgicaux de base', 'Serviços cirúrgicos básicos'), [
                $this->area('SARA-AREA-SURGERY-SUTURING', 'Suturing', 'Suture', 'Sutura'),
                $this->area('SARA-AREA-SURGERY-INCISION-DRAINAGE', 'Incision and drainage of abscesses', "Incision et drainage d'abcès", 'Incisão e drenagem de abcessos'),
                $this->area('SARA-AREA-SURGERY-DEBRIDEMENT', 'Wound debridement', 'Débridement des plaies', 'Desbridamento de feridas'),
                $this->area('SARA-AREA-SURGERY-FRACTURE', 'Closed fracture management', 'Prise en charge des fractures fermées', 'Tratamento de fraturas fechadas'),
                $this->area('SARA-AREA-SURGERY-MALE-CIRCUMCISION', 'Male circumcision', 'Circoncision masculine', 'Circuncisão masculina'),
                $this->area('SARA-AREA-SURGERY-HERNIA', 'Hernia repair', 'Cure de hernie', 'Reparação de hérnia'),
                $this->area('SARA-AREA-SURGERY-APPENDECTOMY', 'Appendectomy', 'Appendicectomie', 'Apendicectomia'),
            ]),
            $this->intervention('SARA-DOM-SURGERY-BLOOD', 'SARA-INT-BLOOD-TRANSFUSION', $this->t('Blood transfusion services', 'Services de transfusion sanguine', 'Serviços de transfusão de sangue'), [
                $this->area('SARA-AREA-BLOOD-COLLECTION', 'Blood collection', 'Collecte de sang', 'Colheita de sangue'),
                $this->area('SARA-AREA-BLOOD-SCREENING', 'Blood screening for transfusion-transmissible infections', 'Dépistage des infections transmissibles par transfusion', 'Rastreio de infeções transmissíveis por transfusão'),
                $this->area('SARA-AREA-BLOOD-GROUPING', 'Blood grouping and compatibility testing', 'Groupage sanguin et tests de compatibilité', 'Tipagem sanguínea e testes de compatibilidade'),
                $this->area('SARA-AREA-BLOOD-STORAGE', 'Blood storage', 'Conservation du sang', 'Armazenamento de sangue'),
                $this->area('SARA-AREA-BLOOD-ADMINISTRATION', 'Blood administration and transfusion monitoring', 'Administration du sang et surveillance transfusionnelle', 'Administração de sangue e monitorização da transfusão'),
            ]),
        ];
    }

    private function intervention(string $domain, string $code, array $names, array $areas): array
    {
        return compact('domain', 'code', 'names', 'areas');
    }

    private function area(string $code, string $en, string $fr, string $pt): array
    {
        return ['code' => $code, 'names' => $this->t($en, $fr, $pt)];
    }

    private function t(string $en, string $fr, string $pt): array
    {
        return compact('en', 'fr', 'pt');
    }
}
