<?php

namespace Database\Seeders;

use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Models\PlanningTimeline\Template\PlanningTemplate;
use App\Models\User;
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

        $leadOpsId = User::role('Lead Operations')->value('id');

        $templates = [
            [
                'name' => 'Import Shipments Template',
                'service_category' => 'LOGISTICS',
                'service_type' => 'IMPORT',
                'created_by' => $leadOpsId,

                'workflow' => [

                    'PURCHASE ORDER AND INITIATION' => [

                        'PO Receipt' => [
                            'Receive Purchase Order/Shipping Instruction',
                        ],

                        'Scope Validation' => [
                            'Validate scope vs quotation',
                        ],

                        'Budget Approval' => [
                            'Approve shipment budget',
                        ],
                    ],

                    'SHIPMENT PLANNING' => [

                        'Incoterm Validation' => [
                            'Validate scope vs quotation',
                        ],

                        'Service Requirement Review' => [
                            'Define routing & mode',
                            'Secure space',
                        ],
                    ],

                    'ORIGIN OPERATIONS' => [

                        'Service Requirement Review' => [
                            'Secure space',
                        ],
                    ],

                    'FREIGHT/TRANSIT' => [

                        'Initial Cost Build-Up' => [
                            'Approve shipment budget',
                        ],
                    ],

                    'ARRIVAL & IMPORT CLEARANCE' => [

                        'Cost Validation' => [
                            'Validate scope vs quotation',
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
                'service_type' => 'EXPORT',
                'created_by' => $leadOpsId,

                'workflow' => [

                    'BOOKING AND PLANNING' => [

                        'Booking Confirmation' => [
                            'Receive booking request',
                        ],
                    ],

                    'ORIGIN OPERATIONS' => [

                        'Booking Confirmation' => [
                            'Coordinate cargo pickup',
                        ],
                    ],

                    'EXPORT CLEARANCE' => [

                        'Export Documentation' => [
                            'Prepare export documents',
                        ],

                        'Customs Clearance' => [
                            'Prepare export documents',
                        ],
                    ],

                    'FREIGHT/TRANSIT' => [

                        'Booking Confirmation' => [
                            'Coordinate cargo pickup',
                        ],
                    ],

                    'DESTINATION OPERATIONS' => [

                        'Customs Clearance' => [
                            'Prepare export documents',
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
            'name'             => $templateData['name'],
            'service_category' => $templateData['service_category'],
            'service_type'     => $templateData['service_type'],
            'created_by'       => $templateData['created_by'],
            'status'           => 'SAVED',
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
            ],
        ];

        $this->insertConfigData(PlanningConfigPhase::class, $configs['phases']);
        $this->insertConfigData(PlanningConfigProcess::class, $configs['processes']);
        $this->insertConfigData(PlanningConfigTask::class, $configs['tasks']);
    }

    private function insertConfigData(string $modelClass, array $data) {
        $modelClass::insert(
            collect($data)
                ->map(fn ($task) => [
                    'name' => $task,
                ])
                ->all()
        );
    }
}