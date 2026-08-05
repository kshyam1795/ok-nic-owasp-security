<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owasp_security_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index();
            $table->string('key', 100)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('owasp_ref')->nullable();
            $table->boolean('enabled')->default(true);
            $table->text('value')->nullable();
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('owasp_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('owasp_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('owasp_role_permission', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('owasp_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('owasp_permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        Schema::create('owasp_user_role', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreignId('role_id')->constrained('owasp_roles')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });

        Schema::create('owasp_otp_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('purpose', 50)->default('login');
            $table->string('code_hash');
            $table->string('channel', 20)->default('email');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('owasp_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('owasp_security_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event', 100)->index();
            $table->string('category', 50)->index();
            $table->text('description')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owasp_security_audit_logs');
        Schema::dropIfExists('owasp_login_attempts');
        Schema::dropIfExists('owasp_otp_tokens');
        Schema::dropIfExists('owasp_user_role');
        Schema::dropIfExists('owasp_role_permission');
        Schema::dropIfExists('owasp_permissions');
        Schema::dropIfExists('owasp_roles');
        Schema::dropIfExists('owasp_security_settings');
    }
};
