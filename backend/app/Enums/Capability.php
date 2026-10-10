<?php

namespace App\Enums;

enum Capability: string
{
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case AuditView = 'audit.view';
    case CatalogView = 'catalog.view';
    case CatalogManage = 'catalog.manage';
    case InventoryView = 'inventory.view';
    case InventoryManage = 'inventory.manage';
    case InventoryAdjust = 'inventory.adjust';

    public function isGrantedTo(UserRole $role): bool
    {
        return match ($this) {
            self::UsersView,
            self::UsersManage,
            self::AuditView,
            self::InventoryAdjust => $role === UserRole::Admin,
            self::CatalogManage,
            self::InventoryManage => in_array($role, [UserRole::Admin, UserRole::Operator], true),
            self::CatalogView,
            self::InventoryView => true,
        };
    }

    /**
     * @return list<string>
     */
    public static function valuesFor(UserRole $role): array
    {
        return array_values(array_map(
            static fn (self $capability): string => $capability->value,
            array_filter(self::cases(), static fn (self $capability): bool => $capability->isGrantedTo($role)),
        ));
    }
}
