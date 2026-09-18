<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drops "client" from the notification domain, because a schedule need no longer
     * have a client.
     *
     * Schedules and deliveries can now target either a client or a named list of
     * addresses, so every name that claimed a client was about to start lying. The
     * stored values move with the code: morph keys and audit actions keep old entries
     * readable, and permission rows are renamed in place so the roles holding them keep
     * their grants.
     *
     * `default_client_notifications` is deliberately left alone: those really are the
     * defaults offered when creating a client.
     */
    public function up(): void
    {
        Schema::rename('client_notification_schedules', 'notification_schedules');
        Schema::rename('client_notification_deliveries', 'notification_deliveries');

        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->renameColumn('client_notification_schedule_id', 'notification_schedule_id');
        });

        $this->renameStoredValues([
            'client_notification_schedule' => 'notification_schedule',
            'client_notification_delivery' => 'notification_delivery',
        ], [
            'client-notifications.' => 'notifications.',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_deliveries', function (Blueprint $table) {
            $table->renameColumn('notification_schedule_id', 'client_notification_schedule_id');
        });

        Schema::rename('notification_deliveries', 'client_notification_deliveries');
        Schema::rename('notification_schedules', 'client_notification_schedules');

        $this->renameStoredValues([
            'notification_schedule' => 'client_notification_schedule',
            'notification_delivery' => 'client_notification_delivery',
        ], [
            'notifications.' => 'client-notifications.',
        ]);
    }

    /**
     * Moves the audit log's morph keys and action names, and the permission names, to
     * match the code.
     *
     * @param  array<string, string>  $morphKeys
     * @param  array<string, string>  $permissionPrefixes
     */
    private function renameStoredValues(array $morphKeys, array $permissionPrefixes): void
    {
        foreach ($morphKeys as $from => $to) {
            DB::table('audits')->where('auditable_type', $from)->update(['auditable_type' => $to]);

            // Audit actions are dotted values such as "client_notification_schedule.created".
            DB::table('audits')
                ->where('action', 'like', $from.'.%')
                ->orderBy('id')
                ->each(function (object $audit) use ($from, $to): void {
                    DB::table('audits')
                        ->where('id', $audit->id)
                        ->update(['action' => $to.mb_substr((string) $audit->action, mb_strlen($from))]);
                });
        }

        foreach ($permissionPrefixes as $from => $to) {
            DB::table('permissions')
                ->where('name', 'like', $from.'%')
                ->orderBy('id')
                ->each(function (object $permission) use ($from, $to): void {
                    DB::table('permissions')
                        ->where('id', $permission->id)
                        ->update(['name' => $to.mb_substr((string) $permission->name, mb_strlen($from))]);
                });
        }
    }
};
