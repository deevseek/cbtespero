<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamToken extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id','token','is_active','expires_at'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'expires_at' => 'datetime'];
    }
}
