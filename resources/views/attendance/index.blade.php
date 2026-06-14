<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Кто на автомат? 🎓</title>
    <style>
        :root {
            --bg: #0f172a; --card: #1e293b; --line: #334155;
            --accent: #6366f1; --gold: #f59e0b; --green: #10b981; --slate: #64748b;
            --text: #e2e8f0; --muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: radial-gradient(1200px 600px at 50% -10%, #1e1b4b, var(--bg));
            color: var(--text); min-height: 100vh; padding: 32px 16px;
        }
        .wrap { max-width: 1100px; margin: 0 auto; }
        h1 { font-size: 2rem; text-align: center; margin-bottom: 4px; }
        .sub { text-align: center; color: var(--muted); margin-bottom: 28px; }

        .upload {
            background: var(--card); border: 2px dashed var(--line);
            border-radius: 16px; padding: 28px; text-align: center;
            transition: .2s; margin-bottom: 24px;
        }
        .upload.drag { border-color: var(--accent); background: #232b45; }
        .upload input[type=file] { display: none; }
        .file-label {
            display: inline-block; cursor: pointer; padding: 12px 22px;
            background: var(--accent); color: #fff; border-radius: 10px;
            font-weight: 600; transition: .2s;
        }
        .file-label:hover { filter: brightness(1.1); }
        .file-name { color: var(--muted); margin: 12px 0; font-size: .9rem; }
        .btn {
            cursor: pointer; padding: 11px 22px; border: none; border-radius: 10px;
            font-weight: 600; font-size: .95rem; transition: .2s; text-decoration: none;
            display: inline-block;
        }
        .btn-go { background: var(--green); color: #fff; }
        .btn-go:hover { filter: brightness(1.1); }
        .btn-export { background: var(--gold); color: #0f172a; }
        .btn-export:hover { filter: brightness(1.1); }
        .err { color: #fca5a5; margin-top: 10px; }

        .stats { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; margin: 24px 0; }
        .stat { background: var(--card); border: 1px solid var(--line); border-radius: 12px;
            padding: 14px 22px; text-align: center; min-width: 120px; }
        .stat b { font-size: 1.6rem; display: block; }
        .stat span { color: var(--muted); font-size: .85rem; }

        .section-title { font-size: 1.25rem; margin: 30px 0 14px; display: flex; align-items: center; gap: 8px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }

        .card {
            background: var(--card); border: 1px solid var(--line);
            border-radius: 14px; padding: 16px; position: relative; overflow: hidden;
        }
        .card .name { font-weight: 600; margin-bottom: 4px; padding-right: 8px; }
        .card .group { color: var(--muted); font-size: .8rem; margin-bottom: 12px; }
        .card .row { display: flex; justify-content: space-between; font-size: .88rem; margin: 4px 0; }
        .card .row span:first-child { color: var(--muted); }

        .card-auto { border-color: var(--gold); box-shadow: 0 0 0 1px var(--gold) inset; }
        .card-auto::before { content: "🏆"; position: absolute; top: -8px; right: -6px; font-size: 3rem; opacity: .12; }
        .grade { display: inline-block; margin-top: 10px; padding: 5px 12px; border-radius: 8px;
            background: var(--gold); color: #0f172a; font-weight: 700; font-size: .85rem; }

        .card-exam { border-color: var(--green); }
        .badge-exam { display: inline-block; margin-top: 10px; padding: 4px 10px; border-radius: 8px;
            background: rgba(16,185,129,.15); color: var(--green); font-weight: 600; font-size: .8rem; }

        .progress { height: 7px; background: #0f172a; border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .progress > div { height: 100%; background: var(--accent); }
        .remain { color: var(--gold); font-size: .82rem; margin-top: 8px; }

        .empty { color: var(--muted); font-size: .9rem; padding: 8px 0; }
        .crown { font-size: .9em; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>🎓 Кто на автомат?</h1>
    <p class="sub">Загрузи Excel-файл посещаемости — покажу, у кого автомат, кто допущен и кому сколько осталось.</p>

    <form method="post" action="{{ route('attendance.calculate') }}" enctype="multipart/form-data" id="form">
        @csrf
        <div class="upload" id="drop">
            <label class="file-label" for="file">📂 Выбрать .xlsx</label>
            <input type="file" name="file" id="file" accept=".xlsx,.xls">
            <div class="file-name" id="fileName">Файл не выбран</div>
            <button type="submit" class="btn btn-go">🚀 Посчитать</button>
            @error('file') <div class="err">{{ $message }}</div> @enderror
        </div>
    </form>

    @if(!empty($hasResults))
        <div class="stats">
            <div class="stat"><b>{{ $stats['total'] }}</b><span>всего студентов</span></div>
            <div class="stat" style="color:var(--gold)"><b>{{ $stats['auto'] }}</b><span>🏆 автомат</span></div>
            <div class="stat" style="color:var(--green)"><b>{{ $stats['exam'] }}</b><span>✅ допуск</span></div>
            <div class="stat" style="color:var(--muted)"><b>{{ $stats['below'] }}</b><span>📊 не дотянули</span></div>
        </div>

        <div style="text-align:center; margin-bottom:8px;">
            <a href="{{ route('attendance.export') }}" class="btn btn-export">⬇️ Выгрузить в Excel</a>
        </div>

        {{-- 🏆 Автомат --}}
        <div class="section-title">🏆 Получают автомат <span style="color:var(--muted);font-size:.9rem">({{ $auto->count() }})</span></div>
        @if($auto->isEmpty())
            <p class="empty">Пока никто не набрал на автомат 🤷</p>
        @else
            <div class="grid">
                @foreach($auto as $s)
                    <div class="card card-auto">
                        <div class="name">{{ $s->name }}</div>
                        <div class="group">{{ $s->group }}</div>
                        <div class="row"><span>Сдано лаб</span><span><b>{{ $s->labsDone }}</b></span></div>
                        <div class="row"><span>Посещаемость</span><span>{{ $s->visitPercent }}%</span></div>
                        <div class="grade">{{ $s->autoGrade }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ✅ Допуск к экзамену --}}
        <div class="section-title">✅ Прошли порог ({{ $requiredLabs }} лаб) — на экзамен <span style="color:var(--muted);font-size:.9rem">({{ $exam->count() }})</span></div>
        @if($exam->isEmpty())
            <p class="empty">Никого 🙃</p>
        @else
            <div class="grid">
                @foreach($exam as $s)
                    <div class="card card-exam">
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
        <div class="section-title">📊 Ещё в пути <span style="color:var(--muted);font-size:.9rem">({{ $below->count() }})</span></div>
        @if($below->isEmpty())
            <p class="empty">Все молодцы! 🎉</p>
        @else
            <div class="grid">
                @foreach($below as $s)
                    <div class="card">
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
</div>

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
</body>
</html>
