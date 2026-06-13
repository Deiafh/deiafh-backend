<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\MainPageHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

class MainPageHeaderController extends Controller
{
    public function index()
    {
        return MainPageHeader::orderBy('sort')->get()->map(fn($h) => $this->withUrl($h));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'file'  => 'required|file|mimes:jpeg,png,jpg,webp,gif,mp4,webm,mov|max:102400',
        ]);

        $file = $request->file('file');
        $mime = $file->getMimeType();
        $isVideo = str_starts_with($mime, 'video/');

        if (!is_dir(base_path('storage/headers'))) {
            mkdir(base_path('storage/headers'), 0755, true);
        }

        if ($isVideo) {
            $ext = $file->getClientOriginalExtension() ?: 'mp4';
            $path = 'storage/headers/' . uniqid() . '.' . $ext;
            $file->move(base_path('storage/headers'), basename($path));
            $type = 'video';
        } else {
            $manager = new ImageManager(new Driver());
            $path = 'storage/headers/' . uniqid() . '.webp';
            $manager->read($file)
                ->scale(width: 1920)
                ->encode(new AutoEncoder('webp', quality: 85))
                ->save(base_path($path));
            $type = 'image';
        }

        $sort = (MainPageHeader::max('sort') ?? 0) + 1;

        $header = MainPageHeader::create([
            'sort'  => $sort,
            'url'   => $path,
            'title' => $request->title,
            'type'  => $type,
        ]);

        return response()->json($this->withUrl($header), 201);
    }

    public function update(Request $request, $sort)
    {
        $header = MainPageHeader::findOrFail($sort);
        $request->validate(['title' => 'required|string|max:255']);
        $header->update(['title' => $request->title]);
        return response()->json($this->withUrl($header));
    }

    public function destroy($sort)
    {
        $header = MainPageHeader::findOrFail($sort);
        if ($header->url && file_exists(base_path($header->url))) {
            @unlink(base_path($header->url));
        }
        $header->delete();
        return response()->json(['message' => 'تم الحذف بنجاح']);
    }

    public function updateSort(Request $request)
    {
        $request->validate([
            'sorts'   => 'required|array|min:1',
            'sorts.*' => 'required|integer',
        ]);

        // sorts = old sort values in the desired new order
        $sorts = $request->sorts;

        DB::transaction(function () use ($sorts) {
            foreach ($sorts as $i => $oldSort) {
                DB::table('main_page_headers')
                    ->where('sort', $oldSort)
                    ->update(['sort' => -($i + 1)]);
            }
            foreach ($sorts as $i => $oldSort) {
                DB::table('main_page_headers')
                    ->where('sort', -($i + 1))
                    ->update(['sort' => $i + 1]);
            }
        });

        return response()->json(['message' => 'تم تحديث الترتيب']);
    }

    private function withUrl($header)
    {
        $header->url = url($header->url);
        return $header;
    }
}
