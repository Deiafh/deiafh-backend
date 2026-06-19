<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
use App\Models\Category;

$cats = Category::with(['items' => function ($q) {
    $q->with(['stockRestrictions' => function ($q2) {
        $q2->where(function ($q3) {
            $q3->whereNull('until')->orWhere('until', '>', now());
        })->with('branch:id,title');
    }])->orderBy('sort');
}])->orderBy('sort')->get();

echo "Categories: " . $cats->count() . "\n";
foreach ($cats as $cat) {
    $items = $cat->items;
    echo "  Category '{$cat->title}': items=" . $items->count() . "\n";
    if ($items->count() > 0) {
        $item = $items->first();
        echo "    First item '{$item->title}': restrictions count=" . $item->stockRestrictions->count() . "\n";
        foreach ($item->stockRestrictions as $r) {
            echo "      restriction id={$r->id} branch_id=" . ($r->branch_id ?? 'NULL') . "\n";
        }
    }
}
