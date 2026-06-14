<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Subject;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /** Добавить занятие к предмету. */
    public function store(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'date'   => ['required', 'date'],
            'time'   => ['nullable', 'string', 'max:5'],
            'type'   => ['required', 'in:lecture,lab'],
            'number' => ['nullable', 'integer', 'min:1', 'max:999'],
            'title'  => ['nullable', 'string', 'max:150'],
        ], [
            'date.required' => 'Укажите дату занятия.',
            'type.required' => 'Выберите тип занятия.',
        ]);

        $subject->lessons()->create($data);

        return back()->with('status', 'Занятие добавлено.');
    }

    public function destroy(Subject $subject, Lesson $lesson)
    {
        abort_unless($lesson->subject_id === $subject->id, 404);
        $lesson->delete();

        return back()->with('status', 'Занятие удалено.');
    }
}
