<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Шаблон списка студентов для импорта в группу.
 * Формат простой: одна колонка «ФИО», по строке на студента.
 */
class StudentRosterTemplate
{
    private const HEADER = 'ФИО';

    /** Отдаёт пустой шаблон на скачивание. */
    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Студенты');

        $sheet->setCellValue('A1', self::HEADER);
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle('A1')->getFont()->getColor()->setRGB('FFFFFF');

        // Примеры-подсказки (серым), пользователь их заменит/удалит.
        $sheet->setCellValue('A2', 'Иванов Иван Иванович');
        $sheet->setCellValue('A3', 'Петров Пётр Петрович');
        $sheet->getStyle('A2:A3')->getFont()->getColor()->setRGB('9CA3AF');

        $sheet->getColumnDimension('A')->setWidth(40);

        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="shablon_studentov.xlsx"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Читает ФИО из загруженного шаблона.
     * Берёт колонку A, пропускает строку-заголовок «ФИО» и пустые строки.
     * @return string[] список ФИО
     */
    public function parseNames(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($filePath)->getSheet(0);

        $names = [];
        foreach ($sheet->getRowIterator() as $row) {
            $value = trim((string) $sheet->getCell('A' . $row->getRowIndex())->getValue());
            if ($value === '' || mb_strtolower($value) === mb_strtolower(self::HEADER)) {
                continue;
            }
            $names[] = $value;
        }

        return $names;
    }
}
