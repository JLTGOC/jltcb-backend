<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Validator;

class UniqueReceiptChargeLabelRule
{
    public function __construct(private array $charges) {}

    public function __invoke(Validator $validator): void
    {
        foreach ($this->charges as $chargeIndex => $charge) {
            $seen = [];

            foreach ($charge['items'] ?? [] as $itemIndex => $item) {
                $label = $item['receipt_charge_label'] ?? null;
                $containerSize = $item['container_size'] ?? null;
                $uom = $item['uom'] ?? null;

                if (!$label) continue;

                if ($uom === 'PER CONTAINER') {
                    $key = $label . '||' . $containerSize;

                    if (isset($seen[$key])) {
                        $validator->errors()->add(
                            "charges.{$chargeIndex}.items.{$itemIndex}.container_size",
                            "The container size must be unique when the receipt charges are similar."
                        );
                    } else {
                        $seen[$key] = true;
                    }
                } else {
                    if (isset($seen[$label])) {
                        $validator->errors()->add(
                            "charges.{$chargeIndex}.items.{$itemIndex}.receipt_charge_label",
                            'The receipt charges must be unique.'
                        );
                    } else {
                        $seen[$label] = true;
                    }
                }
            }
        }
    }
}