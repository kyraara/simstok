@props(['color' => 'steel', 'dot' => true])
@php
    $styles = [
        'gas' => 'bg-gas/10 text-gas ring-gas/20',
        'steel' => 'bg-steel/15 text-[#5d6779] ring-steel/25',
        'flame' => 'bg-flame/10 text-flame ring-flame/20',
        'amber' => 'bg-amber/15 text-[#a8661a] ring-amber/30',
        'ink' => 'bg-ink/5 text-ink ring-ink/10',
    ];
    $dots = ['gas' => 'bg-gas', 'steel' => 'bg-steel', 'flame' => 'bg-flame', 'amber' => 'bg-amber', 'ink' => 'bg-ink'];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset '.($styles[$color] ?? $styles['steel'])]) }}>
    @if ($dot)<span class="size-1.5 rounded-full {{ $dots[$color] ?? 'bg-steel' }}"></span>@endif
    {{ $slot }}
</span>
