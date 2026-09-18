<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A schedule may now target a named list of addresses instead of a client.
     *
     * `client_id` becomes optional and `name` plus `recipients` describe the other kind
     * of target. `subject` and `message` carry the wording of the blank template, which
     * is the one template with no copy of its own.
     *
     * Deliveries gain `target_name`: the name of whatever the message was for, snapshotted
     * like everything else on a delivery, so renaming a client or a list never rewrites
     * history. The occurrence guard grows to include the recipient, because one occurrence
     * of a list schedule is now one email per address rather than one email.
     */
    public function up(): void
    {
        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();

            $table->string('name')->nullable();
            $table->json('recipients')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->string('target_name')->nullable();
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            // Named at creation as client_notification_deliveries_occurrence_unique; the
            // table rename left the index name behind.
            $table->dropUnique('client_notification_deliveries_occurrence_unique');

            $table->unique(
                ['notification_schedule_id', 'scheduled_for', 'recipient_email'],
                'notification_deliveries_occurrence_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * Schedules that target a list cannot exist without these columns, and neither can
     * the deliveries they produced, so reversing the feature removes both. A client
     * schedule keeps everything.
     */
    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->dropUnique('notification_deliveries_occurrence_unique');
        });

        DB::table('notification_deliveries')->whereNull('client_id')->delete();
        DB::table('notification_schedules')->whereNull('client_id')->delete();

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->dropColumn('target_name');
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable(false)->change();

            $table->unique(
                ['notification_schedule_id', 'scheduled_for'],
                'client_notification_deliveries_occurrence_unique'
            );
        });

        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn(['name', 'recipients', 'subject', 'message']);
            $table->foreignId('client_id')->nullable(false)->change();
        });
    }
};
