@extends('layouts.app')
@section('title', 'Группы')

@section('crumbs')
    <span class="current">Группы</span>
@endsection

@section('content')
    <h1>👥 Группы</h1>
    <p class="sub">Создавай группы, наполняй студентами, импортируй из Excel.</p>

    <div style="max-width:480px; margin-bottom:24px;">
        {{-- Создать группу --}}
        <div class="card">
            <h3 style="margin-bottom:6px;">➕ Новая группа</h3>
            <p class="muted" style="font-size:.85rem; margin-bottom:4px;">
                Создай группу, потом открой её и добавь студентов.
            </p>
            <form method="post" action="{{ route('groups.store') }}">
                @csrf
                <label>Название группы</label>
                <input type="text" name="name" placeholder="напр. ИВТ41б" value="{{ old('name') }}">
                @error('name') <div class="err">{{ $message }}</div> @enderror

                <label>Учебный год (необязательно)</label>
                <select name="academic_year_id">
                    <option value="">— не указывать —</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}">{{ $y->name }}{{ $y->is_active ? ' (активный)' : '' }}</option>
                    @endforeach
                </select>

                <label>…или новый учебный год</label>
                <input type="text" name="new_year" placeholder="напр. 2025/2026 весна" value="{{ old('new_year') }}">

                <div style="margin-top:16px;">
                    <button class="btn">➕ Создать группу</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Список групп --}}
    <div class="section-h" style="font-family:'Bricolage Grotesque';font-weight:700;font-size:1.05rem;margin:6px 0 16px;">Все группы</div>
    @if($groups->isEmpty())
        <p class="muted">Пока нет ни одной группы. Создай первую выше 👆</p>
    @else
        <div class="grid grid-tiles reveal">
            @foreach($groups as $g)
                <div class="card" style="display:flex;flex-direction:column;">
                    <div style="display:flex; justify-content:space-between; align-items:start; gap:10px;">
                        <div>
                            <a href="{{ route('groups.show', $g) }}" class="display" style="font-weight:700; font-size:1.2rem;">{{ $g->name }}</a>
                            <div class="muted" style="font-size:.82rem; margin-top:3px;">
                                {{ $g->academicYear?->name ?? 'без года' }}
                            </div>
                        </div>
                        <span style="background:var(--line-soft);border:1px solid var(--line);border-radius:20px;padding:4px 11px;font-size:.8rem;font-weight:600;white-space:nowrap;">{{ $g->students_count }} 🧑‍🎓</span>
                    </div>
                    <div style="margin-top:18px; display:flex; gap:8px;">
                        <a href="{{ route('groups.show', $g) }}" class="btn btn-sm">Открыть →</a>
                        <form method="post" action="{{ route('groups.destroy', $g) }}" onsubmit="return confirm('Удалить группу «{{ $g->name }}» со всеми студентами?')">
                            @csrf @method('DELETE')
                            <button class="btn-danger">Удалить</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
