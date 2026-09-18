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
     * What fills each blank of the schedule's email template: a client attribute, or a
     * literal typed for this schedule alone. Nullable, because most templates leave no
     * blanks and most schedules therefore bind nothing.
     */
    public function up(): void
    {
        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->json('template_bindings')->nullable()->after('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('template_bindings');
        });
    }
};
