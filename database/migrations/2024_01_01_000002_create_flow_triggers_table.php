<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained('flows')->cascadeOnDelete();
            $table->string('type'); // model, webhook, schedule, manual
            $table->string('model_class')->nullable();
            $table->string('event')->nullable(); // created, updated, deleted
            $table->json('conditions')->nullable();
            $table->timestamps();

            $table->index(['type', 'model_class', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_triggers');
    }
};
