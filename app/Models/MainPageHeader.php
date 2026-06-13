<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MainPageHeader extends Model
{
    protected $primaryKey = 'sort';
    public $incrementing = false;
    protected $keyType = 'integer';
    public $timestamps = false;
    protected $fillable = ['sort', 'url', 'title', 'type'];
}
