<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status')->default('active')->index();
            $table->string('plan')->default('starter')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('billing_email')->nullable();
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member')->index();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('domain')->nullable()->unique();
            $table->string('status')->default('active')->index();
            $table->unsignedTinyInteger('autonomy_level')->default(2);
            $table->string('language', 12)->default('en');
            $table->string('timezone')->default('UTC');
            $table->json('settings')->nullable();
            $table->timestamp('last_evolved_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'slug']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('provider')->default('stripe');
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->string('provider_price_id')->nullable()->index();
            $table->string('plan')->default('starter')->index();
            $table->string('status')->default('inactive')->index();
            $table->timestamp('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('usage_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignUuid('site_id')->nullable()->constrained('sites')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('meter')->index();
            $table->unsignedInteger('quantity')->default(1);
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
            $table->index(['workspace_id', 'meter', 'occurred_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'created_at']);
        });

        Schema::create('api_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::table('content_items', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->nullable()->after('id')->constrained('workspaces')->nullOnDelete();
            $table->foreignUuid('site_id')->nullable()->after('workspace_id')->constrained('sites')->nullOnDelete();
        });

        try { Schema::table('content_items', fn (Blueprint $table) => $table->dropUnique('content_items_slug_unique')); } catch (Throwable) {}
        Schema::table('content_items', function (Blueprint $table) {
            $table->unique(['site_id', 'slug']);
            $table->index(['workspace_id', 'site_id', 'status']);
        });

        Schema::table('ai_actions', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->nullable()->after('id')->constrained('workspaces')->nullOnDelete();
            $table->foreignUuid('site_id')->nullable()->after('workspace_id')->constrained('sites')->nullOnDelete();
            $table->index(['workspace_id', 'site_id', 'created_at']);
        });

        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignUuid('workspace_id')->nullable()->after('id')->constrained('workspaces')->nullOnDelete();
            $table->foreignUuid('site_id')->nullable()->after('workspace_id')->constrained('sites')->nullOnDelete();
            $table->index(['workspace_id', 'site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['workspace_id']);
            $table->dropColumn(['site_id', 'workspace_id']);
        });
        Schema::table('ai_actions', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropForeign(['workspace_id']);
            $table->dropColumn(['site_id', 'workspace_id']);
        });
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'slug']);
            $table->dropForeign(['site_id']);
            $table->dropForeign(['workspace_id']);
            $table->dropColumn(['site_id', 'workspace_id']);
            $table->unique('slug');
        });

        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('usage_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('workspaces');
    }
};
