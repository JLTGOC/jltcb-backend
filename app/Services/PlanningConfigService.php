<?php

namespace App\Services;

use App\Exceptions\LockedConfigItemException;
use App\Models\PlanningTimeline\Config\PlanningConfigPhase;
use App\Models\PlanningTimeline\Config\PlanningConfigProcess;
use App\Models\PlanningTimeline\Config\PlanningConfigTask;
use App\Models\PlanningTimeline\Config\PlanningTemplateConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PlanningConfigService {

    public function update(PlanningTemplateConfig $templateConfig, array $data) : PlanningTemplateConfig {
        $templateConfig->load([
            'phases.templatePhases', 
            'processes.templateProcesses', 
            'tasks.templateTasks',
        ]);

        $violations = array_merge(
            $this->checkViolations($templateConfig->phases, $data['phases'], 'phases'),
            $this->checkViolations($templateConfig->processes, $data['processes'], 'processes'),
            $this->checkViolations($templateConfig->tasks, $data['tasks'], 'tasks',),
        );

        if (!empty($violations)) {
            throw new LockedConfigItemException($violations);
        };

        DB::transaction(function() use ($templateConfig, $data) {
            $this->applyChanges(
                $templateConfig->phases, 
                $data['phases'], 
                $templateConfig, 
                PlanningConfigPhase::class
            );

            $this->applyChanges(
                $templateConfig->processes, 
                $data['processes'], 
                $templateConfig, 
                PlanningConfigProcess::class
            );

            $this->applyChanges(
                $templateConfig->tasks, 
                $data['tasks'], 
                $templateConfig, 
                PlanningConfigTask::class
            );

            $templateConfig->increment('version_number');
        });

        return $templateConfig->fresh()->load([
            'phases.templatePhases.template', 
            'processes.templateProcesses.phase.template', 
            'tasks.templateTasks.process.phase.template',

            'phases' => fn ($q) => $q->withExists([
                'templatePhases as is_locked'
            ]),
            'processes' => fn ($q) => $q->withExists([
                'templateProcesses as is_locked'
            ]),
            'tasks' => fn ($q) => $q->withExists([
                'templateTasks as is_locked'
            ]),
        ]);
    }

    private function checkViolations(Collection $current, array $incoming, string $level) {
        $incomingIds = collect($incoming)->pluck('id')->filter()->values()->all();
        $violations = [];

        foreach($current as $item) {
            if (!$item->isLocked()) {
                continue;
            }

            // // Violation for deleting locked config
            if (!in_array($item->id, $incomingIds)) {
                $violations[$level][] = $this->buildViolation($item, 'delete');
                continue;
            }

            // Violation for editing locked config
            $incomingItem = collect($incoming)->firstWhere('id', $item->id);
            if ($incomingItem && $incomingItem['name'] !== $item->name) {
                $violations[$level][] = $this->buildViolation($item, 'edit');
            }
            
        }

        return $violations;
    }

    private function buildViolation($item, string $action) {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'action' => $action,
            'reason' => "Cannot " . $action . ": this item is referenced by one or more planning templates."
        ];
    }

    private function applyChanges(Collection $current, array $incoming, PlanningTemplateConfig $templateConfig, string $configClass,): void
    {
        $incoming    = collect($incoming);
        $incomingIds = $incoming->pluck('id')->filter()->values()->all();

        $current->filter(fn($item) => !in_array($item->id, $incomingIds))
                ->each(fn($item) => $item->delete());

        $incoming->filter(fn($item) => !empty($item['id'] ?? null))
                 ->each(function ($item) use ($current) {
                     $dbItem = $current->firstWhere('id', $item['id']);

                     if ($dbItem && $dbItem->name !== $item['name']) {
                         $dbItem->update(['name' => $item['name']]);
                     }
                 });

        $incoming->filter(fn($item) => empty($item['id'] ?? null))
            ->each(function($item) use ($configClass, $templateConfig) {
                $configClass::create([
                    'name' => $item['name'],
                    'config_id' => $templateConfig->id,
                ]);
            });
    }
}