<?php

namespace App\Services;

use App\DTO\StudentResult;

/**
 * Превращает сырой результат парсинга в список StudentResult:
 * считает % посещаемости, проверяет порог обязательных лаб и
 * определяет оценку-автомат по шкале группы.
 */
class AttendanceCalculator
{
    public function __construct(
        private int $requiredLabs,
    ) {
    }

    /**
     * @param array $parsed результат AttendanceParser::parse()
     * @return StudentResult[]
     */
    public function calculate(array $parsed): array
    {
        $totalLessons = (int) $parsed['totalLessons'];
        $scales       = $parsed['gradeScales'];
        $results      = [];

        foreach ($parsed['groups'] as $groupName => $students) {
            $scale = $this->resolveScale($groupName, $scales);

            foreach ($students as $st) {
                $labsDone   = count($st['labs']);
                $visits     = (int) $st['visits'];
                $percent    = $totalLessons > 0
                    ? (int) round($visits / $totalLessons * 100)
                    : 0;
                $passed     = $labsDone >= $this->requiredLabs;
                $toThreshold = max(0, $this->requiredLabs - $labsDone);
                $autoGrade  = $this->autoGrade($labsDone, $scale);

                $results[] = new StudentResult(
                    name: $st['name'],
                    group: $groupName,
                    labsDone: $labsDone,
                    labNumbers: $st['labs'],
                    visits: $visits,
                    visitPercent: $percent,
                    passedThreshold: $passed,
                    labsToThreshold: $toThreshold,
                    autoGrade: $autoGrade,
                );
            }
        }

        return $results;
    }

    /**
     * Подбирает шкалу для группы.
     * Если точного имени нет, но есть ровно одна общая шкала — используем её.
     */
    private function resolveScale(string $groupName, array $scales): array
    {
        if (isset($scales[$groupName])) {
            return $scales[$groupName];
        }
        if (count($scales) === 1) {
            return reset($scales);
        }
        return [];
    }

    /**
     * Оценка-автомат: берём максимальную планку, для которой labsDone >= нужного.
     */
    private function autoGrade(int $labsDone, array $scale): ?string
    {
        $grade = null;
        foreach ($scale as $rule) {
            if ($labsDone >= $rule['labs']) {
                $grade = $rule['grade'];
            }
        }
        return $grade;
    }
}
