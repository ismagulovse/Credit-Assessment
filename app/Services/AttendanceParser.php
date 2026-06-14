<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Парсит xlsx-файл посещаемости в структуру:
 *   - groups:  [ 'ИВТ41б' => [ ['name'=>..,'labs'=>[..],'visits'=>..], ... ], ... ]
 *   - totalLessons: int  (число колонок-занятий)
 *   - gradeScales: [ 'ПИ41б' => [ ['labs'=>7,'grade'=>'Удовлетворительно'], ... ], ... ]
 *
 * Логика БЕЗ хардкода: число/имена групп, колонки занятий и шкалы оценок
 * определяются по содержимому файла, а не по фиксированным индексам.
 */
class AttendanceParser
{
    /**
     * @return array{groups: array<string, array>, totalLessons: int, gradeScales: array<string, array>}
     */
    public function parse(string $filePath, ?string $sheetName = null): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false); // нужны эмодзи/строки как есть
        $spreadsheet = $reader->load($filePath);

        $sheet = $sheetName
            ? $spreadsheet->getSheetByName($sheetName)
            : $spreadsheet->getSheet(0);

        if ($sheet === null) {
            $sheet = $spreadsheet->getSheet(0);
        }

        // Считываем весь лист в матрицу [row][col] = строковое значение.
        $grid = $this->readGrid($sheet);

        $lessonCols   = $this->detectLessonColumns($grid);
        $totalLessons = count($lessonCols);
        $groups       = $this->detectGroupsAndStudents($grid, $lessonCols);
        $gradeScales  = $this->detectGradeScales($grid);

        return [
            'groups'       => $groups,
            'totalLessons' => $totalLessons,
            'gradeScales'  => $gradeScales,
        ];
    }

    /**
     * Лист → [row => [colIndex => value]]. colIndex 1-based (A=1).
     * @return array<int, array<int, string>>
     */
    private function readGrid(Worksheet $sheet): array
    {
        $grid = [];
        foreach ($sheet->getRowIterator() as $row) {
            $r = $row->getRowIndex();
            $cellIt = $row->getCellIterator();
            $cellIt->setIterateOnlyExistingCells(true);
            foreach ($cellIt as $cell) {
                $val = (string) $cell->getValue();
                if ($val !== '') {
                    $grid[$r][$cell->getColumn() === '' ? 0 : \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn())] = $val;
                }
            }
        }
        return $grid;
    }

    /**
     * Колонки-занятия определяем по строке 2: там тип занятия (ЛК / ЛБ ...).
     * Возвращаем отсортированный список индексов колонок.
     * @return int[]
     */
    private function detectLessonColumns(array $grid): array
    {
        $cols = [];
        $typeRow = $grid[2] ?? [];
        foreach ($typeRow as $col => $val) {
            // тип занятия начинается с "ЛК" или "ЛБ"
            if (preg_match('/^\s*Л[КБ]/u', $val)) {
                $cols[] = $col;
            }
        }
        sort($cols);
        return $cols;
    }

    /**
     * Группы и студенты.
     * Заголовок группы: колонка A заполнена, B пуста, значение не #REF!.
     * Студент: A и B заполнены.
     * @return array<string, array>
     */
    private function detectGroupsAndStudents(array $grid, array $lessonCols): array
    {
        $groups = [];
        $current = null;

        foreach ($grid as $cells) {
            $a = trim($cells[1] ?? '');   // колонка A
            $b = trim($cells[2] ?? '');   // колонка B

            if ($a === '' || str_contains($a, '#REF')) {
                continue;
            }

            // Заголовок группы: A есть, B пусто.
            if ($b === '') {
                // Останавливаемся на служебных блоках (легенда эмодзи, "Оценки за экзамен").
                if ($this->looksLikeServiceRow($a)) {
                    $current = null;
                    continue;
                }
                $current = $a;
                $groups[$current] = [];
                continue;
            }

            // Строка студента.
            if ($current === null) {
                continue;
            }

            // Отсев строк-легенды (A = эмодзи/символ, а не ФИО).
            if (! $this->looksLikeStudentName($a)) {
                continue;
            }

            [$labs, $visits] = $this->countLabsAndVisits($cells, $lessonCols);
            $groups[$current][] = [
                'name'   => $a,
                'labs'   => $labs,
                'visits' => $visits,
            ];
        }

        return $groups;
    }

    /**
     * Похоже ли значение колонки A на ФИО студента.
     * ФИО содержит хотя бы 2 буквы (кириллица/латиница) подряд.
     * Отсекает строки-легенды, где в A стоит эмодзи/символ.
     */
    private function looksLikeStudentName(string $a): bool
    {
        return (bool) preg_match('/\p{L}{2,}/u', $a);
    }

    /** Строки-легенды/служебные блоки, которые не являются группой. */
    private function looksLikeServiceRow(string $a): bool
    {
        // одиночный эмодзи-символ (легенда) или явный заголовок блока
        if (mb_strlen($a) <= 2) {
            return true;
        }
        return str_contains(mb_strtolower($a), 'оценки за экзамен');
    }

    /**
     * Считает уникальные номера сданных лаб (N перед ✅) и число посещений.
     * @return array{0:int[],1:int}
     */
    private function countLabsAndVisits(array $cells, array $lessonCols): array
    {
        $labNumbers = [];
        $visits = 0;

        foreach ($lessonCols as $col) {
            $val = trim($cells[$col] ?? '');
            if ($val === '' || str_contains($val, '#REF')) {
                continue;
            }
            $visits++;

            // Все вхождения "<число>✅" → номера сданных лаб.
            if (preg_match_all('/(\d+)\x{2705}/u', $val, $m)) {
                foreach ($m[1] as $num) {
                    $labNumbers[(int) $num] = true;
                }
            }
        }

        $nums = array_keys($labNumbers);
        sort($nums);
        return [$nums, $visits];
    }

    /**
     * Шкалы оценок из блока "Оценки за экзамен".
     * Заголовок-строка содержит "Оценки за экзамен" в кол. A, а справа — имена групп.
     * Под каждой колонкой-группой строки вида "N лаб - Оценка".
     * @return array<string, array<int, array{labs:int,grade:string}>>
     */
    private function detectGradeScales(array $grid): array
    {
        // 1. Найти строку заголовка блока.
        $headerRow = null;
        foreach ($grid as $r => $cells) {
            foreach ($cells as $col => $val) {
                if (str_contains(mb_strtolower(trim($val)), 'оценки за экзамен')) {
                    $headerRow = $r;
                    break 2;
                }
            }
        }
        if ($headerRow === null) {
            return [];
        }

        // 2. Имена групп — непустые ячейки той же строки, кроме самой надписи.
        $groupCols = [];
        foreach (($grid[$headerRow] ?? []) as $col => $val) {
            $val = trim($val);
            if ($val === '' || str_contains(mb_strtolower($val), 'оценки за экзамен') || str_contains($val, '#REF')) {
                continue;
            }
            $groupCols[$col] = $val; // col => имя группы
        }

        // 3. Ниже заголовка читаем строки "N лаб - Оценка" по каждой колонке.
        $scales = [];
        foreach ($groupCols as $col => $groupName) {
            $rules = [];
            for ($r = $headerRow + 1; $r <= $headerRow + 12; $r++) {
                $val = trim($grid[$r][$col] ?? '');
                if ($val === '') {
                    continue;
                }
                if (preg_match('/^\s*(\d+)\s*[Лл]аб[^-]*-\s*(.+)$/u', $val, $m)) {
                    $rules[] = ['labs' => (int) $m[1], 'grade' => trim($m[2])];
                }
            }
            if ($rules) {
                usort($rules, fn ($x, $y) => $x['labs'] <=> $y['labs']);
                $scales[$groupName] = $rules;
            }
        }

        return $scales;
    }
}
