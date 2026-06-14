<?php

namespace App\DTO;

/**
 * Результат расчёта по одному студенту.
 */
class StudentResult
{
    /**
     * @param string    $name           ФИО
     * @param string    $group          Название группы (ИВТ41б, ПИ41б…)
     * @param int       $labsDone       Кол-во сданных лаб (уникальные номера)
     * @param int[]     $labNumbers     Номера сданных лаб
     * @param int       $visits         Кол-во посещённых занятий
     * @param int       $visitPercent   % посещаемости (целое)
     * @param bool      $passedThreshold Сдал >= порога обязательных лаб
     * @param int       $labsToThreshold Сколько лаб осталось до порога (0 если пройден)
     * @param string|null $autoGrade    Оценка-автомат или null
     */
    public function __construct(
        public string $name,
        public string $group,
        public int $labsDone,
        public array $labNumbers,
        public int $visits,
        public int $visitPercent,
        public bool $passedThreshold,
        public int $labsToThreshold,
        public ?string $autoGrade,
    ) {
    }

    /** Категория студента для группировки в UI. */
    public function category(): string
    {
        if ($this->autoGrade !== null) {
            return 'auto';        // 🏆 получает автомат
        }
        if ($this->passedThreshold) {
            return 'exam';        // ✅ допущен к экзамену
        }
        return 'below';           // 📊 не дотянул до порога
    }

    public function toArray(): array
    {
        return [
            'name'             => $this->name,
            'group'            => $this->group,
            'labs_done'        => $this->labsDone,
            'lab_numbers'      => $this->labNumbers,
            'visits'           => $this->visits,
            'visit_percent'    => $this->visitPercent,
            'passed_threshold' => $this->passedThreshold,
            'labs_to_threshold' => $this->labsToThreshold,
            'auto_grade'       => $this->autoGrade,
            'category'         => $this->category(),
        ];
    }
}
