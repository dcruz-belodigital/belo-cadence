<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Deliveries are historical evidence: they snapshot the message that was produced
     * and are never recalculated from current client or template data.
     */
    public function up(): void
    {
        Schema::create('client_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_notification_schedule_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('template');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->string('sender_email');
            $table->string('sender_name');
            $table->string('subject');
            $table->longText('body_html');
            $table->timestamp('scheduled_for');
            $table->timestamp('attempted_at');
            $table->timestamp('sent_at')->nullable();
            $table->string('status');
            $table->text('failure_message')->nullable();
            $table->timestamps();

            // The database, not application memory, is what guarantees one delivery
            // per scheduled occurrence.
            $table->unique(
                ['client_notification_schedule_id', 'scheduled_for'],
                'client_notification_deliveries_occurrence_unique'
            );
            $table->index('client_id');
            $table->index(['status', 'scheduled_for']);
            $table->index('scheduled_for');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_notification_deliveries');
    }
};
