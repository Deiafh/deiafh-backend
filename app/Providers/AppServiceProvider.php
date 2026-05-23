<?php

namespace App\Providers;

use App\Enums\PricingEntityType;
use App\Models\Item;
use App\Models\ItemOptionValue;
use App\Models\ItemSize;
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
            'item' => Item::class,
            'size' => ItemSize::class,
            'option_value' => ItemOptionValue::class,
        ]);
    }
}
