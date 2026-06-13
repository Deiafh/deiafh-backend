<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuGroup extends Model
{
    protected $fillable = ['name'];

    public function branches()
    {
        return $this->hasMany(Branch::class, 'menu_group_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'menu_group_id')->orderBy('sort');
    }
}
