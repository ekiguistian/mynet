<?php

namespace App;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Admin = 'admin';
    case Finance = 'finance';
    case Sales = 'sales';
    case Technician = 'technician';
    case Helper = 'helper';
    case Manager = 'manager';
    case Noc = 'noc';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Admin => 'Admin',
            self::Finance => 'Finance',
            self::Sales => 'Sales',
            self::Technician => 'Teknisi',
            self::Helper => 'Helper',
            self::Manager => 'Manager',
            self::Noc => 'NOC',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }

    public function canAccessFeature(string $feature, bool $write = false): bool
    {
        if ($this === self::Administrator) {
            return true;
        }

        $view = [
            'dashboard' => [self::Admin, self::Finance, self::Sales, self::Technician, self::Helper, self::Manager, self::Noc],
            'branches' => [self::Admin],
            'customers' => [self::Admin, self::Manager, self::Finance, self::Sales, self::Technician, self::Noc],
            'plans' => [self::Admin, self::Manager, self::Sales, self::Noc],
            'invoices' => [self::Admin, self::Manager, self::Finance],
            'payments' => [self::Admin, self::Manager, self::Finance],
            'finance' => [self::Admin, self::Manager, self::Finance],
            'inventory' => [self::Admin, self::Manager, self::Technician, self::Helper],
            'attendance' => [self::Admin, self::Manager, self::Finance, self::Sales, self::Technician, self::Helper, self::Noc],
            'tickets' => [self::Admin, self::Manager, self::Sales, self::Technician, self::Helper, self::Noc],
            'work_orders' => [self::Admin, self::Manager, self::Sales, self::Technician, self::Helper],
            'routers' => [self::Admin, self::Manager, self::Noc],
            'vouchers' => [self::Admin, self::Manager, self::Sales, self::Noc],
            'users' => [self::Admin, self::Manager],
            'settings' => [self::Admin],
            'company_settings' => [self::Admin],
            'integrations' => [self::Admin],
            'reports' => [self::Admin, self::Manager, self::Finance, self::Sales, self::Technician, self::Noc],
            'activity_logs' => [],
            'version' => [],
        ];

        $manage = [
            'dashboard' => [],
            'branches' => [self::Admin],
            'customers' => [self::Admin, self::Manager, self::Sales],
            'plans' => [self::Admin, self::Manager],
            'invoices' => [self::Admin, self::Manager, self::Finance],
            'payments' => [self::Admin, self::Manager, self::Finance],
            'finance' => [self::Admin, self::Manager, self::Finance],
            'inventory' => [self::Admin, self::Manager, self::Technician, self::Helper],
            'attendance' => [self::Admin, self::Manager],
            'tickets' => [self::Admin, self::Manager, self::Sales, self::Technician, self::Helper, self::Noc],
            'work_orders' => [self::Admin, self::Manager, self::Sales, self::Technician, self::Helper],
            'routers' => [self::Admin, self::Manager, self::Noc],
            'vouchers' => [self::Admin, self::Manager, self::Noc],
            'users' => [self::Admin, self::Manager],
            'settings' => [self::Admin],
            'company_settings' => [self::Admin],
            'integrations' => [self::Admin],
            'reports' => [],
            'activity_logs' => [],
            'version' => [],
        ];

        $allowedRoles = $write ? ($manage[$feature] ?? []) : ($view[$feature] ?? []);

        return in_array($this, $allowedRoles, true);
    }
}
