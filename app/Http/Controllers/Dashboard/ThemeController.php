<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Theme;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $theme = Theme::first();
        return response()->json($theme);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Theme $theme, Request $request)
    {
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Theme $theme, Request $request)
    {
        $data = $request->validate([
            'header' => 'required|string',
            'footer' => 'required|string',
            'icon' => 'required|string',
            'icon_back' => 'required|string',
            'icon_border' => 'required|string',
            'button_back' => 'required|string',
            'button_color' => 'required|string',
            'cat_header_back' => 'required|string',
            'cat_header_color' => 'required|string',
            'cat_header_active_back' => 'required|string',
            'cat_header_active_color' => 'required|string',
            'order_footer_back' => 'required|string',
            'order_footer_color' => 'required|string',
            'order_footer_n_back' => 'required|string',
            'order_footer_n_color' => 'required|string',
            'footer_color' => 'required|string',
            'radio_border' => 'required|string',
            'radio_back' => 'required|string',
            'radio_color' => 'required|string',
            'text' => 'required|string',
            'modal_header_back' => 'required|string',
            'modal_header_color' => 'required|string',
        ]);

        $theme->update($data);

        return response()->json([
            'status' => true,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
