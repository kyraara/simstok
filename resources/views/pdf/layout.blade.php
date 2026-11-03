<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        @page { margin: 28px 34px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #14213d; margin: 0; }
        .kop { border-bottom: 2px solid #14213d; padding-bottom: 8px; margin-bottom: 14px; }
        .kop td { vertical-align: middle; }
        .brand { font-size: 15px; font-weight: bold; letter-spacing: .2px; }
        .muted { color: #5b6474; }
        .logo { width: 34px; height: 34px; padding-top: 11px; background: #2e7d4f; border-radius: 6px; text-align: center; color: #fff; font-weight: bold; font-size: 11px; line-height: 1; }
        h1 { font-size: 14px; margin: 0 0 2px; text-transform: uppercase; letter-spacing: .5px; }
        table { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { border: 1px solid #c9c4b8; padding: 5px 6px; }
        .grid th { background: #f1eee7; font-size: 9px; text-transform: uppercase; letter-spacing: .3px; text-align: left; }
        .grid tfoot td { background: #f7f5f0; font-weight: bold; }
        .num { text-align: right; font-family: 'DejaVu Sans Mono', monospace; white-space: nowrap; }
        .mono { font-family: 'DejaVu Sans Mono', monospace; }
        .meta td { padding: 2px 0; vertical-align: top; }
        .meta td.l { width: 95px; color: #5b6474; }
        .sign td { text-align: center; padding-top: 4px; vertical-align: top; }
        .sign .line { margin: 50px 18px 0; border-top: 1px solid #14213d; padding-top: 3px; }
        .footer { position: fixed; bottom: -14px; left: 0; right: 0; font-size: 8px; color: #8a94a6; }
        .red { color: #e4572e; } .green { color: #2e7d4f; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 8px; font-weight: bold; }
        .badge-cancel { background: #fde4dc; color: #e4572e; }
        .section { margin-top: 14px; }
        .section-title { font-size: 11px; font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width: 44px"><div class="logo">LPG</div></td>
            <td>
                <div class="brand">PT KALINDO GOA UTAMA GAS</div>
                <div class="muted">Agen Distribusi Tabung Gas LPG</div>
            </td>
            <td style="text-align: right" class="muted">
                Dicetak: {{ now()->translatedFormat('d M Y H:i') }}<br>
                oleh {{ auth()->user()?->name }}
            </td>
        </tr>
    </table>

    @yield('content')

    <div class="footer">{{ config('app.name') }} · {{ $title ?? '' }}</div>
</body>
</html>
