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
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->enum('role', ['host', 'cohost', 'participant'])->default('participant');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->boolean('microphone_enabled')->default(true);
            $table->boolean('camera_enabled')->default(true);
            $table->boolean('screen_sharing')->default(false);
            $table->enum('status', ['waiting', 'joined', 'left', 'removed'])->default('waiting')->index();
            $table->json('connection_data')->nullable();
            $table->timestamps();

            $table->index(['meeting_id', 'status']);
            $table->index(['user_id', 'meeting_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};