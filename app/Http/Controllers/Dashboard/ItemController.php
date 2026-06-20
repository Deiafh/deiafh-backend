<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ActiveStatus;
use App\Enums\PricingEntityType;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with(['category:id,title', 'branches:id,title', 'labels:id,name,emoji,color', 'priceForBranch'])
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->orderBy('category_id')
            ->orderBy('sort');

        return response()->json($query->get()->map(fn($item) => array_merge($item->toArray(), [
            'branch_ids' => $item->branches->pluck('id'),
            'label_ids'  => $item->labels->pluck('id'),
        ])));
    }

    public function show(Item $item)
    {
        $item->load([
            'category:id,title',
            'branches:id,title',
            'labels:id,name,emoji,color',
            'sizes.prices',
            'options' => fn($q) => $q->with('values.prices'),
        ]);

        $prices = Price::where('entity_type', PricingEntityType::Item->value)
            ->where('entity_id', $item->id)
            ->get();

        return response()->json(array_merge($item->toArray(), [
            'branch_ids' => $item->branches->pluck('id'),
            'label_ids'  => $item->labels->pluck('id'),
            'prices'     => $prices,
        ]));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id'              => 'required|integer|exists:categories,id',
            'title'                    => 'required|string|max:255',
            'description'              => 'nullable|string',
            'old_price'                => 'nullable|numeric|min:0',
            'active'                   => 'in:active,inactive',
            'from'                     => 'nullable|date',
            'to'                       => 'nullable|date',
            'image'                    => 'nullable|file|mimes:jpg,jpeg,png,webp,gif',
            'branch_ids'               => 'nullable|array',
            'branch_ids.*'             => 'integer|exists:branches,id',
            'label_ids'                => 'nullable|array',
            'label_ids.*'              => 'integer|exists:labels,id',
            'base_price'               => 'required|numeric|min:0',
            'sizes'                              => 'nullable|array',
            'sizes.*.title'                      => 'required|string|max:255',
            'sizes.*.price'                      => 'required|numeric|min:0',
            'sizes.*.branch_prices'              => 'nullable|array',
            'sizes.*.branch_prices.*.branch_id'  => 'required|integer|exists:branches,id',
            'sizes.*.branch_prices.*.price'      => 'required|numeric|min:0',
            'branch_prices'            => 'nullable|array',
            'branch_prices.*.branch_id'=> 'required|integer|exists:branches,id',
            'branch_prices.*.price'    => 'required|numeric|min:0',
            'options'                  => 'nullable|array',
            'options.*.id'             => 'required|integer|exists:item_options,id',
            'options.*.option_type'    => 'nullable|in:optional,mandatory',
            'options.*.is_counter'     => 'nullable|boolean',
            'options.*.min_count'      => 'nullable|integer|min:0',
            'options.*.max_count'      => 'nullable|integer|min:0',
            'options.*.size_id'        => 'nullable|integer|exists:item_sizes,id',
        ]);

        $maxSort = Item::where('category_id', $data['category_id'])->max('sort') ?? 0;

        $imgPath = $this->storeImage($request);

        $item = Item::create([
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'old_price'   => $data['old_price'] ?? null,
            'active'      => $data['active'] ?? ActiveStatus::Active->value,
            'from'        => $data['from'] ?? null,
            'to'          => $data['to'] ?? null,
            'img'         => $imgPath ?? 'storage/items/placeholder.webp',
            'sort'        => $maxSort + 1,
        ]);

        Price::updateOrCreate(
            ['entity_type' => PricingEntityType::Item->value, 'entity_id' => $item->id, 'branch_id' => null],
            ['price' => $data['base_price']]
        );

        $item->branches()->sync($data['branch_ids'] ?? []);
        $item->labels()->sync($data['label_ids'] ?? []);

        foreach ($data['sizes'] ?? [] as $s) {
            $size = $item->sizes()->create(['title' => $s['title']]);
            Price::create([
                'entity_type' => PricingEntityType::Size->value,
                'entity_id'   => $size->id,
                'branch_id'   => null,
                'price'       => $s['price'],
            ]);
            foreach ($s['branch_prices'] ?? [] as $bp) {
                Price::create([
                    'entity_type' => PricingEntityType::Size->value,
                    'entity_id'   => $size->id,
                    'branch_id'   => $bp['branch_id'],
                    'price'       => $bp['price'],
                ]);
            }
        }

        foreach ($data['branch_prices'] ?? [] as $bp) {
            Price::create([
                'entity_type' => PricingEntityType::Item->value,
                'entity_id'   => $item->id,
                'branch_id'   => $bp['branch_id'],
                'price'       => $bp['price'],
            ]);
        }

        $optionsPivot = [];
        foreach ($data['options'] ?? [] as $opt) {
            $type = $opt['option_type'] ?? 'optional';
            $min  = $opt['min_count']   ?? 0;
            $max  = $opt['max_count']   ?? 0;
            $err  = $this->validateOptionCounts($type, $min, $max);
            if ($err) return response()->json(['message' => $err], 422);

            $optionsPivot[$opt['id']] = [
                'size_id'     => $opt['size_id']  ?? null,
                'option_type' => $type,
                'is_counter'  => $opt['is_counter'] ?? false,
                'min_count'   => $min,
                'max_count'   => $max,
            ];
        }
        if (!empty($optionsPivot)) {
            $item->options()->sync($optionsPivot);
        }

        return response()->json($this->fullItem($item), 201);
    }

    public function update(Request $request, Item $item)
    {
        $data = $request->validate([
            'category_id'  => 'required|integer|exists:categories,id',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'old_price'    => 'nullable|numeric|min:0',
            'active'       => 'in:active,inactive',
            'from'         => 'nullable|date',
            'to'           => 'nullable|date',
            'image'        => 'nullable|file|mimes:jpg,jpeg,png,webp,gif',
            'branch_ids'   => 'nullable|array',
            'branch_ids.*' => 'integer|exists:branches,id',
            'label_ids'    => 'nullable|array',
            'label_ids.*'  => 'integer|exists:labels,id',
            'base_price'   => 'required|numeric|min:0',
            'sort'         => 'nullable|integer|min:1',
        ]);

        $update = [
            'category_id' => $data['category_id'],
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'old_price'   => $data['old_price'] ?? null,
            'active'      => $data['active'] ?? $item->active,
            'from'        => $data['from'] ?? null,
            'to'          => $data['to'] ?? null,
        ];

        if (!empty($data['sort'])) $update['sort'] = $data['sort'];

        $imgPath = $this->storeImage($request);
        if ($imgPath) $update['img'] = $imgPath;

        $item->update($update);

        Price::updateOrCreate(
            ['entity_type' => PricingEntityType::Item->value, 'entity_id' => $item->id, 'branch_id' => null],
            ['price' => $data['base_price']]
        );

        $item->branches()->sync($data['branch_ids'] ?? []);
        $item->labels()->sync($data['label_ids'] ?? []);

        return response()->json($this->fullItem($item));
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return response()->json(['message' => 'deleted']);
    }

    public function updateSort(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'sorts'       => 'required|array',
            'sorts.*'     => 'integer|exists:items,id',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->sorts as $i => $id) {
                Item::where('id', $id)->update(['sort' => -($i + 1)]);
            }
            foreach ($request->sorts as $i => $id) {
                Item::where('id', $id)->update(['sort' => $i + 1]);
            }
        });

        return response()->json(['message' => 'sorted']);
    }

    // Branch prices
    public function upsertBranchPrice(Request $request, Item $item)
    {
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'price'     => 'required|numeric|min:0',
        ]);

        $price = Price::updateOrCreate(
            ['entity_type' => PricingEntityType::Item->value, 'entity_id' => $item->id, 'branch_id' => $data['branch_id']],
            ['price' => $data['price']]
        );

        return response()->json($price);
    }

    public function deleteBranchPrice(Request $request, Item $item)
    {
        $request->validate(['branch_id' => 'required|integer|exists:branches,id']);
        Price::where('entity_type', PricingEntityType::Item->value)
            ->where('entity_id', $item->id)
            ->where('branch_id', $request->branch_id)
            ->delete();

        return response()->json(['message' => 'deleted']);
    }

    // Sizes
    public function storeSizes(Request $request, Item $item)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
        ]);

        $size = $item->sizes()->create(['title' => $data['title']]);

        Price::create([
            'entity_type' => PricingEntityType::Size->value,
            'entity_id'   => $size->id,
            'branch_id'   => null,
            'price'       => $data['base_price'],
        ]);

        return response()->json($size->load('prices'), 201);
    }

    public function updateSize(Request $request, Item $item, $sizeId)
    {
        $size = $item->sizes()->findOrFail($sizeId);
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'base_price' => 'required|numeric|min:0',
        ]);

        $size->update(['title' => $data['title']]);

        Price::updateOrCreate(
            ['entity_type' => PricingEntityType::Size->value, 'entity_id' => $size->id, 'branch_id' => null],
            ['price' => $data['base_price']]
        );

        return response()->json($size->load('prices'));
    }

    public function upsertSizeBranchPrice(Request $request, Item $item, $sizeId)
    {
        $size = $item->sizes()->findOrFail($sizeId);
        $data = $request->validate([
            'branch_id' => 'required|integer|exists:branches,id',
            'price'     => 'required|numeric|min:0',
        ]);

        $price = Price::updateOrCreate(
            ['entity_type' => PricingEntityType::Size->value, 'entity_id' => $size->id, 'branch_id' => $data['branch_id']],
            ['price' => $data['price']]
        );

        return response()->json($price);
    }

    public function deleteSizeBranchPrice(Request $request, Item $item, $sizeId)
    {
        $size = $item->sizes()->findOrFail($sizeId);
        $request->validate(['branch_id' => 'required|integer|exists:branches,id']);

        Price::where('entity_type', PricingEntityType::Size->value)
            ->where('entity_id', $size->id)
            ->where('branch_id', $request->branch_id)
            ->delete();

        return response()->json(['message' => 'deleted']);
    }

    public function destroySize(Item $item, $sizeId)
    {
        $size = $item->sizes()->findOrFail($sizeId);
        $size->prices()->delete();
        $size->delete();
        return response()->json(['message' => 'deleted']);
    }

    // Link/unlink shared options
    public function attachOption(Request $request, Item $item)
    {
        $data = $request->validate([
            'item_option_id' => 'required|integer|exists:item_options,id',
            'size_id'        => 'nullable|integer|exists:item_sizes,id',
            'option_type'    => 'in:optional,mandatory',
            'is_counter'     => 'boolean',
            'min_count'      => 'integer|min:0',
            'max_count'      => 'integer|min:0',
        ]);

        $err = $this->validateOptionCounts(
            $data['option_type'] ?? 'optional',
            $data['min_count']   ?? 0,
            $data['max_count']   ?? 0
        );
        if ($err) return response()->json(['message' => $err], 422);

        $item->options()->syncWithoutDetaching([
            $data['item_option_id'] => [
                'size_id'     => $data['size_id']     ?? null,
                'option_type' => $data['option_type']  ?? 'optional',
                'is_counter'  => $data['is_counter']   ?? false,
                'min_count'   => $data['min_count']    ?? 0,
                'max_count'   => $data['max_count']    ?? 0,
            ]
        ]);

        return response()->json(['message' => 'attached']);
    }

    public function updateOptionPivot(Request $request, Item $item, $optionId)
    {
        $data = $request->validate([
            'size_id'     => 'nullable|integer|exists:item_sizes,id',
            'option_type' => 'in:optional,mandatory',
            'is_counter'  => 'boolean',
            'min_count'   => 'integer|min:0',
            'max_count'   => 'integer|min:0',
        ]);

        $err = $this->validateOptionCounts(
            $data['option_type'] ?? 'optional',
            $data['min_count']   ?? 0,
            $data['max_count']   ?? 0
        );
        if ($err) return response()->json(['message' => $err], 422);

        $item->options()->updateExistingPivot($optionId, $data);
        return response()->json(['message' => 'updated']);
    }

    private function validateOptionCounts(string $type, int $min, int $max): ?string
    {
        if ($type === 'mandatory' && $min < 1) {
            return 'الخيار الإلزامي يجب أن يكون الحد الأدنى 1 على الأقل';
        }
        if ($max > 0 && $max < $min) {
            return 'الحد الأقصى يجب أن يكون أكبر من أو يساوي الحد الأدنى';
        }
        return null;
    }

    public function detachOption(Item $item, $optionId)
    {
        $item->options()->detach($optionId);
        return response()->json(['message' => 'detached']);
    }

    private function storeImage(Request $request): ?string
    {
        if (!$request->hasFile('image')) return null;

        $manager  = new ImageManager(new Driver());
        $image    = $manager->read($request->file('image'));
        $filename = uniqid() . '.webp';
        $path     = storage_path('app/public/items/' . $filename);

        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

        $image->toWebp(85)->save($path);
        return 'storage/items/' . $filename;
    }

    private function fullItem(Item $item)
    {
        $item->load(['category:id,title', 'branches:id,title', 'labels:id,name,emoji,color', 'sizes.prices', 'options.values.prices']);
        $prices = Price::where('entity_type', PricingEntityType::Item->value)->where('entity_id', $item->id)->get();
        return array_merge($item->toArray(), [
            'branch_ids' => $item->branches->pluck('id'),
            'label_ids'  => $item->labels->pluck('id'),
            'prices'     => $prices,
        ]);
    }
}
