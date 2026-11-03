@extends('pdf.layout', ['title' => 'Surat Jalan '.$distribution->number])

@section('content')
    <table>
        <tr>
            <td>
                <h1>Surat Jalan</h1>
                <div class="mono" style="font-size: 12px; font-weight: bold">{{ $distribution->number }}</div>
                @unless ($distribution->isPosted())
                    <span class="badge badge-cancel">DIBATALKAN — {{ $distribution->cancel_reason }}</span>
                @endunless
            </td>
            <td style="text-align: right; vertical-align: top">
                <div class="muted">Tanggal</div>
                <div style="font-weight: bold; font-size: 11px">{{ $distribution->date->translatedFormat('d F Y') }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-top: 14px">
        <tr>
            <td style="width: 55%; vertical-align: top">
                <table class="meta">
                    <tr><td class="l">Kepada</td><td><b>{{ $distribution->outlet->name }}</b> ({{ $distribution->outlet->code }})</td></tr>
                    <tr><td class="l">Pemilik</td><td>{{ $distribution->outlet->owner_name ?: '-' }}</td></tr>
                    <tr><td class="l">Alamat</td><td>{{ $distribution->outlet->address ?: '-' }}</td></tr>
                    <tr><td class="l">No. HP</td><td>{{ $distribution->outlet->phone ?: '-' }}</td></tr>
                </table>
            </td>
            <td style="vertical-align: top">
                <table class="meta">
                    <tr><td class="l">Kendaraan</td><td class="mono">{{ $distribution->vehicle?->plate_number ?? '-' }}</td></tr>
                    <tr><td class="l">Jenis</td><td>{{ $distribution->vehicle?->type ?? '-' }}</td></tr>
                    <tr><td class="l">Sopir</td><td>{{ $distribution->vehicle?->driver_name ?? '-' }}</td></tr>
                    <tr><td class="l">Petugas</td><td>{{ $distribution->user->name }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="grid section">
        <thead>
            <tr>
                <th style="width: 28px">No</th>
                <th>Jenis Tabung</th>
                <th class="num" style="width: 110px">Tabung Isi Dikirim</th>
                <th class="num" style="width: 120px">Tabung Kosong Diterima</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($distribution->items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->cylinderType->name }}</td>
                    <td class="num">{{ number_format($item->qty_full_out, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format($item->qty_empty_in, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="num">{{ number_format($distribution->items->sum('qty_full_out'), 0, ',', '.') }}</td>
                <td class="num">{{ number_format($distribution->items->sum('qty_empty_in'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($distribution->note)
        <p style="margin-top: 8px"><span class="muted">Catatan:</span> {{ $distribution->note }}</p>
    @endif

    <p class="muted" style="margin-top: 10px; font-size: 9px">Barang telah diterima dalam keadaan baik dan jumlah sesuai. Tabung yang rusak/bocor harap dilaporkan saat serah terima.</p>

    <table class="sign" style="margin-top: 22px">
        <tr>
            <td>Petugas Gudang<div class="line">{{ $distribution->user->name }}</div></td>
            <td>Sopir<div class="line">{{ $distribution->vehicle?->driver_name ?? '(...........................)' }}</div></td>
            <td>Penerima (Pangkalan)<div class="line">{{ $distribution->outlet->owner_name ?: '(...........................)' }}</div></td>
        </tr>
    </table>
@endsection
