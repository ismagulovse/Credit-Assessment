<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['academic_year_id', 'name', 'required_labs'];

    protected $casts = ['required_labs' => 'integer'];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'subject_group')->withTimestamps();
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('date')->orderBy('time');
    }

    /** Порог обязательных лаб: из предмета или из конфига. */
    public function requiredLabs(): int
    {
        return $this->required_labs ?? (int) config('attendance.required_labs');
    }
}
