<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /** Список всех групп. */
    public function index()
    {
        $groups = Group::with('academicYear')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $years = AcademicYear::orderByDesc('is_active')->orderBy('name')->get();

        return view('groups.index', compact('groups', 'years'));
    }

    /** Создать группу. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'new_year'         => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'Введите название группы.',
        ]);

        // Если ввели новый учебный год текстом — создаём его.
        $yearId = $data['academic_year_id'] ?? null;
        if (! empty($data['new_year'])) {
            $yearId = AcademicYear::firstOrCreate(['name' => $data['new_year']])->id;
        }

        Group::create([
            'name'             => $data['name'],
            'academic_year_id' => $yearId,
        ]);

        return redirect()->route('groups.index')->with('status', "Группа «{$data['name']}» создана.");
    }

    /** Страница группы со списком студентов. */
    public function show(Group $group)
    {
        $group->load(['academicYear', 'students' => fn ($q) => $q->orderBy('full_name')]);

        return view('groups.show', compact('group'));
    }

    /** Удалить группу (вместе со студентами — каскад). */
    public function destroy(Group $group)
    {
        $name = $group->name;
        $group->delete();

        return redirect()->route('groups.index')->with('status', "Группа «$name» удалена.");
    }
}
