<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $fillable = ['name', 'emoji', 'image', 'color'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? url($this->image) : null;
    }

    protected $appends = ['image_url'];
    protected $hidden = ['image'];
}
