<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDevice extends Model
{
    use HasFactory;
    protected $fillable = ['student_id','device_id','device_name','platform','app_version','last_seen_at','ip_address','is_active'];
    protected function casts(): array { return ['last_seen_at' => 'datetime', 'is_active' => 'boolean']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
}
