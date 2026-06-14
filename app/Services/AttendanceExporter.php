<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Выгружает результаты расчёта в xlsx-файл.
 */
class AttendanceExporter
{
    /**
     * @param array<int, array> $rows результат StudentResult::toArray()
     */
    public function download(array $rows): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Результаты');

        $headers = ['ФИО', 'Группа', 'Сдано лаб', 'Номера лаб', 'Посещений', '% посещаемости', 'Статус', 'Автомат-оценка'];
        $sheet->fromArray($headers, null, 'A1');

        // Шапка жирная, с заливкой.
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setRGB('FFFFFF');

        $r = 2;
        foreach ($rows as $row) {
            $status = match ($row['category']) {
                'auto'  => 'Автомат',
                'exam'  => 'Допуск к экзамену',
                default => 'Не допущен (осталось ' . $row['labs_to_threshold'] . ')',
            };

            $sheet->fromArray([
                $row['name'],
                $row['group'],
                $row['labs_done'],
                implode(', ', $row['lab_numbers']),
                $row['visits'],
                $row['visit_percent'],
                $status,
                $row['auto_grade'] ?? '—',
            ], null, "A$r");
            $r++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle("C2:F$r")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $fileName = 'avtomat_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
