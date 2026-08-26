<?php

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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('meeting_code', 12)->unique()->index();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('password')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->enum('status', ['scheduled', 'active', 'ended', 'cancelled'])->default('scheduled')->index();
            $table->unsignedInteger('max_participants')->default(50);
            $table->boolean('waiting_room_enabled')->default(true);
            $table->boolean('allow_participants_before_host')->default(false);
            $table->boolean('recording_enabled')->default(false);
            $table->timestamps();

            $table->index(['host_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};