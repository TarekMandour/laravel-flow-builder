<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('flows')->cascadeOnDelete();
            $table->string('type'); // trigger, condition, action, operation, integration
            $table->string('name');
            $table->json('data')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('position_x')->default(0);
            $table->integer('position_y')->default(0);
            $table->timestamps();

            $table->index(['flow_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_nodes');
    }
};
