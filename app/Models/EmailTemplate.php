<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'subject',
        'content_json',
        'content_html',
        'is_autosave'
    ];

    protected $casts = [
        'content_json' => 'array',
        'is_autosave' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
