<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gatekeeper_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label')->nullable();
            $table->string('guard')->default('web');
            $table->string('group')->nullable();
            $table->timestamps();
        });

        Schema::create('gatekeeper_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label')->nullable();
            $table->string('guard')->default('web');
            $table->timestamps();
        });

        Schema::create('gatekeeper_role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('gatekeeper_roles')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('gatekeeper_permissions')
                ->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('gatekeeper_role_user', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('gatekeeper_roles')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->primary(['role_id', 'user_id', 'user_type']);
        });

        Schema::create('gatekeeper_permission_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')
                ->constrained('gatekeeper_permissions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('user_type');
            $table->boolean('granted')->default(true);
            $table->timestamps();
            $table->unique(['permission_id', 'user_id', 'user_type']);
        });

        Schema::create('gatekeeper_sync_log', function (Blueprint $table) {
            $table->id();
            $table->json('summary');
            $table->boolean('dry_run')->default(false);
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gatekeeper_permission_user');
        Schema::dropIfExists('gatekeeper_role_user');
        Schema::dropIfExists('gatekeeper_role_permissions');
        Schema::dropIfExists('gatekeeper_roles');
        Schema::dropIfExists('gatekeeper_permissions');
        Schema::dropIfExists('gatekeeper_sync_log');
    }
};
