<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplateContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_template_id',
        'content_json',
        'content_html'
    ];

    protected $casts = [
        'content_json' => 'array'
    ];

    public function template()
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
