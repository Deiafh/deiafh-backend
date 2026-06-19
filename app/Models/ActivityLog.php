<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'concealed',
        'action_key',
        'description',
        'method',
        'url',
        'properties',
        'ip',
    ];

    protected $casts = [
        'concealed' => 'boolean',
        'properties' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
