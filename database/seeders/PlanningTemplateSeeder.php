<?php

namespace Database\Seeders;

use App\Enums\DefaultPhaseHeading;
use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Models\PlanningTimeline\Config\PlanningTemplateConfig;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\PlanningTimeline\Template\PlanningTemplatePhaseHeading;
use App\Models\ServiceType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class PlanningTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createConfigs();

        $templates = [
            [
                'name' => 'Import Shipments Template',
                'service_category' => 'LOGISTICS',
                'service_type_id' => ServiceType::where('name', 'IMPORT')->value('id'),

                'workflow' => [

                    'PURCHASE ORDER AND INITIATION' => [
                        'PO Receipt' => [
                            'Receive Purchase Order/Shipping Instruction',
                        ],
                    ],

                    'SHIPMENT PLANNING' => [
                        'Service Requirement Review' => [
                            'Secure space',
                        ],
                    ],

                    'ARRIVAL & IMPORT CLEARANCE' => [
                        'Customs Clearance' => [
                            'Review customs declaration completeness',
                        ],
                    ],

                    'FINAL DELIVERY' => [
                        'Budget Approval' => [
                            'Approve shipment budget',
                        ],
                    ],
                ],
            ],

            [
                'name' => 'Export Shipments Template',
                'service_category' => 'LOGISTICS',
                'service_type_id' => ServiceType::where('name', 'EXPORT')->value('id'),

                'workflow' => [

                    'BOOKING AND PLANNING' => [
                        'Booking Confirmation' => [
                            'Receive booking request',
                        ],
                    ],

                    'ORIGIN OPERATIONS' => [
                        'Service Requirement Review' => [
                            'Coordinate cargo pickup',
                        ],
                    ],

                    'EXPORT CLEARANCE' => [
                        'Export Documentation' => [
                            'Prepare export documents',
                        ],
                    ],

                    'FREIGHT/TRANSIT' => [
                        'Initial Cost Build-Up' => [
                            'Define routing & mode',
                        ],
                    ],

                    'DOCUMENT AUDIT & COMPLIANCE' => [
                        'Customs Clearance' => [
                            'Verify commercial invoice accuracy',
                        ],
                    ],
                ],
            ],

            [
                'name' => 'Permits & Licensing Template',
                'service_category' => 'REGULATORY',
                'service_type_id' => ServiceType::where('name', 'PERMITS & LICENSING')->value('id'),

                'workflow' => [

                    'BOC ACCREDITATION' => [
                        'Client Profile Creation' => [
                            'Collect SEC and BIR registration documents',
                        ],
                        'BOC Client Registration System (CRS) Enrollment' => [
                            'Register client in BOC systems',
                        ],
                    ],

                    'IMPORTER REGISTRATION' => [
                        'Importer Clearance Certificate Validation' => [
                            'Validate importer accreditation status',
                        ],
                    ],

                    'REGULATED GOODS PERMITTING' => [
                        'FDA Permit Processing' => [
                            'Submit FDA LTO requirements',
                            'Apply for FDA import permit',
                        ],
                    ],

                    'PERMIT RENEWALS' => [
                        'Permit Renewal Management' => [
                            'Track permit validity dates',
                        ],
                    ],

                    'COMPLIANCE AUDITS' => [
                        'Customs Compliance Review' => [
                            'Prepare compliance documentation',
                            'Respond to customs compliance findings',
                        ],
                    ],
                ],
            ],

            [
                'name' => 'Post Clearance Audit Template',
                'service_category' => 'REGULATORY',
                'service_type_id' => ServiceType::where('name', 'POST CLEARANCE AUDIT')->value('id'),

                'workflow' => [

                    'REGULATED GOODS PERMITTING' => [
                        'FDA Permit Processing' => [
                            'Submit FDA LTO requirements',
                            'Apply for FDA import permit',
                        ],

                        'NTC Equipment Permit Processing' => [
                            'Apply for NTC import permit',
                        ],

                        'DENR Permit Processing' => [
                            'Apply for DENR clearance',
                        ],
                    ],

                    'PERMIT RENEWALS' => [
                        'Permit Renewal Management' => [
                            'Track permit validity dates',
                        ],
                    ],

                    'COMPLIANCE AUDITS' => [
                        'Customs Compliance Review' => [
                            'Prepare compliance documentation',
                        ],
                    ],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $this->createTemplate($templateData);
        }
    }

    private function createTemplate(array $templateData): void
    {
        $planningTemplate = PlanningTemplate::create([
            'name' => $templateData['name'],
            'version_number' => 1,
            'service_category' => $templateData['service_category'],
            'service_type_id' => $templateData['service_type_id'],
        ]);

        $phaseConfigs = PlanningConfigPhase::all()->keyBy('name');
        $processConfigs = PlanningConfigProcess::all()->keyBy('name');
        $taskConfigs = PlanningConfigTask::all()->keyBy('name');

        $sortOrder = 1;

        foreach ($templateData['workflow'] as $phaseName => $processes) {

            $configPhase = $phaseConfigs[$phaseName];

            $templatePhase = $planningTemplate->phases()->create([
                'config_phase_id' => $configPhase->id,
                'sort_order'      => $sortOrder++,
            ]);

            foreach ($processes as $processName => $tasks) {

                $configProcess = $processConfigs[$processName];

                $templateProcess = $templatePhase->processes()->create([
                    'config_process_id' => $configProcess->id,
                ]);

                foreach ($tasks as $taskName) {

                    $configTask = $taskConfigs[$taskName];

                    $templateProcess->tasks()->create([
                        'config_task_id' => $configTask->id,
                    ]);
                }
            }
        }

        $this->createPhaseHeadings($planningTemplate);
    }

    private function createConfigs() {
        $configs = [
            'phases' => [
                'PURCHASE ORDER AND INITIATION',
                'SHIPMENT PLANNING',
                'ORIGIN OPERATIONS',
                'FREIGHT/TRANSIT',
                'ARRIVAL & IMPORT CLEARANCE',
                'FINAL DELIVERY',
                'BOOKING AND PLANNING',
                'EXPORT CLEARANCE',
                'DESTINATION OPERATIONS',

                'CUSTOMS RISK MANAGEMENT',
                'DOCUMENT AUDIT & COMPLIANCE',
                'POST-CLEARANCE REVIEW',
            ],

            'processes' => [
                'PO Receipt',
                'Scope Validation',
                'Incoterm Validation',
                'Service Requirement Review',
                'Cost Validation',
                'Initial Cost Build-Up',
                'Budget Approval',
                'Booking Confirmation',
                'Export Documentation',
                'Customs Clearance',

                'HS Code Classification',
                'Duty & Tax Assessment',
                'Regulatory Compliance Check',
                'Document Verification',
                'Inspection Coordination',
                'Post Entry Amendment Handling',
                'Audit Trail Review',
            ],

            'tasks' => [
                'Receive Purchase Order/Shipping Instruction',
                'Validate scope vs quotation',
                'Secure space',
                'Define routing & mode',
                'Approve shipment budget',
                'Receive booking request',
                'Prepare export documents',
                'Coordinate cargo pickup',

                'Classify HS code',
                'Calculate duties and taxes',
                'Verify commercial invoice accuracy',
                'Review customs declaration completeness',
                'Handle customs audit request',
                'Submit post-entry correction',
                'Coordinate physical inspection with customs',
            ],
        ];

        $logisticsTemplateConfig = PlanningTemplateConfig::create([
            'version_number' => 1,
            'service_category' => 'LOGISTICS'
        ]);

        $configId = $logisticsTemplateConfig->id;

        $this->insertConfigData(PlanningConfigPhase::class, $configs['phases'], $configId);
        $this->insertConfigData(PlanningConfigProcess::class, $configs['processes'], $configId);
        $this->insertConfigData(PlanningConfigTask::class, $configs['tasks'], $configId);

        // Regulatory Config Version
        $regulatoryTemplateConfig = PlanningTemplateConfig::create([
            'version_number' => 1,
            'service_category' => 'REGULATORY'
        ]);

        $configs = [
            'phases' => [
                'BOC ACCREDITATION',
                'IMPORTER REGISTRATION',
                'EXPORTER REGISTRATION',
                'REGULATED GOODS PERMITTING',
                'TRADE INCENTIVE REGISTRATION',
                'PERMIT RENEWALS',
                'COMPLIANCE AUDITS',
            ],

            'processes' => [
                'Client Profile Creation',
                'BOC Client Registration System (CRS) Enrollment',
                'Importer Clearance Certificate Validation',
                'CPRS Enrollment',
                'FDA Permit Processing',
                'BPI Import Permit Processing',
                'BAI Import Clearance Processing',
                'NTC Equipment Permit Processing',
                'DENR Permit Processing',
                'PEZA Accreditation',
                'Permit Renewal Management',
                'Customs Compliance Review',
            ],

            'tasks' => [
                'Collect SEC and BIR registration documents',
                'Register client in BOC systems',
                'Validate importer accreditation status',
                'Submit FDA LTO requirements',
                'Apply for FDA import permit',
                'Apply for BPI SPS permit',
                'Apply for BAI veterinary clearance',
                'Apply for NTC import permit',
                'Apply for DENR clearance',
                'Track permit validity dates',
                'Prepare compliance documentation',
                'Respond to customs compliance findings',
            ],
        ];

        $configId = $regulatoryTemplateConfig->id;

        $this->insertConfigData(PlanningConfigPhase::class, $configs['phases'], $configId);
        $this->insertConfigData(PlanningConfigProcess::class, $configs['processes'], $configId);
        $this->insertConfigData(PlanningConfigTask::class, $configs['tasks'], $configId);
    }

    private function insertConfigData(string $modelClass, array $data, $configId) {
        $modelClass::insert(
            collect($data)
                ->map(fn ($name) => [
                    'name' => $name,
                    'config_id' => $configId
                ])
                ->all()
        );
    }

    public function createPhaseHeadings(PlanningTemplate $planningTemplate) {
        foreach($planningTemplate->phases as $phase) {
            PlanningTemplatePhaseHeading::insert(
                DefaultPhaseHeading::defaultRows($phase->id)
            );
        }
    }
}