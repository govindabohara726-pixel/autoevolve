<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->json('body')->nullable();
            $table->string('primary_keyword')->nullable()->index();
            $table->string('search_intent')->nullable();
            $table->string('status')->default('draft')->index();
            $table->unsignedTinyInteger('health_score')->default(70);
            $table->unsignedTinyInteger('seo_score')->default(70);
            $table->string('risk_score')->default('low');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
        Schema::create('content_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->constrained('content_items')->cascadeOnDelete();
            $table->json('snapshot');
            $table->string('reason')->nullable();
            $table->string('created_by')->default('system');
            $table->timestamps();
        });
        Schema::create('ai_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->nullable()->constrained('content_items')->nullOnDelete();
            $table->string('action_type')->index();
            $table->string('risk_level')->default('low')->index();
            $table->string('status')->default('completed')->index();
            $table->text('summary');
            $table->json('payload')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type')->default('topic_gap')->index();
            $table->string('topic');
            $table->string('keyword')->nullable()->index();
            $table->text('reason');
            $table->unsignedTinyInteger('priority')->default(50)->index();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('autonomy_level')->default(2);
            $table->unsignedSmallInteger('stale_after_days')->default(30);
            $table->unsignedSmallInteger('max_actions_per_run')->default(5);
            $table->boolean('auto_publish_low_risk')->default(true);
            $table->string('site_name')->default('AutoEvolve');
            $table->string('site_url')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('ai_actions');
        Schema::dropIfExists('content_versions');
        Schema::dropIfExists('content_items');
    }
};
