<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use App\Services\StudentRosterTemplate;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /** Скачать пустой шаблон списка студентов. */
    public function downloadTemplate(StudentRosterTemplate $template)
    {
        return $template->download();
    }

    /** Импорт студентов в группу из заполненного шаблона. */
    public function import(Request $request, Group $group, StudentRosterTemplate $template)
    {
        $maxKb = (int) config('attendance.upload_max_size_mb') * 1024;
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', "max:$maxKb"],
        ], [
            'file.required' => 'Выберите файл.',
            'file.mimes'    => 'Нужен файл Excel (.xlsx).',
        ]);

        $names = $template->parseNames($request->file('file')->getRealPath());

        $added = 0;
        foreach ($names as $name) {
            $student = $group->students()->firstOrCreate(
                ['full_name' => $name],
                ['subgroup' => 1],
            );
            if ($student->wasRecentlyCreated) {
                $added++;
            }
        }

        return redirect()->route('groups.show', $group)
            ->with('status', "Импортировано студентов: $added (из " . count($names) . ' в файле).');
    }

    /** Добавить одного студента в группу. */
    public function store(Request $request, Group $group)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'subgroup'  => ['nullable', 'integer', 'min:1', 'max:9'],
        ], [
            'full_name.required' => 'Введите ФИО.',
        ]);

        $group->students()->create([
            'full_name' => trim($data['full_name']),
            'subgroup'  => $data['subgroup'] ?? 1,
        ]);

        return redirect()->route('groups.show', $group)->with('status', 'Студент добавлен.');
    }

    /** Пакетное добавление: список ФИО, по строке на студента. */
    public function storeBulk(Request $request, Group $group)
    {
        $data = $request->validate([
            'names'    => ['required', 'string'],
            'subgroup' => ['nullable', 'integer', 'min:1', 'max:9'],
        ], [
            'names.required' => 'Вставьте список ФИО.',
        ]);

        $subgroup = $data['subgroup'] ?? 1;
        $lines = preg_split('/\r\n|\r|\n/', $data['names']);

        $added = 0;
        foreach ($lines as $line) {
            $name = trim($line);
            if ($name === '') {
                continue;
            }
            $group->students()->firstOrCreate(
                ['full_name' => $name],
                ['subgroup' => $subgroup],
            );
            $added++;
        }

        return redirect()->route('groups.show', $group)
            ->with('status', "Добавлено студентов: $added.");
    }

    /** Удалить студента. */
    public function destroy(Group $group, Student $student)
    {
        abort_unless($student->group_id === $group->id, 404);
        $student->delete();

        return redirect()->route('groups.show', $group)->with('status', 'Студент удалён.');
    }
}
