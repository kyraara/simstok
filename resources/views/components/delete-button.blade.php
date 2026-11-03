@props(['action', 'confirm' => 'Hapus data ini?'])
<form method="POST" action="{{ $action }}" class="inline" onsubmit="return confirm(@js($confirm))">
    @csrf @method('DELETE')
    <button type="submit" class="btn-ghost btn btn-sm hover:text-flame" title="Hapus"><x-icon name="trash" class="size-4" /></button>
</form>
