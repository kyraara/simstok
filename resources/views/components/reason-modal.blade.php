{{--
    Tombol + modal yang meminta alasan sebelum mengirim form (batal transaksi, tolak persetujuan).
    Pemakaian: <x-reason-modal :action="route(...)" title="Batalkan?" button="Batalkan" />
--}}
@props([
    'action',
    'title',
    'description' => null,
    'button' => 'Kirim',
    'confirm' => null,
    'field' => 'reason',
    'label' => 'Alasan',
    'required' => true,
    'variant' => 'danger',
    'icon' => 'ban',
])
<div x-data="{ open: {{ $errors->has($field) ? 'true' : 'false' }} }" class="inline-block">
    <button type="button" @click="open = true" class="btn {{ $variant === 'danger' ? 'btn-secondary text-flame' : 'btn-primary' }}">
        <x-icon :name="$icon" class="size-4" /> {{ $button }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center" @keydown.escape.window="open = false">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-ink/40" @click="open = false"></div>
            <form x-show="open" x-transition x-trap.noscroll="open" method="POST" action="{{ $action }}"
                class="card relative w-full max-w-md p-6 shadow-xl">
                @csrf
                <h3 class="text-lg font-bold text-ink">{{ $title }}</h3>
                @if ($description)
                    <p class="mt-1 text-sm text-muted">{{ $description }}</p>
                @endif
                <div class="mt-4">
                    <label for="modal-{{ $field }}" class="label">{{ $label }} @if ($required)<span class="text-flame">*</span>@endif</label>
                    <textarea id="modal-{{ $field }}" name="{{ $field }}" rows="3" class="input @error($field) input-error @enderror" @if ($required) required @endif maxlength="255">{{ old($field) }}</textarea>
                    @error($field)
                        <p class="mt-1 text-xs font-medium text-flame">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="btn btn-secondary" @click="open = false">Tutup</button>
                    <button type="submit" class="btn {{ $variant === 'danger' ? 'btn-danger' : 'btn-primary' }}">{{ $confirm ?? $button }}</button>
                </div>
            </form>
        </div>
    </template>
</div>
