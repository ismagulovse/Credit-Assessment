<?php

namespace App\Services;

use App\Models\Subject;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Выгружает журнал предмета в Excel: сетка студенты × занятия.
 * В ячейках — эмодзи статуса + номер лабы (как в исходном файле).
 */
class JournalExporter
{
    public function download(Subject $subject): StreamedResponse
    {
        $subject->load([
            'lessons',
            'groups.students' => fn ($q) => $q->orderBy('full_name'),
            'lessons.attendances',
        ]);

        // Карта отметок [student_id][lesson_id]
        $marks = [];
        foreach ($subject->lessons as $lesson) {
            foreach ($lesson->attendances as $a) {
                $marks[$a->student_id][$a->lesson_id] = $a;
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Журнал');

        // Шапка: ФИО | Группа | <занятия...>
        $sheet->setCellValue('A1', 'ФИО');
        $sheet->setCellValue('B1', 'Группа');
        $col = 3;
        $lessonCols = [];
        foreach ($subject->lessons as $lesson) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->setCellValue("{$letter}1", $lesson->shortLabel());
            $lessonCols[$lesson->id] = $col;
            $col++;
        }
        $lastCol = Coordinate::stringFromColumnIndex(max(2, $col - 1));

        // Стиль шапки
        $sheet->getStyle("A1:{$lastCol}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$lastCol}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');

        // Строки студентов
        $row = 2;
        foreach ($subject->groups as $group) {
            foreach ($group->students as $student) {
                $sheet->setCellValue("A{$row}", $student->full_name);
                $sheet->setCellValue("B{$row}", $group->name);
                foreach ($lessonCols as $lessonId => $cIdx) {
                    $a = $marks[$student->id][$lessonId] ?? null;
                    if ($a) {
                        $val = $a->status->emoji() . ($a->lab_number ? (string) $a->lab_number : '');
                        $sheet->setCellValueExplicit(
                            Coordinate::stringFromColumnIndex($cIdx) . $row,
                            $val,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                    }
                }
                $row++;
            }
        }

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(14);
        for ($c = 3; $c < $col; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(10);
        }
        $sheet->getStyle("C1:{$lastCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->freezePane('C2');

        $fileName = 'journal_' . preg_replace('/[^\w\-]+/u', '_', $subject->name) . '_' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control'       => 'max-age=0',
        ]);
    }
}
