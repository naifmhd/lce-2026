<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CcDhaaira1 = 'cc-dhaaira-1';
    case CcDhaaira2 = 'cc-dhaaira-2';
    case CcDhaaira3 = 'cc-dhaaira-3';
    case CcDhaaira4 = 'cc-dhaaira-4';
    case CcDhaaira5 = 'cc-dhaaira-5';
    case CcDhaaira6 = 'cc-dhaaira-6';
    case CcMayor = 'cc-mayor';
    case CcRaeesa = 'cc-raeesa';
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
    case Results = 'results';
    case Zeroday = 'zeroday';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::CcDhaaira1 => 'CC Dhaaira 1',
            self::CcDhaaira2 => 'CC Dhaaira 2',
            self::CcDhaaira3 => 'CC Dhaaira 3',
            self::CcDhaaira4 => 'CC Dhaaira 4',
            self::CcDhaaira5 => 'CC Dhaaira 5',
            self::CcDhaaira6 => 'CC Dhaaira 6',
            self::CcMayor => 'CC Mayor',
            self::CcRaeesa => 'CC Raeesa',
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
            self::Results => 'Results',
            self::Zeroday => 'Zeroday',
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
            self::Mayor->value,
            self::Raeesa->value,
            self::Zeroday->value,
        ];
    }

    public static function ccDhaairaCodeForRole(string $role): ?string
    {
        return match ($role) {
            self::CcDhaaira1->value => 'B9-1',
            self::CcDhaaira2->value => 'B9-2',
            self::CcDhaaira3->value => 'B9-3',
            self::CcDhaaira4->value => 'B9-4',
            self::CcDhaaira5->value => 'B9-5',
            self::CcDhaaira6->value => 'B9-6',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function ccRoleKeys(): array
    {
        return [
            self::CcDhaaira1->value,
            self::CcDhaaira2->value,
            self::CcDhaaira3->value,
            self::CcDhaaira4->value,
            self::CcDhaaira5->value,
            self::CcDhaaira6->value,
            self::CcMayor->value,
            self::CcRaeesa->value,
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
