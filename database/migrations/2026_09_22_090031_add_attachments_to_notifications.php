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
     * A schedule may attach the files a client answered a file attribute with. It stores
     * the bindings — the same attribute-and-path tokens its template values use — rather
     * than the files themselves, so replacing a client's file changes what the next email
     * carries without anybody editing the schedule.
     *
     * A delivery stores the opposite: the names of what actually went out. Deliveries are
     * snapshots, so history says what was attached even after the file has been replaced
     * or deleted.
     */
    public function up(): void
    {
        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->json('attachment_bindings')->nullable()->after('template_bindings');
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('body_html');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_schedules', function (Blueprint $table) {
            $table->dropColumn('attachment_bindings');
        });

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->dropColumn('attachments');
        });
    }
};
