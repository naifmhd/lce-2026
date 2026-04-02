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
    case MonitorUthuru1 = 'monitor-uthuru-1';
    case MonitorUthuru2 = 'monitor-uthuru-2';
    case MonitorHulhanguUthuru1 = 'monitor-hulhangu-uthuru-1';
    case MonitorHulhanguUthuru2 = 'monitor-hulhangu-uthuru-2';
    case MonitorMedhuUthuru1 = 'monitor-medhu-uthuru-1';
    case MonitorMedhuUthuru2 = 'monitor-medhu-uthuru-2';
    case MonitorMedhuDhekunu1 = 'monitor-medhu-dhekunu-1';
    case MonitorMedhuDhekunu2 = 'monitor-medhu-dhekunu-2';
    case MonitorHulhanguDhekunu1 = 'monitor-hulhangu-dhekunu-1';
    case MonitorHulhanguDhekunu2 = 'monitor-hulhangu-dhekunu-2';
    case MonitorIruDhekunu1 = 'monitor-iru-dhekunu-1';
    case MonitorIruDhekunu2 = 'monitor-iru-dhekunu-2';
    case MonitorGreaterMale = 'monitor-greater-male';
    case MonitorOther1 = 'monitor-other-1';
    case MonitorOther2 = 'monitor-other-2';
    case MonitorOther3 = 'monitor-other-3';
    case MonitorOther4 = 'monitor-other-4';
    case MonitorOther5 = 'monitor-other-5';
    case MonitorOther6 = 'monitor-other-6';

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
            self::MonitorUthuru1 => 'Monitor Uthuru-1',
            self::MonitorUthuru2 => 'Monitor Uthuru-2',
            self::MonitorHulhanguUthuru1 => 'Monitor Hulhangu Uthuru-1',
            self::MonitorHulhanguUthuru2 => 'Monitor Hulhangu Uthuru-2',
            self::MonitorMedhuUthuru1 => 'Monitor Medhu Uthuru-1',
            self::MonitorMedhuUthuru2 => 'Monitor Medhu Uthuru-2',
            self::MonitorMedhuDhekunu1 => 'Monitor Medhu Dhekunu-1',
            self::MonitorMedhuDhekunu2 => 'Monitor Medhu Dhekunu-2',
            self::MonitorHulhanguDhekunu1 => 'Monitor Hulhangu Dhekunu-1',
            self::MonitorHulhanguDhekunu2 => 'Monitor Hulhangu Dhekunu-2',
            self::MonitorIruDhekunu1 => 'Monitor Iru Dhekunu-1',
            self::MonitorIruDhekunu2 => 'Monitor Iru Dhekunu-2',
            self::MonitorGreaterMale => 'Monitor Greater Male',
            self::MonitorOther1 => 'Monitor Other-1',
            self::MonitorOther2 => 'Monitor Other-2',
            self::MonitorOther3 => 'Monitor Other-3',
            self::MonitorOther4 => 'Monitor Other-4',
            self::MonitorOther5 => 'Monitor Other-5',
            self::MonitorOther6 => 'Monitor Other-6',
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
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function resultsViewerRoleKeys(): array
    {
        return [
            self::Dhaaira1Council->value,
            self::Dhaaira2Council->value,
            self::Dhaaira3Council->value,
            self::Dhaaira4Council->value,
            self::Dhaaira5Council->value,
            self::Dhaaira6Council->value,
            self::Dhaaira1Wdc->value,
            self::Dhaaira2Wdc->value,
            self::Dhaaira3Wdc->value,
            self::Dhaaira4Wdc->value,
            self::Dhaaira5Wdc->value,
            self::Dhaaira6Wdc->value,
            self::Mayor->value,
            self::Raeesa->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function monitorRoleKeys(): array
    {
        return [
            self::MonitorUthuru1->value,
            self::MonitorUthuru2->value,
            self::MonitorHulhanguUthuru1->value,
            self::MonitorHulhanguUthuru2->value,
            self::MonitorMedhuUthuru1->value,
            self::MonitorMedhuUthuru2->value,
            self::MonitorMedhuDhekunu1->value,
            self::MonitorMedhuDhekunu2->value,
            self::MonitorHulhanguDhekunu1->value,
            self::MonitorHulhanguDhekunu2->value,
            self::MonitorIruDhekunu1->value,
            self::MonitorIruDhekunu2->value,
            self::MonitorGreaterMale->value,
            self::MonitorOther1->value,
            self::MonitorOther2->value,
            self::MonitorOther3->value,
            self::MonitorOther4->value,
            self::MonitorOther5->value,
            self::MonitorOther6->value,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function registeredBoxesForMonitorRole(string $role): array
    {
        return match ($role) {
            self::MonitorUthuru1->value => ['Kulhudhuffushi Uthuru-1'],
            self::MonitorUthuru2->value => ['Kulhudhuffushi Uthuru-2'],
            self::MonitorHulhanguUthuru1->value => ['Kulhudhuffushi Hulhangu Uthuru-1'],
            self::MonitorHulhanguUthuru2->value => ['Kulhudhuffushi Hulhangu Uthuru-2'],
            self::MonitorMedhuUthuru1->value => ['Kulhudhuffushi Medhu Uthuru-1'],
            self::MonitorMedhuUthuru2->value => ['Kulhudhuffushi Medhu Uthuru-2'],
            self::MonitorMedhuDhekunu1->value => ['Kulhudhuffushi Medhu Dhekunu-1'],
            self::MonitorMedhuDhekunu2->value => ['Kulhudhuffushi Medhu Dhekunu-2'],
            self::MonitorHulhanguDhekunu1->value => ['Kulhudhuffushi Hulhangu Dhekunu-1'],
            self::MonitorHulhanguDhekunu2->value => ['Kulhudhuffushi Hulhangu Dhekunu-2'],
            self::MonitorIruDhekunu1->value => ['Kulhudhuffushi Iru Dhekunu-1'],
            self::MonitorIruDhekunu2->value => ['Kulhudhuffushi Iru Dhekunu-2'],
            self::MonitorGreaterMale->value => [
                "HDH. Atoll, Male'-4",
                "Hulhumale' Phase1, Ehenihen-18",
                "Hulhumale' Phase2, Ehenihen-3",
                "Vilimale', Ehenihen-4",
            ],
            default => [],
        };
    }

    public static function dhaairaCodeForOtherMonitorRole(string $role): ?string
    {
        return match ($role) {
            self::MonitorOther1->value => 'B9-1',
            self::MonitorOther2->value => 'B9-2',
            self::MonitorOther3->value => 'B9-3',
            self::MonitorOther4->value => 'B9-4',
            self::MonitorOther5->value => 'B9-5',
            self::MonitorOther6->value => 'B9-6',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    public static function knownRegisteredBoxes(): array
    {
        return [
            'Kulhudhuffushi Uthuru-1',
            'Kulhudhuffushi Uthuru-2',
            'Kulhudhuffushi Hulhangu Uthuru-1',
            'Kulhudhuffushi Hulhangu Uthuru-2',
            'Kulhudhuffushi Medhu Uthuru-1',
            'Kulhudhuffushi Medhu Uthuru-2',
            'Kulhudhuffushi Medhu Dhekunu-1',
            'Kulhudhuffushi Medhu Dhekunu-2',
            'Kulhudhuffushi Hulhangu Dhekunu-1',
            'Kulhudhuffushi Hulhangu Dhekunu-2',
            'Kulhudhuffushi Iru Dhekunu-1',
            'Kulhudhuffushi Iru Dhekunu-2',
            "HDH. Atoll, Male'-4",
            "Hulhumale' Phase1, Ehenihen-18",
            "Hulhumale' Phase2, Ehenihen-3",
            "Vilimale', Ehenihen-4",
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function votersListRoleKeys(): array
    {
        return [
            self::Dhaaira1Council->value,
            self::Dhaaira2Council->value,
            self::Dhaaira3Council->value,
            self::Dhaaira4Council->value,
            self::Dhaaira5Council->value,
            self::Dhaaira6Council->value,
            self::Dhaaira1Wdc->value,
            self::Dhaaira2Wdc->value,
            self::Dhaaira3Wdc->value,
            self::Dhaaira4Wdc->value,
            self::Dhaaira5Wdc->value,
            self::Dhaaira6Wdc->value,
            self::Raeesa->value,
            self::Mayor->value,
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
