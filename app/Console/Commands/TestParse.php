<?php

namespace App\Console\Commands;

use App\Services\AttendanceCalculator;
use App\Services\AttendanceParser;
use Illuminate\Console\Command;

/**
 * Отладочная команда: парсит файл и печатает результат в консоль.
 * Использование: php artisan attendance:test "путь/к/файлу.xlsx"
 */
class TestParse extends Command
{
    protected $signature = 'attendance:test {file}';
    protected $description = 'Тестовый парсинг xlsx посещаемости';

    public function handle(AttendanceParser $parser): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("Файл не найден: $file");
            return self::FAILURE;
        }

        $parsed = $parser->parse($file, config('attendance.sheet_name'));

        $this->info("Всего занятий: {$parsed['totalLessons']}");
        $this->info('Шкалы оценок:');
        foreach ($parsed['gradeScales'] as $g => $rules) {
            $line = collect($rules)->map(fn ($r) => "{$r['labs']}={$r['grade']}")->implode(', ');
            $this->line("  $g: $line");
        }

        $calc = new AttendanceCalculator((int) config('attendance.required_labs'));
        $results = $calc->calculate($parsed);

        $byGroup = collect($results)->groupBy('group');
        foreach ($byGroup as $group => $students) {
            $this->newLine();
            $this->info("=== $group ({$students->count()}) ===");
            foreach ($students as $s) {
                $tag = match ($s->category()) {
                    'auto'  => "АВТОМАТ: {$s->autoGrade}",
                    'exam'  => 'порог пройден (экзамен)',
                    default => "до порога: {$s->labsToThreshold}",
                };
                $this->line(sprintf(
                    '  %-42s лаб=%2d посещ=%2d(%3d%%) | %s',
                    $s->name, $s->labsDone, $s->visits, $s->visitPercent, $tag
                ));
            }
        }

        return self::SUCCESS;
    }
}
