@extends('layouts.app')
@section('title', 'Журнал')

@section('crumbs')
    <span class="current">Журнал</span>
@endsection

@section('content')
    <h1>📋 Журнал</h1>
    <p class="sub">Создавайте предметы, добавляйте группы и ведите посещаемость с отметками о сдаче лаб.</p>

    <div style="max-width:520px; margin-bottom:26px;">
        <div class="card">
            <h3 style="margin-bottom:6px;">➕ Новый предмет</h3>
            <p class="muted" style="font-size:.85rem; margin-bottom:4px;">
                Укажите название и выберите группы, которые ходят на занятия.
            </p>
            <form method="post" action="{{ route('subjects.store') }}">
                @csrf
                <label>Название предмета</label>
                <input type="text" name="name" placeholder="напр. Веб-разработка" value="{{ old('name') }}">
                @error('name') <div class="err">{{ $message }}</div> @enderror

                <label>Обязательных лаб (необязательно)</label>
                <input type="number" name="required_labs" min="1" max="50" placeholder="по умолчанию {{ config('attendance.required_labs') }}" value="{{ old('required_labs') }}">

                <label>Группы</label>
                @if($groups->isEmpty())
                    <p class="muted" style="font-size:.85rem;">Сначала создайте группы в разделе «Группы».</p>
                @else
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:4px;">
                        @foreach($groups as $g)
                            <label style="display:inline-flex; align-items:center; gap:7px; background:var(--line-soft); border:1px solid var(--line); border-radius:9px; padding:7px 12px; margin:0; cursor:pointer; font-weight:500; color:var(--ink);">
                                <input type="checkbox" name="group_ids[]" value="{{ $g->id }}" style="width:auto;"> {{ $g->name }}
                            </label>
                        @endforeach
                    </div>
                @endif

                <div style="margin-top:18px;">
                    <button class="btn">➕ Создать предмет</button>
                </div>
            </form>
        </div>
    </div>

    <div class="section-h" style="font-family:'Bricolage Grotesque';font-weight:700;font-size:1.05rem;margin:6px 0 16px;">Все предметы</div>
    @if($subjects->isEmpty())
        <p class="muted">Пока нет предметов. Создайте первый выше 👆</p>
    @else
        <div class="grid grid-tiles reveal">
            @foreach($subjects as $s)
                <div class="card" style="display:flex; flex-direction:column;">
                    <a href="{{ route('subjects.show', $s) }}" class="display" style="font-weight:700; font-size:1.2rem;">{{ $s->name }}</a>
                    <div class="muted" style="font-size:.85rem; margin-top:6px;">
                        {{ $s->groups_count }} групп · {{ $s->lessons_count }} занятий
                    </div>
                    <div style="margin-top:18px; display:flex; gap:8px;">
                        <a href="{{ route('subjects.show', $s) }}" class="btn btn-sm">Открыть журнал →</a>
                        <form method="post" action="{{ route('subjects.destroy', $s) }}" onsubmit="return confirm('Удалить предмет «{{ $s->name }}» со всеми занятиями и отметками?')">
                            @csrf @method('DELETE')
                            <button class="btn-danger">Удалить</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
