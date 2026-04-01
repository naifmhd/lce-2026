<?php

namespace App\Enums;

enum Permission: string
{
    case CouncilPledge = 'pledge.council';
    case MayorPledge = 'pledge.mayor';
    case WdcPledge = 'pledge.wdc';
    case RaeesaPledge = 'pledge.raeesa';

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
                    ['key' => self::CouncilPledge->value, 'label' => 'Council'],
                    ['key' => self::MayorPledge->value, 'label' => 'Mayor'],
                    ['key' => self::WdcPledge->value, 'label' => 'WDC'],
                    ['key' => self::RaeesaPledge->value, 'label' => 'Raeesa'],
                ],
            ],
        ];
    }
}
