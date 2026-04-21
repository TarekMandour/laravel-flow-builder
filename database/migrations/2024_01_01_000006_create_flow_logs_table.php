<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('flow_executions')->cascadeOnDelete();
            $table->foreignId('node_id')->nullable()->constrained('flow_nodes')->nullOnDelete();
            $table->string('status'); // running, success, failed, skipped
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['execution_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_logs');
    }
};
