@extends('pdf.layout', ['title' => 'Laporan Stok Opname '.$opname->number])

@section('content')
    <h1>Laporan Stok Opname & Selisih</h1>
    <table class="meta" style="margin: 8px 0 4px">
        <tr><td class="l">No. Opname</td><td class="mono"><b>{{ $opname->number }}</b></td><td class="l">Status</td><td><b>{{ $opname->status->label() }}</b></td></tr>
        <tr><td class="l">Tanggal</td><td>{{ $opname->date->translatedFormat('d F Y') }}</td><td class="l">Diajukan</td><td>{{ $opname->submitted_at?->translatedFormat('d M Y H:i') ?? '-' }}</td></tr>
        <tr><td class="l">Petugas</td><td>{{ $opname->user->name }}</td><td class="l">Peninjau</td><td>{{ $opname->approver?->name ?? '-' }} {{ $opname->approved_at ? '('.$opname->approved_at->translatedFormat('d M Y').')' : '' }}</td></tr>
        @if ($opname->adjustment)
            <tr><td class="l">Penyesuaian</td><td class="mono" colspan="3">{{ $opname->adjustment->number }}</td></tr>
        @endif
    </table>

    <table class="grid section">
        <thead>
            <tr>
                <th>Jenis Tabung</th>
                <th>Kondisi</th>
                <th class="num">Stok Sistem</th>
                <th class="num">Stok Fisik</th>
                <th class="num">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($opname->items->sortBy(fn ($i) => [$i->cylinderType->weight_kg, $i->id]) as $item)
                <tr>
                    <td>{{ $item->cylinderType->name }}</td>
                    <td>{{ $item->condition->label() }}</td>
                    <td class="num">{{ number_format($item->system_qty, 0, ',', '.') }}</td>
                    <td class="num">{{ $item->physical_qty !== null ? number_format($item->physical_qty, 0, ',', '.') : '-' }}</td>
                    <td class="num {{ $item->difference < 0 ? 'red' : ($item->difference > 0 ? 'green' : '') }}">{{ $item->difference > 0 ? '+' : '' }}{{ number_format($item->difference, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="num">{{ number_format($opname->items->sum('system_qty'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format($opname->items->sum('physical_qty'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format($opname->items->sum('difference'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($opname->note)
        <p style="margin-top: 8px"><span class="muted">Catatan petugas:</span> {{ $opname->note }}</p>
    @endif
    @if ($opname->review_note)
        <p><span class="muted">Catatan peninjau:</span> {{ $opname->review_note }}</p>
    @endif

    <table class="sign" style="margin-top: 26px">
        <tr>
            <td>Petugas Gudang<div class="line">{{ $opname->user->name }}</div></td>
            <td>Pimpinan<div class="line">{{ $opname->approver?->name ?? '(...........................)' }}</div></td>
        </tr>
    </table>
@endsection
