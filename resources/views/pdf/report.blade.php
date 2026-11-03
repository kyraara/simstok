@extends('pdf.layout', ['title' => $report['title']])

@php
    $numeric = $report['numeric'] ?? [];
    $fmt = fn ($v) => is_int($v) ? number_format($v, 0, ',', '.') : $v;
@endphp

@section('content')
    <h1>{{ $report['title'] }}</h1>
    <div class="muted">{{ $report['subtitle'] }}</div>

    <table class="grid section">
        <thead>
            <tr>
                @foreach ($report['headings'] as $i => $h)
                    <th @class(['num' => in_array($i, $numeric)])>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($report['rows'] as $row)
                <tr>
                    @foreach ($row as $i => $cell)
                        <td @class(['num' => in_array($i, $numeric)])>{{ $fmt($cell) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($report['headings']) }}" style="text-align: center" class="muted">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
        @if (! empty($report['totals']) && count($report['rows']))
            <tfoot>
                <tr>
                    @foreach ($report['totals'] as $i => $cell)
                        <td @class(['num' => in_array($i, $numeric)])>{{ $fmt($cell) }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    <table class="sign" style="margin-top: 26px">
        <tr>
            <td style="width: 60%"></td>
            <td>Mengetahui,<br>Pimpinan<div class="line">(...........................)</div></td>
        </tr>
    </table>
@endsection
