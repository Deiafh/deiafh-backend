<?php

namespace App\Providers;

use App\Enums\PricingEntityType;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            PricingEntityType::Item->value => 'App\Models\Item',
            PricingEntityType::Size->value => 'App\Models\ItemSize',
            PricingEntityType::OptionValue->value => 'App\Models\ItemOptionValue',
        ]);
    }
}
