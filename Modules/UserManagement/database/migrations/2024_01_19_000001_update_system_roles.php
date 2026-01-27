<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Only Super Admin should be a system role.
     */
    public function up(): void
    {
        // Set all roles to is_system = false except super_admin
        DB::table('roles')
            ->where('slug', '!=', 'super_admin')
            ->update(['is_system' => false]);

        // Ensure super_admin is a system role
        DB::table('roles')
            ->where('slug', 'super_admin')
            ->update(['is_system' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original system roles
        DB::table('roles')
            ->whereIn('slug', ['admin', 'customer', 'guest'])
            ->update(['is_system' => true]);
    }
};
