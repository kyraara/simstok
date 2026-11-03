@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition.opacity
        class="mb-5 flex items-start gap-3 rounded-card border border-gas/25 bg-gas/8 px-4 py-3 text-sm text-gas-dark" role="status">
        <x-icon name="check-circle" class="mt-0.5 size-5 shrink-0" />
        <div class="flex-1">{{ session('success') }}</div>
        <button type="button" @click="show = false" class="text-gas-dark/60 hover:text-gas-dark" aria-label="Tutup"><x-icon name="x" class="size-4" /></button>
    </div>
@endif

@php $generalErrors = collect(['general', 'stock'])->flatMap(fn ($key) => $errors->get($key)); @endphp
@if ($generalErrors->isNotEmpty())
    <div class="mb-5 flex items-start gap-3 rounded-card border border-flame/30 bg-flame/8 px-4 py-3 text-sm text-flame" role="alert">
        <x-icon name="alert" class="mt-0.5 size-5 shrink-0" />
        <div class="flex-1 space-y-0.5 font-medium">
            @foreach ($generalErrors as $message)
                <p>{{ $message }}</p>
            @endforeach
        </div>
    </div>
@endif
