<?php

namespace Database\Seeders;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Models\PlanningTimeline\Config\PlanningConfigVersion;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
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
            $configPhase->update([
                'is_locked' => true
            ]);

            $templatePhase = $planningTemplate->phases()->create([
                'config_phase_id' => $configPhase->id,
                'sort_order'      => $sortOrder++,
            ]);

            foreach ($processes as $processName => $tasks) {

                $configProcess = $processConfigs[$processName];
                $configProcess->update([
                    'is_locked' => true
                ]);

                $templateProcess = $templatePhase->processes()->create([
                    'config_process_id' => $configProcess->id,
                ]);

                foreach ($tasks as $taskName) {

                    $configTask = $taskConfigs[$taskName];
                    $configTask->update([
                        'is_locked' => true
                    ]);

                    $templateProcess->tasks()->create([
                        'config_task_id' => $configTask->id,
                    ]);
                }
            }
        }
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

        $configVersion = PlanningConfigVersion::create([
            'version_number' => 1,
            'service_category' => 'LOGISTICS'
        ]);

        $versionId = $configVersion->id;

        $this->insertConfigData(PlanningConfigPhase::class, $configs['phases'], $versionId);
        $this->insertConfigData(PlanningConfigProcess::class, $configs['processes'], $versionId);
        $this->insertConfigData(PlanningConfigTask::class, $configs['tasks'], $versionId);

        // Regulatory Config Version
        PlanningConfigVersion::create([
            'version_number' => 1,
            'service_category' => 'REGULATORY'
        ]);
    }

    private function insertConfigData(string $modelClass, array $data, $versionId) {
        $modelClass::insert(
            collect($data)
                ->map(fn ($name) => [
                    'name' => $name,
                    'config_version_id' => $versionId
                ])
                ->all()
        );
    }
}