{{-- Tampilan layar generik untuk laporan berbentuk tabel dari ReportService. --}}
@php
    $numeric = $report['numeric'] ?? [];
    $fmt = fn ($v) => is_int($v) ? number_format($v, 0, ',', '.') : $v;
@endphp
<x-layouts.app :title="$report['title']">
    <x-page-header :title="$report['title']" :subtitle="$report['subtitle']" :back="route('reports.index')">
        <x-slot:actions><x-export-buttons /></x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mb-6 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        @isset($filters['period'])
            <div><label class="label" for="from">Dari</label><input id="from" type="date" name="from" value="{{ $filters['period'][0]->toDateString() }}" class="input"></div>
            <div><label class="label" for="to">Sampai</label><input id="to" type="date" name="to" value="{{ $filters['period'][1]->toDateString() }}" class="input"></div>
        @endisset
        @isset($filters['date'])
            <div><label class="label" for="date">Tanggal</label><input id="date" type="date" name="date" value="{{ $filters['date']->toDateString() }}" max="{{ now()->toDateString() }}" class="input"></div>
        @endisset
        @isset($filters['select'])
            @php $s = $filters['select']; @endphp
            <div class="sm:min-w-56">
                <label class="label" for="{{ $s['name'] }}">{{ $s['label'] }}</label>
                <select id="{{ $s['name'] }}" name="{{ $s['name'] }}" class="input">
                    <option value="">{{ $s['all'] }}</option>
                    @foreach ($s['options'] as $id => $name)
                        <option value="{{ $id }}" @selected(request($s['name']) == $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        @endisset
        <button class="btn btn-primary"><x-icon name="filter" class="size-4" /> Tampilkan</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    @foreach ($report['headings'] as $i => $h)
                        <th @class(['text-right' => in_array($i, $numeric), 'whitespace-nowrap'])>{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $i => $cell)
                            <td @class(['num text-right' => in_array($i, $numeric), 'whitespace-nowrap' => $i < 2])>{{ $fmt($cell) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($report['headings']) }}"><x-empty icon="report" title="Tidak ada data pada filter ini" /></td></tr>
                @endforelse
            </tbody>
            @if (! empty($report['totals']) && count($report['rows']))
                <tfoot>
                    <tr class="bg-paper/70 font-bold">
                        @foreach ($report['totals'] as $i => $cell)
                            <td @class(['px-4 py-3 whitespace-nowrap', 'num text-right' => in_array($i, $numeric)])>{{ $fmt($cell) }}</td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-layouts.app>
