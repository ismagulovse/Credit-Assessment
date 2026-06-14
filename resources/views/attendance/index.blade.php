@extends('layouts.app')
@section('title', 'Кто на автомат')

@section('crumbs')
    <span class="current">Кто на автомат</span>
@endsection

@push('head')
<style>
    .upload {
        background: var(--panel); border: 2px dashed var(--line);
        border-radius: 16px; padding: 30px; text-align: center; transition: .2s; margin-bottom: 24px;
    }
    .upload.drag { border-color: var(--accent); background: var(--accent-soft); }
    .upload input[type=file] { display: none; }
    .file-label {
        display: inline-flex; align-items: center; gap: 7px; cursor: pointer;
        padding: 11px 20px; background: var(--accent); color: #fff; border-radius: 10px; font-weight: 600;
    }
    .file-label:hover { filter: brightness(1.05); }
    .file-name { color: var(--muted); margin: 12px 0; font-size: .9rem; }

    .stats { display: flex; gap: 12px; flex-wrap: wrap; margin: 22px 0; }
    .stat-pill { background: var(--panel); border: 1px solid var(--line); border-radius: 13px; padding: 14px 22px; min-width: 130px; }
    .stat-pill b { font-size: 1.5rem; display: block; }
    .stat-pill span { color: var(--muted); font-size: .82rem; }

    .section-title { font-size: 1.15rem; font-weight: 700; margin: 28px 0 14px; }
    .section-title .cnt { color: var(--muted); font-size: .9rem; font-weight: 500; }

    .scard { position: relative; overflow: hidden; }
    .scard .name { font-weight: 600; margin-bottom: 2px; }
    .scard .group { color: var(--muted); font-size: .8rem; margin-bottom: 12px; }
    .scard .row { display: flex; justify-content: space-between; font-size: .88rem; margin: 5px 0; }
    .scard .row span:first-child { color: var(--muted); }

    .scard.auto { border-color: #fde68a; background: linear-gradient(160deg, #fffbeb, var(--panel) 55%); }
    .scard.auto::before { content: "🏆"; position: absolute; top: -6px; right: -2px; font-size: 2.6rem; opacity: .18; }
    .grade { display: inline-block; margin-top: 12px; padding: 6px 13px; border-radius: 9px; background: var(--gold); color: #fff; font-weight: 700; font-size: .85rem; }

    .scard.exam { border-color: #bbf7d0; }
    .badge-exam { display: inline-block; margin-top: 12px; padding: 5px 11px; border-radius: 9px; background: #f0fdf4; color: var(--green); font-weight: 600; font-size: .8rem; }

    .progress { height: 8px; background: #f1f5f9; border-radius: 5px; margin-top: 12px; overflow: hidden; }
    .progress > div { height: 100%; background: var(--accent); border-radius: 5px; }
    .remain { color: var(--gold); font-size: .82rem; margin-top: 8px; font-weight: 500; }
    .empty { color: var(--muted); }
</style>
@endpush

@section('content')
    <h1>🏆 Кто на автомат?</h1>
    <p class="sub">Загрузи Excel-файл посещаемости — покажу, у кого автомат, кто допущен и кому сколько осталось.</p>

    {{-- Источник 1: из журнала --}}
    @if(!empty($subjects) && $subjects->isNotEmpty())
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-bottom:10px;">📋 Посчитать из журнала</h3>
            <form method="get" action="{{ route('attendance.index') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <label style="margin-top:0;">Предмет</label>
                    <select name="subject">
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(($subjectId ?? null) == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn">🏆 Посчитать</button>
            </form>
        </div>

        <div style="text-align:center; color:var(--muted-light); margin:14px 0; font-size:.85rem;">— или загрузите файл —</div>
    @endif

    {{-- Источник 2: загрузка Excel --}}
    <form method="post" action="{{ route('attendance.calculate') }}" enctype="multipart/form-data" id="form">
        @csrf
        <div class="upload" id="drop">
            <label class="file-label" for="file">📂 Выбрать .xlsx</label>
            <input type="file" name="file" id="file" accept=".xlsx,.xls">
            <div class="file-name" id="fileName">Файл не выбран</div>
            <button type="submit" class="btn btn-green">🚀 Посчитать</button>
            @error('file') <div class="err">{{ $message }}</div> @enderror
        </div>
    </form>

    @if(!empty($hasResults))
        <div class="stats">
            <div class="stat-pill"><b>{{ $stats['total'] }}</b><span>всего студентов</span></div>
            <div class="stat-pill" style="color:var(--gold)"><b>{{ $stats['auto'] }}</b><span>🏆 автомат</span></div>
            <div class="stat-pill" style="color:var(--green)"><b>{{ $stats['exam'] }}</b><span>✅ допуск</span></div>
            <div class="stat-pill" style="color:var(--muted)"><b>{{ $stats['below'] }}</b><span>📊 не дотянули</span></div>
        </div>

        <a href="{{ route('attendance.export') }}" class="btn btn-gold">⬇️ Выгрузить в Excel</a>

        {{-- 🏆 Автомат --}}
        <div class="section-title">🏆 Получают автомат <span class="cnt">({{ $auto->count() }})</span></div>
        @if($auto->isEmpty())
            <p class="empty">Пока никто не набрал на автомат 🤷</p>
        @else
            <div class="grid grid-tiles reveal">
                @foreach($auto as $s)
                    <div class="card scard auto">
                        <div class="name">{{ $s->name }}</div>
                        <div class="group">{{ $s->group }}</div>
                        <div class="row"><span>Сдано лаб</span><span><b>{{ $s->labsDone }}</b></span></div>
                        <div class="row"><span>Посещаемость</span><span>{{ $s->visitPercent }}%</span></div>
                        <div class="grade">{{ $s->autoGrade }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ✅ Допуск --}}
        <div class="section-title">✅ Прошли порог ({{ $requiredLabs }} лаб) — на экзамен <span class="cnt">({{ $exam->count() }})</span></div>
        @if($exam->isEmpty())
            <p class="empty">Никого 🙃</p>
        @else
            <div class="grid grid-tiles reveal">
                @foreach($exam as $s)
                    <div class="card scard exam">
                        <div class="name">{{ $s->name }}</div>
                        <div class="group">{{ $s->group }}</div>
                        <div class="row"><span>Сдано лаб</span><span><b>{{ $s->labsDone }}</b></span></div>
                        <div class="row"><span>Посещаемость</span><span>{{ $s->visitPercent }}%</span></div>
                        <div class="badge-exam">Допущен к экзамену</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 📊 Не дотянули --}}
        <div class="section-title">📊 Ещё в пути <span class="cnt">({{ $below->count() }})</span></div>
        @if($below->isEmpty())
            <p class="empty">Все молодцы! 🎉</p>
        @else
            <div class="grid grid-tiles reveal">
                @foreach($below as $s)
                    <div class="card scard">
                        <div class="name">{{ $s->name }}</div>
                        <div class="group">{{ $s->group }}</div>
                        <div class="row"><span>Сдано лаб</span><span><b>{{ $s->labsDone }}</b> / {{ $requiredLabs }}</span></div>
                        <div class="row"><span>Посещаемость</span><span>{{ $s->visitPercent }}%</span></div>
                        <div class="progress"><div style="width: {{ $requiredLabs ? min(100, round($s->labsDone / $requiredLabs * 100)) : 0 }}%"></div></div>
                        <div class="remain">Осталось сдать: {{ $s->labsToThreshold }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
@endsection

@push('scripts')
<script>
    const fileInput = document.getElementById('file');
    const fileName = document.getElementById('fileName');
    const drop = document.getElementById('drop');

    fileInput.addEventListener('change', () => {
        fileName.textContent = fileInput.files.length ? fileInput.files[0].name : 'Файл не выбран';
    });
    ['dragenter', 'dragover'].forEach(e =>
        drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.add('drag'); }));
    ['dragleave', 'drop'].forEach(e =>
        drop.addEventListener(e, ev => { ev.preventDefault(); drop.classList.remove('drag'); }));
    drop.addEventListener('drop', ev => {
        if (ev.dataTransfer.files.length) {
            fileInput.files = ev.dataTransfer.files;
            fileName.textContent = ev.dataTransfer.files[0].name;
        }
    });
</script>
@endpush
