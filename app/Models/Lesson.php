<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = ['subject_id', 'date', 'time', 'type', 'number', 'title'];

    protected $casts = [
        'date'   => 'date',
        'number' => 'integer',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Короткая подпись для шапки колонки: «23.09 · ЛБ-3». */
    public function shortLabel(): string
    {
        $kind = $this->type === 'lecture' ? 'ЛК' : 'ЛБ';
        $num  = $this->number ? "-{$this->number}" : '';
        return $this->date->format('d.m') . " · {$kind}{$num}";
    }
}
