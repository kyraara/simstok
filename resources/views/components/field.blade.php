{{-- Pembungkus label + input + pesan error. Isi slot dengan elemen input. --}}
@props(['label', 'name' => null, 'hint' => null, 'required' => false])
<div {{ $attributes }}>
    <label @if ($name) for="{{ $name }}" @endif class="label">
        {{ $label }} @if ($required)<span class="text-flame">*</span>@endif
    </label>
    {{ $slot }}
    @if ($hint)
        <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
    @endif
    @if ($name)
        @error($name)
            <p class="mt-1 text-xs font-medium text-flame">{{ $message }}</p>
        @enderror
    @endif
</div>
