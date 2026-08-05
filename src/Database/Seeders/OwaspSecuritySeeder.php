<?php

namespace Growats\OkNicOwaspSecurity\Database\Seeders;

use Growats\OkNicOwaspSecurity\Services\RoleService;
use Growats\OkNicOwaspSecurity\Services\SecuritySettingsService;
use Illuminate\Database\Seeder;

class OwaspSecuritySeeder extends Seeder
{
    public function run(): void
    {
        app(SecuritySettingsService::class)->seedDefaults();
        app(RoleService::class)->seedRolesAndPermissions();
        app(RoleService::class)->ensureSuperAdmin();
    }
}
