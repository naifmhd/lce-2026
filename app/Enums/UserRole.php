<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CallCenter = 'call-center';
    case Dhaaira1Council = 'dhaaira-1-council';
    case Dhaaira2Council = 'dhaaira-2-council';
    case Dhaaira3Council = 'dhaaira-3-council';
    case Dhaaira4Council = 'dhaaira-4-council';
    case Dhaaira5Council = 'dhaaira-5-council';
    case Dhaaira6Council = 'dhaaira-6-council';
    case Dhaaira1Wdc = 'dhaaira-1-wdc';
    case Dhaaira2Wdc = 'dhaaira-2-wdc';
    case Dhaaira3Wdc = 'dhaaira-3-wdc';
    case Dhaaira4Wdc = 'dhaaira-4-wdc';
    case Dhaaira5Wdc = 'dhaaira-5-wdc';
    case Dhaaira6Wdc = 'dhaaira-6-wdc';
    case Raeesa = 'raeesa';
    case Mayor = 'mayor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::CallCenter => 'Call Center',
            self::Dhaaira1Council => 'Dhaaira 1 Council',
            self::Dhaaira2Council => 'Dhaaira 2 Council',
            self::Dhaaira3Council => 'Dhaaira 3 Council',
            self::Dhaaira4Council => 'Dhaaira 4 Council',
            self::Dhaaira5Council => 'Dhaaira 5 Council',
            self::Dhaaira6Council => 'Dhaaira 6 Council',
            self::Dhaaira1Wdc => 'Dhaaira 1 WDC',
            self::Dhaaira2Wdc => 'Dhaaira 2 WDC',
            self::Dhaaira3Wdc => 'Dhaaira 3 WDC',
            self::Dhaaira4Wdc => 'Dhaaira 4 WDC',
            self::Dhaaira5Wdc => 'Dhaaira 5 WDC',
            self::Dhaaira6Wdc => 'Dhaaira 6 WDC',
            self::Mayor => 'Mayor',
            self::Raeesa => 'Raeesa',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::cases());
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $role): array => [
                'key' => $role->value,
                'label' => $role->label(),
            ],
            self::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function fullAccessRoleKeys(): array
    {
        return [
            self::Admin->value,
            self::CallCenter->value,
            self::Mayor->value,
            self::Raeesa->value,
        ];
    }

    public static function dhaairaaCodeForRole(string $role): ?string
    {
        return match ($role) {
            self::Dhaaira1Council->value => 'B9-1',
            self::Dhaaira2Council->value => 'B9-2',
            self::Dhaaira3Council->value => 'B9-3',
            self::Dhaaira4Council->value => 'B9-4',
            self::Dhaaira5Council->value => 'B9-5',
            self::Dhaaira6Council->value => 'B9-6',
            self::Dhaaira1Wdc->value => 'B9-1',
            self::Dhaaira2Wdc->value => 'B9-2',
            self::Dhaaira3Wdc->value => 'B9-3',
            self::Dhaaira4Wdc->value => 'B9-4',
            self::Dhaaira5Wdc->value => 'B9-5',
            self::Dhaaira6Wdc->value => 'B9-6',
            default => null,
        };
    }
}
