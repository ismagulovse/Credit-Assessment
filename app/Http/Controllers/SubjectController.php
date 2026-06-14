<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /** Список предметов. */
    public function index()
    {
        $subjects = Subject::withCount(['lessons', 'groups'])->orderBy('name')->get();
        $groups   = Group::orderBy('name')->get();

        return view('subjects.index', compact('subjects', 'groups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'required_labs' => ['nullable', 'integer', 'min:1', 'max:50'],
            'group_ids'     => ['array'],
            'group_ids.*'   => ['exists:groups,id'],
        ], ['name.required' => 'Введите название предмета.']);

        $subject = Subject::create([
            'name'          => $data['name'],
            'required_labs' => $data['required_labs'] ?? null,
        ]);

        if (! empty($data['group_ids'])) {
            $subject->groups()->sync($data['group_ids']);
        }

        return redirect()->route('subjects.show', $subject)
            ->with('status', "Предмет «{$subject->name}» создан.");
    }

    /** Журнал предмета. */
    public function show(Subject $subject)
    {
        $subject->load([
            'groups.students' => fn ($q) => $q->orderBy('full_name'),
            'lessons.attendances',
        ]);

        $allGroups = Group::orderBy('name')->get();

        // Карта отметок: [student_id][lesson_id] => Attendance
        $marks = [];
        foreach ($subject->lessons as $lesson) {
            foreach ($lesson->attendances as $a) {
                $marks[$a->student_id][$a->lesson_id] = $a;
            }
        }

        return view('subjects.show', [
            'subject'   => $subject,
            'allGroups' => $allGroups,
            'marks'     => $marks,
            'statuses'  => AttendanceStatus::options(),
        ]);
    }

    public function destroy(Subject $subject)
    {
        $name = $subject->name;
        $subject->delete();

        return redirect()->route('subjects.index')->with('status', "Предмет «$name» удалён.");
    }

    /** Прикрепить группу к предмету. */
    public function attachGroup(Request $request, Subject $subject)
    {
        $data = $request->validate(['group_id' => ['required', 'exists:groups,id']]);
        $subject->groups()->syncWithoutDetaching([$data['group_id']]);

        return back()->with('status', 'Группа добавлена в предмет.');
    }

    public function detachGroup(Subject $subject, Group $group)
    {
        $subject->groups()->detach($group->id);

        return back()->with('status', 'Группа откреплена.');
    }

    /**
     * Сохранить отметку одной ячейки (студент+занятие).
     * Пустой status => удалить отметку (студент отсутствовал).
     * Возвращает JSON для обновления сетки без перезагрузки.
     */
    public function mark(Request $request, Subject $subject)
    {
        $data = $request->validate([
            'lesson_id'  => ['required', 'exists:lessons,id'],
            'student_id' => ['required', 'exists:students,id'],
            'status'     => ['nullable', 'string'],
            'lab_number' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $status = $data['status'] ?? null;

        if ($status === null || $status === '') {
            Attendance::where('lesson_id', $data['lesson_id'])
                ->where('student_id', $data['student_id'])
                ->delete();

            return response()->json(['ok' => true, 'emoji' => '', 'status' => null, 'lab_number' => null]);
        }

        $enum = AttendanceStatus::tryFrom($status);
        abort_if($enum === null, 422, 'Неизвестный статус');

        $labNumber = $enum->isLabDone() ? ($data['lab_number'] ?? null) : null;

        $att = Attendance::updateOrCreate(
            ['lesson_id' => $data['lesson_id'], 'student_id' => $data['student_id']],
            ['status' => $enum, 'lab_number' => $labNumber],
        );

        $cell = $enum->emoji() . ($labNumber ? " №$labNumber" : '');

        return response()->json([
            'ok'         => true,
            'emoji'      => $enum->emoji(),
            'cell'       => $cell,
            'status'     => $enum->value,
            'lab_number' => $labNumber,
        ]);
    }
}
