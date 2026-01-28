<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
            $table->index('permission_id');
            $table->index('role_id');
        });

        // Add foreign keys after ensuring referenced tables exist
        if (Schema::hasTable('permissions')) {
            Schema::table('permission_role', function (Blueprint $table) {
                $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            });
        }
        if (Schema::hasTable('roles')) {
            Schema::table('permission_role', function (Blueprint $table) {
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
