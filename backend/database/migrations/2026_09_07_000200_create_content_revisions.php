<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('content_item_id')->constrained('content_items')->cascadeOnDelete();
            $table->foreignUuid('ai_action_id')->nullable()->constrained('ai_actions')->nullOnDelete();
            $table->json('proposed_snapshot');
            $table->text('reason')->nullable();
            $table->string('risk_level')->default('medium')->index();
            $table->string('status')->default('pending')->index();
            $table->string('created_by')->default('ai');
            $table->string('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['content_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
    }
};
