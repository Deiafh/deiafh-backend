<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = ['menu_group_id', 'url', 'sort', 'active'];

    protected $casts = ['sort' => 'integer'];

    public function menuGroup()
    {
        return $this->belongsTo(MenuGroup::class, 'menu_group_id');
    }
}
