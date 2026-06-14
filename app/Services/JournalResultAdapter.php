<?php

namespace App\Services;

use App\Models\Subject;

/**
 * Собирает из журнала предмета ту же структуру-массив, что отдаёт
 * AttendanceParser::parse() — чтобы переиспользовать AttendanceCalculator
 * без изменений (расчёт автомата «из журнала»).
 *
 * Формат:
 *   ['groups' => ['ИВТ41б' => [['name','labs'=>[номера],'visits'=>N], ...]],
 *    'totalLessons' => N, 'gradeScales' => [...]]
 */
class JournalResultAdapter
{
    /**
     * @param array<string, array<int, array{labs:int,grade:string}>> $gradeScales
     *        Шкалы оценок по группам (опционально; если пусто — автоматов не будет).
     */
    public function adapt(Subject $subject, array $gradeScales = []): array
    {
        $subject->load([
            'lessons',
            'groups.students',
            'lessons.attendances',
        ]);

        $totalLessons = $subject->lessons->count();

        // Отметки: [student_id][lesson_id] => Attendance
        $byStudent = [];
        foreach ($subject->lessons as $lesson) {
            foreach ($lesson->attendances as $a) {
                $byStudent[$a->student_id][$lesson->id] = $a;
            }
        }

        $groups = [];
        foreach ($subject->groups as $group) {
            $students = [];
            foreach ($group->students as $student) {
                $marks = $byStudent[$student->id] ?? [];

                $visits = 0;
                $labs   = [];
                foreach ($marks as $a) {
                    if ($a->status->countsAsVisit()) {
                        $visits++;
                    }
                    if ($a->status->isLabDone() && $a->lab_number !== null) {
                        $labs[$a->lab_number] = true;
                    }
                }

                $labNumbers = array_keys($labs);
                sort($labNumbers);

                $students[] = [
                    'name'   => $student->full_name,
                    'labs'   => $labNumbers,
                    'visits' => $visits,
                ];
            }
            $groups[$group->name] = $students;
        }

        return [
            'groups'       => $groups,
            'totalLessons' => $totalLessons,
            'gradeScales'  => $gradeScales,
        ];
    }
}
