<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A delivery can now be produced by a person as well as by the scheduler.
     *
     * A manual send belongs to no schedule, so the schedule becomes optional. Where a
     * delivery came from is stated by `is_manual` rather than inferred from that null:
     * the flag is what tables, filters and exports read, and it cannot be confused with
     * a schedule that has since been removed. `triggered_by_user_id` records the person
     * who asked for it, and stays null for the scheduler.
     *
     * The occurrence unique index still covers (schedule, scheduled_for), and nulls
     * never collide there. That is exactly right: two manual sends to the same client in
     * the same second are two deliveries, not one duplicated occurrence.
     */
    public function up(): void
    {
        Schema::table('client_notification_deliveries', function (Blueprint $table) {
            $table->foreignId('client_notification_schedule_id')->nullable()->change();
        });

        Schema::table('client_notification_deliveries', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false);
            $table->foreignId('triggered_by_user_id')->nullable()->constrained('users')->restrictOnDelete();

            $table->index(['is_manual', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Manual deliveries cannot exist without these columns, so reversing the feature
     * removes them. Nothing produced by the scheduler is touched.
     */
    public function down(): void
    {
        Schema::table('client_notification_deliveries', function (Blueprint $table) {
            $table->dropIndex(['is_manual', 'scheduled_for']);
            $table->dropForeign(['triggered_by_user_id']);
            $table->dropColumn(['is_manual', 'triggered_by_user_id']);
        });

        DB::table('client_notification_deliveries')->whereNull('client_notification_schedule_id')->delete();

        Schema::table('client_notification_deliveries', function (Blueprint $table) {
            $table->foreignId('client_notification_schedule_id')->nullable(false)->change();
        });
    }
};
