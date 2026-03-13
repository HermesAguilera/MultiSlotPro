<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tenantTables = [
            'tenant_users',
            'tenant_domains',
            'tenant_settings',
            'tenant_databases',
            'tenants',
        ];

        Schema::disableForeignKeyConstraints();

        foreach ($tenantTables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }

        Schema::enableForeignKeyConstraints();

        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->whereIn('model_type', [
                    'App\\Models\\TenantUser',
                    'App\\Models\\Tenant',
                ])
                ->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->whereIn('model_type', [
                    'App\\Models\\TenantUser',
                    'App\\Models\\Tenant',
                ])
                ->delete();
        }
    }

    public function down(): void
    {
        // Irreversible cleanup migration by design.
    }
};
