<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlogPostPriority extends Model
{
    use HasFactory;

    protected $fillable = [
        'wordpress_post_id',
        'priority',
    ];
}
