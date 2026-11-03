@props(['icon' => 'database', 'title' => 'Belum ada data'])
<div class="flex flex-col items-center justify-center px-6 py-14 text-center">
    <div class="mb-3 flex size-12 items-center justify-center rounded-full bg-paper text-steel">
        <x-icon :name="$icon" class="size-6" />
    </div>
    <p class="font-semibold text-ink">{{ $title }}</p>
    @if ($slot->isNotEmpty())
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $slot }}</p>
    @endif
</div>
