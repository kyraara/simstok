{{--
    Pilihan dengan pencarian (mis. "Cari pangkalan…").
    - options: [['id' => 1, 'label' => 'Nama', 'sub' => 'keterangan'], ...]
    - model: nama properti Alpine di parent yang menyimpan id terpilih (opsional)
--}}
@props(['name', 'options', 'selected' => null, 'placeholder' => 'Cari…', 'model' => null, 'invalid' => false])
<div x-data="{
        open: false,
        query: '',
        options: @js($options),
        selectedId: @js($selected ? (int) $selected : null),
        active: 0,
        get filtered() {
            const q = this.query.toLowerCase().trim();
            return q ? this.options.filter(o => (o.label + ' ' + (o.sub ?? '')).toLowerCase().includes(q)) : this.options;
        },
        get selected() { return this.options.find(o => o.id === this.selectedId) ?? null; },
        choose(o) {
            this.selectedId = o.id;
            this.query = '';
            this.open = false;
            @if ($model) {{ $model }} = o.id; @endif
        },
        move(step) {
            if (!this.open) { this.open = true; return; }
            const n = this.filtered.length;
            if (n) this.active = (this.active + step + n) % n;
        },
        enter() { const o = this.filtered[this.active]; if (this.open && o) this.choose(o); },
    }"
    @if ($model) x-init="{{ $model }} = selectedId" @endif
    @click.outside="open = false; query = ''" class="relative">
    <input type="hidden" name="{{ $name }}" :value="selectedId ?? ''">
    <div class="relative">
        <input type="text" autocomplete="off" role="combobox" :aria-expanded="open"
            class="input pr-9 {{ $invalid ? 'input-error' : '' }}"
            :placeholder="selected ? selected.label : @js($placeholder)"
            :class="selected && !open && 'placeholder:text-ink placeholder:font-medium'"
            x-model="query" @focus="open = true" @input="open = true; active = 0"
            @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)"
            @keydown.enter.prevent="enter()" @keydown.escape="open = false">
        <x-icon name="chevron-down" class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-steel" />
    </div>
    <ul x-show="open" x-cloak x-transition.opacity class="card absolute z-20 mt-1 max-h-64 w-full overflow-y-auto p-1 shadow-lg" role="listbox">
        <template x-for="(o, i) in filtered" :key="o.id">
            <li role="option" :aria-selected="o.id === selectedId" @click="choose(o)" @mouseenter="active = i"
                class="flex cursor-pointer items-center justify-between gap-2 rounded-md px-3 py-2 text-sm"
                :class="i === active ? 'bg-paper' : ''">
                <span>
                    <span class="block font-medium" x-text="o.label"></span>
                    <span class="block text-xs text-muted" x-show="o.sub" x-text="o.sub"></span>
                </span>
                <x-icon name="check" class="size-4 text-gas" x-show="o.id === selectedId" />
            </li>
        </template>
        <li x-show="filtered.length === 0" class="px-3 py-2 text-sm text-muted">Tidak ditemukan.</li>
    </ul>
</div>
