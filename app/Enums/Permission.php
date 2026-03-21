<?php

namespace App\Enums;

enum Permission: string
{
    case ViewCouncilPledge = 'pledge.council.view';
    case UpdateCouncilPledge = 'pledge.council.update';
    case ViewMayorPledge = 'pledge.mayor.view';
    case UpdateMayorPledge = 'pledge.mayor.update';
    case ViewWdcPledge = 'pledge.wdc.view';
    case UpdateWdcPledge = 'pledge.wdc.update';
    case ViewRaeesaPledge = 'pledge.raeesa.view';
    case UpdateRaeesaPledge = 'pledge.raeesa.update';

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_map(static fn (self $p): string => $p->value, self::cases());
    }

    /**
     * @return array<int, array{key: string, label: string, items: array<int, array{key: string, label: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'pledge',
                'label' => 'Pledge',
                'items' => [
                    ['key' => self::ViewCouncilPledge->value, 'label' => 'View Council'],
                    ['key' => self::UpdateCouncilPledge->value, 'label' => 'Edit Council'],
                    ['key' => self::ViewMayorPledge->value, 'label' => 'View Mayor'],
                    ['key' => self::UpdateMayorPledge->value, 'label' => 'Edit Mayor'],
                    ['key' => self::ViewWdcPledge->value, 'label' => 'View WDC'],
                    ['key' => self::UpdateWdcPledge->value, 'label' => 'Edit WDC'],
                    ['key' => self::ViewRaeesaPledge->value, 'label' => 'View Raeesa'],
                    ['key' => self::UpdateRaeesaPledge->value, 'label' => 'Edit Raeesa'],
                ],
            ],
        ];
    }
}
