@extends('layouts.app')
@section('title', $group->name)

@section('crumbs')
    <a href="{{ route('groups.index') }}">Группы</a>
    <span class="sep">›</span>
    <span class="current">{{ $group->name }}</span>
@endsection

@section('content')
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div>
            <h1>👥 {{ $group->name }}</h1>
            <p class="sub" style="margin-bottom:0;">
                {{ $group->academicYear?->name ?? 'без учебного года' }} ·
                {{ $group->students->count() }} студентов
            </p>
        </div>
        <a href="{{ route('groups.index') }}" class="btn btn-ghost btn-sm">← К списку групп</a>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; margin:22px 0;">
        {{-- По одному --}}
        <div class="card">
            <h3 style="margin-bottom:10px;">➕ Добавить студента</h3>
            <form method="post" action="{{ route('students.store', $group) }}">
                @csrf
                <label>ФИО</label>
                <input type="text" name="full_name" placeholder="Иванов Иван Иванович" value="{{ old('full_name') }}">
                @error('full_name') <div class="err">{{ $message }}</div> @enderror
                <label>Подгруппа</label>
                <input type="number" name="subgroup" min="1" max="9" value="{{ old('subgroup', 1) }}">
                <div style="margin-top:14px;"><button class="btn btn-green">Добавить</button></div>
            </form>
        </div>

        {{-- Пакетно --}}
        <div class="card">
            <h3 style="margin-bottom:10px;">📋 Вставить списком</h3>
            <form method="post" action="{{ route('students.storeBulk', $group) }}">
                @csrf
                <label>ФИО — по одному в строке</label>
                <textarea name="names" rows="5" placeholder="Иванов Иван Иванович&#10;Петров Пётр Петрович&#10;…">{{ old('names') }}</textarea>
                @error('names') <div class="err">{{ $message }}</div> @enderror
                <label>Подгруппа для всех</label>
                <input type="number" name="subgroup" min="1" max="9" value="{{ old('subgroup', 1) }}">
                <div style="margin-top:14px;"><button class="btn">Добавить всех</button></div>
            </form>
        </div>
    </div>

    {{-- Импорт из Excel-шаблона --}}
    <div class="card" style="margin-bottom:22px;">
        <h3 style="margin-bottom:6px;">📥 Импорт из Excel</h3>
        <p class="muted" style="font-size:.85rem; margin-bottom:10px;">
            Скачай шаблон, заполни колонку «ФИО» (по одному студенту в строке) и загрузи обратно.
        </p>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <a href="{{ route('students.template') }}" class="btn btn-gold btn-sm">⬇️ Скачать шаблон</a>
            <form method="post" action="{{ route('students.import', $group) }}" enctype="multipart/form-data"
                  style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls" style="width:auto;">
                <button class="btn btn-sm">Загрузить</button>
            </form>
        </div>
        @error('file') <div class="err">{{ $message }}</div> @enderror
    </div>

    {{-- Список студентов --}}
    <div class="card">
        <h3 style="margin-bottom:12px;">Студенты</h3>
        @if($group->students->isEmpty())
            <p class="muted">В группе пока нет студентов.</p>
        @else
            <table>
                <thead>
                    <tr><th>#</th><th>ФИО</th><th>Подгруппа</th><th style="width:1%;"></th></tr>
                </thead>
                <tbody>
                    @foreach($group->students as $i => $s)
                        <tr>
                            <td class="muted">{{ $i + 1 }}</td>
                            <td>{{ $s->full_name }}</td>
                            <td>{{ $s->subgroup }}</td>
                            <td>
                                <form method="post" action="{{ route('students.destroy', [$group, $s]) }}" onsubmit="return confirm('Удалить студента?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger">✕</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
