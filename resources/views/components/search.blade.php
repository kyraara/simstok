@props(['placeholder' => 'Cari…'])
<form method="GET" class="relative w-full sm:max-w-xs">
    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-steel" />
    <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="input pl-9">
</form>
