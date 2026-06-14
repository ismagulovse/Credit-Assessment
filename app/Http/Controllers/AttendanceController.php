<?php

namespace App\Http\Controllers;

use App\Services\AttendanceCalculator;
use App\Services\AttendanceParser;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AttendanceController extends Controller
{
    public function __construct(
        private AttendanceParser $parser,
    ) {
    }

    /** Страница загрузки файла. */
    public function index()
    {
        return view('attendance.index');
    }

    /** Обработка загруженного файла → страница с результатами. */
    public function calculate(Request $request)
    {
        $maxKb = (int) config('attendance.upload_max_size_mb') * 1024;

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', "max:$maxKb"],
        ], [
            'file.required' => 'Выберите файл.',
            'file.mimes'    => 'Нужен файл Excel (.xlsx).',
            'file.max'      => "Файл слишком большой (макс. {$maxKb} КБ).",
        ]);

        $results = $this->run($request->file('file')->getRealPath());

        // Сохраняем результат в сессию — чтобы можно было выгрузить в Excel.
        session(['attendance_results' => array_map(fn ($r) => $r->toArray(), $results)]);

        return view('attendance.index', $this->viewData($results));
    }

    /** Выгрузка последнего результата в Excel. */
    public function export()
    {
        $raw = session('attendance_results');
        if (! $raw) {
            return redirect()->route('attendance.index');
        }

        return app(\App\Services\AttendanceExporter::class)->download($raw);
    }

    /**
     * @return \App\DTO\StudentResult[]
     */
    private function run(string $path): array
    {
        $parsed = $this->parser->parse($path, config('attendance.sheet_name'));
        $calc   = new AttendanceCalculator((int) config('attendance.required_labs'));

        return $calc->calculate($parsed);
    }

    /** Готовит данные для шаблона: разбивка по категориям + статистика. */
    private function viewData(array $results): array
    {
        $all = collect($results);

        return [
            'hasResults' => true,
            'requiredLabs' => (int) config('attendance.required_labs'),
            'auto'   => $this->sortForUi($all->filter(fn ($r) => $r->category() === 'auto')),
            'exam'   => $this->sortForUi($all->filter(fn ($r) => $r->category() === 'exam')),
            'below'  => $this->sortForUi($all->filter(fn ($r) => $r->category() === 'below')),
            'stats'  => [
                'total' => $all->count(),
                'auto'  => $all->where('autoGrade', '!=', null)->count(),
                'exam'  => $all->filter(fn ($r) => $r->category() === 'exam')->count(),
                'below' => $all->filter(fn ($r) => $r->category() === 'below')->count(),
            ],
        ];
    }

    /** Сортировка карточек: больше лаб → выше; затем по ФИО. */
    private function sortForUi(Collection $c): Collection
    {
        return $c->sortBy([
            fn ($a, $b) => $b->labsDone <=> $a->labsDone,
            fn ($a, $b) => $a->name <=> $b->name,
        ])->values();
    }
}
