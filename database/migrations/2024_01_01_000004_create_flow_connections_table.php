<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('flows')->cascadeOnDelete();
            $table->foreignId('from_node_id')->constrained('flow_nodes')->cascadeOnDelete();
            $table->foreignId('to_node_id')->constrained('flow_nodes')->cascadeOnDelete();
            $table->string('condition_value')->nullable(); // 'true' or 'false' for condition branches
            $table->timestamps();

            $table->index(['flow_id']);
            $table->index(['from_node_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_connections');
    }
};
