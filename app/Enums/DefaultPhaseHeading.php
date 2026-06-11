<?php

namespace App\Enums;


enum DefaultPhaseHeading: string
{
    case PIC                 = 'pic';
    case SELLING_COST        = 'selling_cost';
    case FORECASTED_COST     = 'forecasted_cost';
    case ACTUAL_COST         = 'actual_cost';
    case TARGET_DATETIME     = 'target_datetime';
    case ACTUAL_DATETIME     = 'actual_datetime';

    public function label(): string
    {
        return match($this) {
            self::PIC              => 'PIC',
            self::SELLING_COST     => 'Selling Cost',
            self::FORECASTED_COST  => 'Forecasted Cost',
            self::ACTUAL_COST      => 'Actual Cost',
            self::TARGET_DATETIME  => 'Target Date and Time',
            self::ACTUAL_DATETIME  => 'Actual Date and Time',

        };
    }

    public function inputType(): string
    {
        return match($this) {
            self::TARGET_DATETIME,
            self::ACTUAL_DATETIME  => 'DATETIME',
            self::SELLING_COST,
            self::FORECASTED_COST,
            self::ACTUAL_COST      => 'NUMBER',
            default                => 'TEXT',
        };
    }

    public static function defaultRows(int $templatePhaseId): array
    {
        return collect(self::cases())
            ->map(function(self $heading, int $index) use ($templatePhaseId) {
                return [
                    'template_phase_id' => $templatePhaseId,
                    'name' => $heading->label(),
                    'key' => $heading->value,
                    'sort_order' => $index + 1,
                    'input_type' => $heading->inputType()
                ];
            })
            ->values()
            ->all();
    }
}
