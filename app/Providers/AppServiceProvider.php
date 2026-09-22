<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ApplicationSettings;
use App\Models\Audit;
use App\Models\Client;
use App\Models\ClientAttribute;
use App\Models\ClientAttributeFile;
use App\Models\ClientAttributeValue;
use App\Models\DefaultClientNotification;
use App\Models\NotificationDelivery;
use App\Models\NotificationSchedule;
use App\Models\Role;
use App\Models\User;
use App\Support\ViewerTimezone;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolved once per request: the timezone a page is rendered in does not change mid-request.
        $this->app->scoped(ViewerTimezone::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(CarbonImmutable::class);

        Model::preventLazyLoading(! $this->app->isProduction());

        // Stable, readable morph keys, so audit history stays meaningful even if
        // classes are ever moved or renamed.
        Relation::enforceMorphMap([
            'application_settings' => ApplicationSettings::class,
            'audit' => Audit::class,
            'client' => Client::class,
            'client_attribute' => ClientAttribute::class,
            'client_attribute_file' => ClientAttributeFile::class,
            'client_attribute_value' => ClientAttributeValue::class,
            'notification_delivery' => NotificationDelivery::class,
            'notification_schedule' => NotificationSchedule::class,
            'default_client_notification' => DefaultClientNotification::class,
            'role' => Role::class,
            'user' => User::class,
        ]);

        Password::defaults(fn (): Password => Password::min(12)->letters()->numbers());

        Vite::prefetch(concurrency: 3);
    }
}
