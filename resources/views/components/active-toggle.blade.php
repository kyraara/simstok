@props(['checked' => true, 'label' => 'Aktif', 'hint' => 'Data nonaktif tidak muncul di pilihan form transaksi.'])
<label class="flex cursor-pointer items-start gap-3">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $checked)) class="mt-0.5 size-4 rounded border-line text-gas focus:ring-gas/30">
    <span>
        <span class="block text-sm font-medium">{{ $label }}</span>
        <span class="block text-xs text-muted">{{ $hint }}</span>
    </span>
</label>
