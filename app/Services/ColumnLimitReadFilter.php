<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Ограничивает чтение xlsx колонками до заданного предела.
 *
 * В реальных файлах посещаемости встречаются служебные формульные
 * столбцы у самого края листа (напр. колонка ~12000–16384). Без фильтра
 * PhpSpreadsheet пытается прочитать их все и упирается в memory_limit
 * либо падает с "Invalid column index". Колонки журнала укладываются
 * в первую ~сотню, поэтому 1024 — безопасный предел с большим запасом.
 */
class ColumnLimitReadFilter implements IReadFilter
{
    public function __construct(
        private int $maxColumn = 1024,
        private int $maxRow = 2000,
    ) {
    }

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        if ($row > $this->maxRow) {
            return false;
        }
        return Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumn;
    }
}
