@extends('layouts.app')
@section('title', $subject->name)

@section('crumbs')
    <a href="{{ route('subjects.index') }}">Журнал</a>
    <span class="sep">›</span>
    <span class="current">{{ $subject->name }}</span>
@endsection

@push('head')
<style>
    .toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
    .toolbar .spacer { flex:1; }

    .chips { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:18px; }
    .chip {
        display:inline-flex; align-items:center; gap:8px; background:var(--panel);
        border:1px solid var(--line); border-radius:20px; padding:6px 8px 6px 13px; font-size:.86rem; font-weight:600;
    }
    .chip form { display:inline; }
    .chip .x { border:none; background:var(--line-soft); color:var(--muted); width:20px; height:20px; border-radius:50%; cursor:pointer; font-size:.8rem; line-height:1; }
    .chip .x:hover { background:#fbeaea; color:var(--red); }

    /* Легенда */
    .legend { display:flex; flex-wrap:wrap; gap:14px; font-size:.82rem; color:var(--muted); margin-bottom:18px; }
    .legend span b { font-style:normal; }

    /* Сетка */
    .grid-wrap { overflow:auto; border:1px solid var(--line); border-radius:14px; background:var(--panel); max-height:72vh; }
    table.journal { border-collapse:separate; border-spacing:0; width:max-content; min-width:100%; }
    table.journal th, table.journal td { border-bottom:1px solid var(--line); border-right:1px solid var(--line); padding:0; }
    table.journal thead th {
        position:sticky; top:0; z-index:3; background:var(--line-soft); font-size:.74rem;
        text-transform:none; letter-spacing:0; padding:8px 10px; white-space:nowrap; text-align:center; min-width:74px;
    }
    table.journal thead th .del-lesson { display:block; font-size:.7rem; color:var(--muted-light); cursor:pointer; margin-top:2px; }
    table.journal .name-col {
        position:sticky; left:0; z-index:2; background:var(--panel); text-align:left;
        padding:9px 14px; min-width:240px; font-size:.88rem; white-space:nowrap;
    }
    table.journal thead th.name-col { z-index:4; }
    table.journal .grp-row td { background:var(--line-soft); font-weight:700; font-family:'Bricolage Grotesque'; padding:8px 14px; position:sticky; left:0; }
    table.journal tbody tr:hover .name-col { background:var(--line-soft); }
    .cell {
        width:74px; height:42px; display:flex; align-items:center; justify-content:center;
        cursor:pointer; font-size:1.05rem; user-select:none; transition:background .12s;
    }
    .cell:hover { background:var(--accent-soft); }
    .cell.has { font-weight:600; }

    /* Поповер */
    .pop-backdrop { position:fixed; inset:0; z-index:50; display:none; }
    .pop-backdrop.open { display:block; }
    .popover {
        position:absolute; z-index:51; background:var(--panel); border:1px solid var(--line);
        border-radius:14px; box-shadow:0 16px 40px rgba(0,0,0,.18); padding:12px; width:230px;
    }
    .pop-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:6px; }
    .pop-grid button {
        border:1px solid var(--line); background:var(--panel); border-radius:9px; height:40px;
        font-size:1.15rem; cursor:pointer; transition:.12s; padding:0;
    }
    .pop-grid button:hover { border-color:var(--accent); background:var(--accent-soft); transform:translateY(-1px); }
    .pop-lab { margin-top:10px; display:none; }
    .pop-lab.show { display:block; }
    .pop-lab input { width:100%; }
    .pop-actions { margin-top:10px; display:flex; justify-content:space-between; align-items:center; }
    .pop-actions .clear { border:none; background:none; color:var(--red); cursor:pointer; font-size:.82rem; }
    .pop-hint { font-size:.74rem; color:var(--muted); margin:8px 0 2px; }
</style>
@endpush

@section('content')
    <div class="toolbar">
        <h1 style="margin:0;">📋 {{ $subject->name }}</h1>
        <div class="spacer"></div>
        <a href="{{ route('attendance.index', ['subject' => $subject->id]) }}" class="btn btn-sm">🏆 Посчитать автомат</a>
        <a href="{{ route('subjects.index') }}" class="btn btn-sm btn-ghost">← К предметам</a>
    </div>

    {{-- Группы предмета --}}
    <div class="chips">
        @forelse($subject->groups as $g)
            <span class="chip">
                {{ $g->name }} <span class="muted" style="font-weight:500;">· {{ $g->students->count() }}</span>
                <form method="post" action="{{ route('subjects.detachGroup', [$subject, $g]) }}" onsubmit="return confirm('Открепить группу {{ $g->name }}?')">
                    @csrf @method('DELETE')
                    <button class="x" title="Открепить">✕</button>
                </form>
            </span>
        @empty
            <span class="muted">Нет прикреплённых групп.</span>
        @endforelse

        {{-- Добавить группу --}}
        @php $available = $allGroups->whereNotIn('id', $subject->groups->pluck('id')); @endphp
        @if($available->isNotEmpty())
            <form method="post" action="{{ route('subjects.attachGroup', $subject) }}" style="display:inline-flex; gap:6px;">
                @csrf
                <select name="group_id" style="width:auto;">
                    @foreach($available as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                </select>
                <button class="btn btn-sm btn-ghost">+ группа</button>
            </form>
        @endif
    </div>

    {{-- Добавить занятие --}}
    <div class="card" style="margin-bottom:18px;">
        <form method="post" action="{{ route('lessons.store', $subject) }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            @csrf
            <div><label style="margin-top:0;">Дата</label><input type="date" name="date" required style="width:auto;"></div>
            <div><label style="margin-top:0;">Время</label><input type="text" name="time" placeholder="18:15" style="width:90px;"></div>
            <div><label style="margin-top:0;">Тип</label>
                <select name="type" style="width:auto;"><option value="lab">Лаба</option><option value="lecture">Лекция</option></select>
            </div>
            <div><label style="margin-top:0;">№</label><input type="number" name="number" min="1" style="width:70px;"></div>
            <button class="btn btn-sm">➕ Занятие</button>
        </form>
    </div>

    {{-- Легенда статусов --}}
    <div class="legend">
        @foreach($statuses as $val => $st)
            <span><b>{{ $st['emoji'] }}</b> — {{ $st['label'] }}</span>
        @endforeach
    </div>

    @if($subject->lessons->isEmpty() || $subject->groups->isEmpty())
        <div class="card"><p class="muted">Добавьте хотя бы одну группу и одно занятие, чтобы вести журнал.</p></div>
    @else
        <div class="grid-wrap">
            <table class="journal">
                <thead>
                    <tr>
                        <th class="name-col">Студент</th>
                        @foreach($subject->lessons as $lesson)
                            <th>
                                {{ $lesson->shortLabel() }}
                                <form method="post" action="{{ route('lessons.destroy', [$subject, $lesson]) }}" onsubmit="return confirm('Удалить занятие {{ $lesson->shortLabel() }}?')">
                                    @csrf @method('DELETE')
                                    <button class="del-lesson" title="Удалить занятие" style="border:none;background:none;">✕</button>
                                </form>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($subject->groups as $group)
                        <tr class="grp-row"><td colspan="{{ $subject->lessons->count() + 1 }}">{{ $group->name }}</td></tr>
                        @foreach($group->students as $student)
                            <tr>
                                <td class="name-col">{{ $student->full_name }}</td>
                                @foreach($subject->lessons as $lesson)
                                    @php $m = $marks[$student->id][$lesson->id] ?? null; @endphp
                                    <td>
                                        <div class="cell {{ $m ? 'has' : '' }}"
                                             data-lesson="{{ $lesson->id }}"
                                             data-student="{{ $student->id }}"
                                             data-status="{{ $m?->status->value }}"
                                             data-lab="{{ $m?->lab_number }}">{{ $m ? $m->status->emoji() . ($m->lab_number ? $m->lab_number : '') : '' }}</div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Поповер выбора статуса --}}
    <div class="pop-backdrop" id="popBackdrop"></div>
    <div class="popover" id="popover" style="display:none;">
        <div class="pop-grid">
            @foreach($statuses as $val => $st)
                <button type="button" data-status="{{ $val }}" title="{{ $st['label'] }}">{{ $st['emoji'] }}</button>
            @endforeach
        </div>
        <div class="pop-lab" id="popLab">
            <div class="pop-hint">Номер сданной лабы:</div>
            <input type="number" id="popLabInput" min="1" max="99" placeholder="напр. 3">
        </div>
        <div class="pop-actions">
            <button type="button" class="clear" id="popClear">✕ Очистить</button>
            <button type="button" class="btn btn-sm" id="popSave">Сохранить</button>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const markUrl = "{{ route('subjects.mark', $subject) }}";
const csrf = document.querySelector('meta[name=csrf-token]').content;
const statuses = @json($statuses);

const popover = document.getElementById('popover');
const backdrop = document.getElementById('popBackdrop');
const popLab = document.getElementById('popLab');
const popLabInput = document.getElementById('popLabInput');
let activeCell = null;
let chosenStatus = null;

function openPop(cell) {
    activeCell = cell;
    chosenStatus = cell.dataset.status || null;
    popLabInput.value = cell.dataset.lab || '';
    popLab.classList.toggle('show', chosenStatus === 'lab_done');

    const r = cell.getBoundingClientRect();
    popover.style.display = 'block';
    backdrop.classList.add('open');
    let top = r.bottom + window.scrollY + 6;
    let left = r.left + window.scrollX;
    const pw = 230;
    if (left + pw > window.innerWidth) left = window.innerWidth - pw - 12;
    popover.style.top = top + 'px';
    popover.style.left = left + 'px';
}
function closePop() {
    popover.style.display = 'none';
    backdrop.classList.remove('open');
    activeCell = null;
}

document.querySelectorAll('.cell').forEach(c => c.addEventListener('click', () => openPop(c)));
backdrop.addEventListener('click', closePop);

popover.querySelectorAll('.pop-grid button').forEach(b => {
    b.addEventListener('click', () => {
        chosenStatus = b.dataset.status;
        popLab.classList.toggle('show', chosenStatus === 'lab_done');
        if (chosenStatus !== 'lab_done') save();
        else popLabInput.focus();
    });
});
document.getElementById('popClear').addEventListener('click', () => { chosenStatus = null; save(); });
document.getElementById('popSave').addEventListener('click', save);

async function save() {
    if (!activeCell) return;
    const payload = {
        lesson_id: activeCell.dataset.lesson,
        student_id: activeCell.dataset.student,
        status: chosenStatus || '',
        lab_number: (chosenStatus === 'lab_done' && popLabInput.value) ? popLabInput.value : null,
    };
    const cell = activeCell;
    try {
        const res = await fetch(markUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (data.ok) {
            cell.dataset.status = data.status || '';
            cell.dataset.lab = data.lab_number || '';
            cell.textContent = data.status ? (data.emoji + (data.lab_number ? data.lab_number : '')) : '';
            cell.classList.toggle('has', !!data.status);
        }
    } catch (e) { alert('Ошибка сохранения'); }
    closePop();
}
</script>
@endpush
