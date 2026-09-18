<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ActivateUserController;
use App\Http\Controllers\Admin\ApplicationSettingsController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\AuditExportController;
use App\Http\Controllers\Admin\ClientAttributeController;
use App\Http\Controllers\Admin\DeactivateUserController;
use App\Http\Controllers\Admin\DefaultClientNotificationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\RoleExportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserExportController;
use App\Http\Controllers\Admin\UserImportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Cadence\DisableNotificationScheduleController;
use App\Http\Controllers\Cadence\EmailTemplateController;
use App\Http\Controllers\Cadence\EnableNotificationScheduleController;
use App\Http\Controllers\Cadence\ManualNotificationController;
use App\Http\Controllers\Cadence\NotificationDeliveryController;
use App\Http\Controllers\Cadence\NotificationDeliveryExportController;
use App\Http\Controllers\Cadence\NotificationScheduleController;
use App\Http\Controllers\Cadence\NotificationScheduleExportController;
use App\Http\Controllers\Cadence\SendNotificationScheduleController;
use App\Http\Controllers\Cadence\UpcomingNotificationController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\Clients\ClientExportController;
use App\Http\Controllers\Clients\ClientImportController;
use App\Http\Controllers\Clients\RestoreClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Notifications\MarkAllNotificationsReadController;
use App\Http\Controllers\Notifications\MarkNotificationReadController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Profile\ColorSchemeController;
use App\Http\Controllers\Profile\LocaleController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\ProfilePasswordController;
use App\Http\Controllers\Profile\ProfilePreferencesController;
use App\Http\Controllers\RootController;
use App\Http\Controllers\Support\ChangelogController;
use App\Http\Controllers\Support\UserGuideController;
use Illuminate\Support\Facades\Route;

Route::get('/', RootController::class)->name('root');

/*
| Guests may only reach the pages needed to get into the application. There is no
| registration: accounts are created by authorised users from inside Belo Cadence.
*/
Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    /*
    | Clients. The import and export routes are declared before the resource so they
    | are not mistaken for a client identifier.
    */
    Route::get('clients/export', ClientExportController::class)->name('clients.export');
    // Importing is two steps: upload the file, then confirm how its columns are matched.
    Route::get('clients/import', [ClientImportController::class, 'create'])->name('clients.import.create');
    Route::post('clients/import', [ClientImportController::class, 'store'])->name('clients.import.store');
    Route::get('clients/import/mapping', [ClientImportController::class, 'mapping'])->name('clients.import.mapping');
    Route::post('clients/import/mapping', [ClientImportController::class, 'run'])->name('clients.import.run');
    Route::get('clients/import/template', [ClientImportController::class, 'template'])->name('clients.import.template');
    // An archived client stays readable, which is where it is restored from. Editing,
    // archiving and creating schedules deliberately do not accept an archived client.
    Route::resource('clients', ClientController::class)->withTrashed(['show']);
    Route::post('clients/{client}/restore', RestoreClientController::class)->withTrashed()->name('clients.restore');

    // Creating a schedule in the context of one client, which fixes it to that client.
    Route::get('clients/{client}/schedules/create', [NotificationScheduleController::class, 'createForClient'])
        ->name('clients.schedules.create');
    Route::post('clients/{client}/schedules', [NotificationScheduleController::class, 'storeForClient'])
        ->name('clients.schedules.store');

    Route::prefix('cadence')->name('cadence.')->group(function (): void {
        Route::get('upcoming', UpcomingNotificationController::class)->name('upcoming');

        Route::get('schedules/export', NotificationScheduleExportController::class)->name('schedules.export');
        // A schedule may also be created without a client, for a named list of addresses.
        Route::get('schedules/create', [NotificationScheduleController::class, 'create'])->name('schedules.create');
        Route::post('schedules', [NotificationScheduleController::class, 'store'])->name('schedules.store');
        Route::get('schedules', [NotificationScheduleController::class, 'index'])->name('schedules.index');
        Route::get('schedules/{schedule}', [NotificationScheduleController::class, 'show'])->name('schedules.show');
        Route::get('schedules/{schedule}/edit', [NotificationScheduleController::class, 'edit'])->name('schedules.edit');
        Route::put('schedules/{schedule}', [NotificationScheduleController::class, 'update'])->name('schedules.update');
        Route::delete('schedules/{schedule}', [NotificationScheduleController::class, 'destroy'])->name('schedules.destroy');
        Route::post('schedules/{schedule}/enable', EnableNotificationScheduleController::class)->name('schedules.enable');
        Route::post('schedules/{schedule}/disable', DisableNotificationScheduleController::class)->name('schedules.disable');
        // Sends the schedule's own message now, leaving its recurrence untouched.
        Route::post('schedules/{schedule}/send', SendNotificationScheduleController::class)->name('schedules.send');

        Route::get('email-templates', EmailTemplateController::class)->name('email-templates');

        Route::get('deliveries/export', NotificationDeliveryExportController::class)->name('deliveries.export');
        // Sending by hand: declared before the delivery routes so "send" is not read as an
        // identifier. It creates delivery history rather than editing it, which is why
        // history itself still has no store, update or destroy.
        Route::get('deliveries/send', [ManualNotificationController::class, 'create'])->name('deliveries.send');
        Route::post('deliveries/send', [ManualNotificationController::class, 'store'])->name('deliveries.send.store');
        Route::get('deliveries', [NotificationDeliveryController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{delivery}', [NotificationDeliveryController::class, 'show'])->name('deliveries.show');
    });

    Route::get('notifications', NotificationController::class)->name('notifications.index');
    Route::post('notifications/read-all', MarkAllNotificationsReadController::class)->name('notifications.read-all');
    Route::post('notifications/{notification}/read', MarkNotificationReadController::class)->name('notifications.read');

    /*
    | The guide and the changelog are open to anybody who can sign in: understanding the
    | application is not something that needs a permission.
    */
    Route::get('user-guide', UserGuideController::class)->name('support.user-guide');
    Route::get('changelog', ChangelogController::class)->name('support.changelog');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfilePasswordController::class, 'update'])->name('profile.password.update');
    Route::put('profile/preferences', [ProfilePreferencesController::class, 'update'])->name('profile.preferences.update');
    Route::patch('profile/color-scheme', [ColorSchemeController::class, 'update'])->name('profile.color-scheme.update');
    Route::patch('profile/locale', [LocaleController::class, 'update'])->name('profile.locale.update');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('users/export', UserExportController::class)->name('users.export');
        Route::get('users/import', [UserImportController::class, 'create'])->name('users.import.create');
        Route::post('users/import', [UserImportController::class, 'store'])->name('users.import.store');
        Route::get('users/import/mapping', [UserImportController::class, 'mapping'])->name('users.import.mapping');
        Route::post('users/import/mapping', [UserImportController::class, 'run'])->name('users.import.run');
        Route::get('users/import/template', [UserImportController::class, 'template'])->name('users.import.template');
        Route::resource('users', UserController::class)->except(['destroy']);
        Route::post('users/{user}/activate', ActivateUserController::class)->name('users.activate');
        Route::post('users/{user}/deactivate', DeactivateUserController::class)->name('users.deactivate');

        Route::get('roles/export', RoleExportController::class)->name('roles.export');
        Route::resource('roles', RoleController::class);

        // The route key is `attribute`, so a controller reads one the way it is named.
        Route::resource('client-attributes', ClientAttributeController::class)
            ->parameters(['client-attributes' => 'attribute']);

        Route::get('audit-log/export', AuditExportController::class)->name('audit-log.export');
        Route::get('audit-log', [AuditController::class, 'index'])->name('audit-log.index');
        Route::get('audit-log/{audit}', [AuditController::class, 'show'])->name('audit-log.show');

        Route::get('settings', [ApplicationSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [ApplicationSettingsController::class, 'update'])->name('settings.update');

        // A section of the settings page, so it has a form endpoint but no page of its own.
        Route::put('default-client-notifications', [DefaultClientNotificationController::class, 'update'])
            ->name('default-client-notifications.update');
    });
});
