<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case CallCenter = 'call-center';
    case Dhaaira1 = 'dhaaira-1';
    case Dhaaira2 = 'dhaaira-2';
    case Dhaaira3 = 'dhaaira-3';
    case Dhaaira4 = 'dhaaira-4';
    case Dhaaira5 = 'dhaaira-5';
    case Dhaaira6 = 'dhaaira-6';
    case Raeesa = 'raeesa';
    case Mayor = 'mayor';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::CallCenter => 'Call Center',
            self::Dhaaira1 => 'Dhaaira 1',
            self::Dhaaira2 => 'Dhaaira 2',
            self::Dhaaira3 => 'Dhaaira 3',
            self::Dhaaira4 => 'Dhaaira 4',
            self::Dhaaira5 => 'Dhaaira 5',
            self::Dhaaira6 => 'Dhaaira 6',
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
            self::Dhaaira1->value => 'B9-1',
            self::Dhaaira2->value => 'B9-2',
            self::Dhaaira3->value => 'B9-3',
            self::Dhaaira4->value => 'B9-4',
            self::Dhaaira5->value => 'B9-5',
            self::Dhaaira6->value => 'B9-6',
            default => null,
        };
    }
}
