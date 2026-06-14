@extends('layouts.app')
@section('title', 'Дашборд')

@section('crumbs')
    <span class="current">Дашборд</span>
@endsection

@push('head')
<style>
    .hero { margin-bottom: 28px; }
    .hero h1 { font-size: 2.1rem; }
    .hero .sub { font-size: 1.05rem; }

    .stat-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 34px; }
    .stat {
        background: var(--panel); border: 1px solid var(--line); border-radius: 16px;
        padding: 18px 20px; display: flex; align-items: center; gap: 15px;
        box-shadow: 0 1px 2px rgba(20,17,12,.03);
    }
    .stat .ic { width: 50px; height: 50px; border-radius: 14px; display: grid; place-items: center; font-size: 1.5rem; }
    .stat .ic.blue { background: var(--accent-soft); }
    .stat .ic.green { background: #e8f4ed; }
    .stat b { font-family: 'Bricolage Grotesque'; font-size: 1.9rem; display: block; line-height: 1; font-weight: 800; }
    .stat span { color: var(--muted); font-size: .85rem; }
    .hero .sub { font-size: 1.05rem; }

    .section-h {
        font-family: 'Bricolage Grotesque'; font-size: 1.1rem; font-weight: 700;
        margin: 0 0 16px; display: flex; align-items: center; gap: 10px;
    }
    .section-h::after { content: ''; flex: 1; height: 1px; background: var(--line); }

    /* Плитки-разделы: иконка, цветной угловой акцент, описание, кнопка */
    .tile {
        background: var(--panel); border: 1px solid var(--line); border-radius: 18px;
        padding: 24px; position: relative; overflow: hidden;
        transition: transform .18s, box-shadow .18s, border-color .18s;
        display: flex; flex-direction: column; min-height: 184px;
    }
    .tile:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(0,0,0,.09); border-color: #d4d4d8; }
    .tile .corner { position: absolute; inset: -40px -40px auto auto; width: 150px; height: 110px; border-radius: 0 16px 0 60%; opacity: .9; }
    .tile.c-violet .corner { background: linear-gradient(135deg, #ede9fe, #ddd6fe 60%, transparent); }
    .tile.c-blue   .corner { background: linear-gradient(135deg, #dbeafe, #bfdbfe 60%, transparent); }
    .tile.c-amber  .corner { background: linear-gradient(135deg, #ffedd5, #fed7aa 60%, transparent); }
    .tile .ti {
        width: 50px; height: 50px; border-radius: 14px; display: grid; place-items: center;
        font-size: 1.55rem; background: var(--panel); border: 1px solid var(--line);
        position: relative; z-index: 1; margin-bottom: 16px;
    }
    .tile h3 { font-size: 1.25rem; margin-bottom: 7px; position: relative; z-index: 1; }
    .tile p { color: var(--muted); font-size: .92rem; line-height: 1.55; position: relative; z-index: 1; flex: 1; }
    .tile .go { margin-top: 18px; position: relative; z-index: 1; }
    .tile.soon { opacity: .9; }
    .tile.soon .badge {
        position: absolute; top: 18px; right: 18px; z-index: 2; background: var(--line-soft);
        color: var(--muted); font-size: .72rem; font-weight: 700; padding: 5px 11px;
        border-radius: 20px; border: 1px solid var(--line); letter-spacing: .03em;
    }
</style>
@endpush

@section('content')
    <div class="hero">
        <h1>Добрый день 👋</h1>
        <p class="sub">Система ведения журнала и определения автоматов. Выберите раздел, чтобы начать.</p>
    </div>

    <div class="stat-row reveal">
        <div class="stat">
            <div class="ic blue">👥</div>
            <div><b>{{ $stats['groups'] }}</b><span>групп создано</span></div>
        </div>
        <div class="stat">
            <div class="ic green">🧑‍🎓</div>
            <div><b>{{ $stats['students'] }}</b><span>студентов в базе</span></div>
        </div>
        <div class="stat">
            <div class="ic blue">📋</div>
            <div><b>{{ $stats['subjects'] }}</b><span>предметов</span></div>
        </div>
    </div>

    <div class="section-h">Разделы</div>
    <div class="grid grid-tiles reveal">
        {{-- Кто на автомат --}}
        <a href="{{ route('attendance.index') }}" class="tile c-violet">
            <div class="corner"></div>
            <div class="ti">🏆</div>
            <h3>Кто на автомат</h3>
            <p>Загрузите Excel с посещаемостью — система покажет, у кого автомат, кто допущен и кому сколько осталось.</p>
            <div class="go"><span class="btn btn-sm">Открыть →</span></div>
        </a>

        {{-- Группы --}}
        <a href="{{ route('groups.index') }}" class="tile c-blue">
            <div class="corner"></div>
            <div class="ti">👥</div>
            <h3>Группы</h3>
            <p>Создавайте группы и наполняйте их студентами — вручную, списком или импортом из Excel-шаблона.</p>
            <div class="go"><span class="btn btn-sm">Открыть →</span></div>
        </a>

        {{-- Журнал --}}
        <a href="{{ route('subjects.index') }}" class="tile c-amber">
            <div class="corner"></div>
            <div class="ti">📋</div>
            <h3>Журнал</h3>
            <p>Ведите занятия, отмечайте посещаемость и сдачу лаб прямо в системе. С выгрузкой в Excel.</p>
            <div class="go"><span class="btn btn-sm">Открыть →</span></div>
        </a>
    </div>
@endsection
