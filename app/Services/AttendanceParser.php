<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
     * Первая колонка с данными занятий (AA = 27). Левее — служебные
     * колонки (ФИО, подгруппа, статусные T..Z), их не сканируем на лабы.
     */
    private const FIRST_LESSON_COL = 27;

    /**
     * @return array{groups: array<string, array>, totalLessons: int, gradeScales: array<string, array>}
     */
    public function parse(string $filePath, ?string $sheetName = null): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(false); // нужны эмодзи/строки как есть
        // Ограничиваем чтение, чтобы не упереться в память/край листа
        // на файлах со служебными формульными столбцами у границы Excel.
        $reader->setReadFilter(new ColumnLimitReadFilter(1024, 2000));
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
     *
     * Читаем только до последней колонки с реальными данными
     * (getHighestDataColumn), а не до края листа — иначе на файлах со
     * служебными формулами у самой границы Excel (колонка ~16384)
     * итератор падает с "Invalid column index".
     * Формульные ячейки (=SUM…) и #REF! пропускаем — это не данные журнала.
     *
     * @return array<int, array<int, string>>
     */
    private function readGrid(Worksheet $sheet): array
    {
        // Граница по колонкам: последняя колонка с данными, но не больше
        // безопасного предела (служебные формулы могут уезжать к краю листа).
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $maxCol = min($maxCol, 1024);
        $maxRow = min($sheet->getHighestDataRow(), 2000);

        $grid = [];
        for ($r = 1; $r <= $maxRow; $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                if (! $sheet->cellExists([$c, $r])) {
                    continue;
                }
                $cell = $sheet->getCell([$c, $r]);

                // Для формул берём КЭШИРОВАННОЕ значение из файла
                // (без пересчёта — он может падать на #REF/служебных формулах).
                // Так колонка-фамилия =MID(...) читается по результату,
                // а битые служебные формулы дают пусто/#REF и отсеиваются.
                if ($cell->isFormula()) {
                    $val = (string) $cell->getOldCalculatedValue();
                } else {
                    $val = (string) $cell->getValue();
                }

                if ($val !== '' && ! str_contains($val, '#REF')) {
                    $grid[$r][$c] = $val;
                }
            }
        }
        return $grid;
    }

    /**
     * Колонки-занятия для подсчёта ПОСЕЩАЕМОСТИ (знаменатель %).
     * Колонка считается занятием, если в строке 2 есть тип (ЛК/ЛБ)
     * ИЛИ в строке 3 есть дата (Excel-serial или строка с датой).
     * Учитываем только колонки данных (>= AA = 27), чтобы не цеплять служебные.
     * @return int[]
     */
    private function detectLessonColumns(array $grid): array
    {
        $cols = [];
        $typeRow = $grid[2] ?? [];
        $dateRow = $grid[3] ?? [];

        $candidates = array_unique(array_merge(array_keys($typeRow), array_keys($dateRow)));
        foreach ($candidates as $col) {
            if ($col < self::FIRST_LESSON_COL) {
                continue;
            }
            $type = trim($typeRow[$col] ?? '');
            $date = trim($dateRow[$col] ?? '');
            $hasType = preg_match('/^\s*Л[КБ]/u', $type) === 1;
            $hasDate = $date !== '' && (is_numeric($date) || preg_match('/\d{1,2}[.\/]\d{1,2}/', $date));
            if ($hasType || $hasDate) {
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
                // Служебные строки (легенда, "Оценки за экзамен", шапка "ФИО",
                // нижние заметки "1. Романов …") — не группа.
                if ($this->looksLikeServiceRow($a) || ! $this->looksLikeGroupName($a)) {
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

        // Отбрасываем «группы» без студентов (артефакты шапки/заметок).
        return array_filter($groups, fn ($students) => count($students) > 0);
    }

    /**
     * Похоже ли значение колонки A на название группы (а не на шапку/заметку).
     * Отсекает «ФИО», нижние строки «1. Романов …» (начинаются с цифры).
     */
    private function looksLikeGroupName(string $a): bool
    {
        if (mb_strtolower($a) === 'фио') {
            return false;
        }
        // Заметки вида "1. Романов ПИ Бек" начинаются с цифры.
        if (preg_match('/^\s*\d/u', $a)) {
            return false;
        }
        return true;
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
     * Считает номера сданных лаб и число посещений по строке студента.
     *
     * ЛАБЫ ищем во ВСЕХ ячейках данных строки (>= FIRST_LESSON_COL), а не
     * только в колонках-занятиях: в реальном файле часть лаб (N✅) стоит в
     * столбцах, у которых не проставлен тип/дата в шапке — иначе они теряются.
     *
     * ПОСЕЩЕНИЯ считаем только по колонкам-занятиям ($lessonCols) — это
     * корректный знаменатель для % посещаемости.
     *
     * @return array{0:int[],1:int}
     */
    private function countLabsAndVisits(array $cells, array $lessonCols): array
    {
        // Лабы — по всей строке данных.
        $labNumbers = [];
        foreach ($cells as $col => $val) {
            if ($col < self::FIRST_LESSON_COL) {
                continue;
            }
            if (preg_match_all('/(\d+)\x{2705}/u', (string) $val, $m)) {
                foreach ($m[1] as $num) {
                    $labNumbers[(int) $num] = true;
                }
            }
        }

        // Посещения — только по колонкам-занятиям.
        $visits = 0;
        foreach ($lessonCols as $col) {
            $val = trim($cells[$col] ?? '');
            if ($val !== '') {
                $visits++;
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

        // 2. Имена групп над колонками шкал. Имя группы валидно, только если
        //    оно НЕ является само строкой шкалы ("N лаб - Оценка").
        //    Если подписей групп нет — шкала общая для всех групп.
        $isRule = fn (string $v) => preg_match('/^\s*\d+\s*[Лл]аб[^-]*-/u', $v) === 1;

        $groupCols = [];
        foreach (($grid[$headerRow] ?? []) as $col => $val) {
            $val = trim($val);
            if ($val === '' || str_contains(mb_strtolower($val), 'оценки за экзамен') || str_contains($val, '#REF')) {
                continue;
            }
            if ($isRule($val)) {
                continue; // это уже строка шкалы, а не имя группы
            }
            $groupCols[$col] = $val; // col => имя группы
        }

        // Хелпер: собрать правила шкалы из колонки $col ниже заголовка.
        $readRules = function (int $col) use ($grid, $headerRow): array {
            $rules = [];
            for ($r = $headerRow; $r <= $headerRow + 12; $r++) {
                $val = trim($grid[$r][$col] ?? '');
                if (preg_match('/^\s*(\d+)\s*[Лл]аб[^-]*-\s*(.+)$/u', $val, $m)) {
                    $rules[] = ['labs' => (int) $m[1], 'grade' => trim($m[2])];
                }
            }
            usort($rules, fn ($x, $y) => $x['labs'] <=> $y['labs']);
            return $rules;
        };

        $scales = [];

        if ($groupCols) {
            // 3а. Шкалы по группам.
            foreach ($groupCols as $col => $groupName) {
                $rules = $readRules($col);
                if ($rules) {
                    $scales[$groupName] = $rules;
                }
            }
        } else {
            // 3б. Подписей групп нет → одна общая шкала.
            // Берём первую колонку (>= FIRST_LESSON_COL не требуется — блок слева),
            // где под заголовком есть строки "N лаб - Оценка".
            $candidateCols = [];
            for ($r = $headerRow; $r <= $headerRow + 12; $r++) {
                foreach (($grid[$r] ?? []) as $col => $val) {
                    if ($isRule(trim($val))) {
                        $candidateCols[$col] = true;
                    }
                }
            }
            foreach (array_keys($candidateCols) as $col) {
                $rules = $readRules($col);
                if ($rules) {
                    $scales['*'] = $rules; // '*' = общая шкала для всех групп
                    break;
                }
            }
        }

        return $scales;
    }
}
