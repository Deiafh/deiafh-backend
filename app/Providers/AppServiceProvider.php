<?php

namespace App\Providers;

use App\Enums\PricingEntityType;
use App\Models\Item;
use App\Models\ItemOptionValue;
use App\Models\ItemSize;
use App\Support\ActivityChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Request-scoped collector of model old→new diffs for activity logging.
        $this->app->singleton(ActivityChanges::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'item' => Item::class,
            'size' => ItemSize::class,
            'option_value' => ItemOptionValue::class,
        ]);

        // Super-admin bypasses every permission/ability check.
        Gate::before(fn($user) => $user->hasRole('super_admin') ? true : null);

        // Collect model field changes (old→new) so the activity-log middleware
        // can attach a diff to each logged action.
        foreach (['created', 'updated', 'deleted'] as $event) {
            Event::listen("eloquent.$event: *", function ($eventName, $data) use ($event) {
                $model = $data[0] ?? null;
                if ($model instanceof Model) {
                    app(ActivityChanges::class)->record($event, $model);
                }
            });
        }
    }
}
