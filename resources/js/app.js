import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

const fmt = (n) => new Intl.NumberFormat('id-ID').format(n || 0);
const int = (v) => {
    const n = parseInt(v, 10);
    return Number.isFinite(n) && n > 0 ? n : 0;
};

/**
 * Form distribusi: isi dikirim otomatis disalin ke kosong diterima (tukar 1:1)
 * sampai petugas mengubah kolom kosong secara manual. Menampilkan sisa kuota pangkalan.
 */
Alpine.data('distributionForm', ({ rows, quotaUrl, outletId, date }) => ({
    rows,
    outletId,
    date,
    quota: {},
    quotaLoading: false,

    init() {
        this.rows.forEach((row) => {
            row.linked = row.linked ?? int(row.qty_empty_in) === int(row.qty_full_out);
        });
        this.loadQuota();
        this.$watch('outletId', () => this.loadQuota());
        this.$watch('date', () => this.loadQuota());
    },

    onFullInput(row) {
        if (row.linked) row.qty_empty_in = row.qty_full_out;
    },

    onEmptyInput(row) {
        row.linked = false;
    },

    exceeds(row) {
        return int(row.qty_full_out) > row.stock_full;
    },

    get hasError() {
        return this.rows.some((r) => this.exceeds(r));
    },

    get totalFull() {
        return this.rows.reduce((s, r) => s + int(r.qty_full_out), 0);
    },

    get totalEmpty() {
        return this.rows.reduce((s, r) => s + int(r.qty_empty_in), 0);
    },

    quotaFor(row) {
        return this.quota[row.cylinder_type_id] ?? null;
    },

    quotaRemaining(row) {
        const q = this.quotaFor(row);
        return q ? q.quota - q.used - int(row.qty_full_out) : null;
    },

    async loadQuota() {
        if (!this.outletId) {
            this.quota = {};
            return;
        }
        this.quotaLoading = true;
        try {
            const url = `${quotaUrl}?outlet_id=${encodeURIComponent(this.outletId)}&date=${encodeURIComponent(this.date || '')}`;
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            this.quota = res.ok ? await res.json() : {};
        } catch {
            this.quota = {};
        } finally {
            this.quotaLoading = false;
        }
    },

    fmt,
}));

/** Form penerimaan SPBE: tabung kosong yang dikirim tidak boleh melebihi stok kosong. */
Alpine.data('receiptForm', ({ rows }) => ({
    rows,

    init() {
        this.rows.forEach((row) => {
            row.linked = row.linked ?? int(row.qty_empty_out) === int(row.qty_full_in);
        });
    },

    onFullInput(row) {
        if (row.linked) row.qty_empty_out = Math.min(int(row.qty_full_in), row.stock_empty);
    },

    onEmptyInput(row) {
        row.linked = false;
    },

    exceeds(row) {
        return int(row.qty_empty_out) > row.stock_empty;
    },

    get hasError() {
        return this.rows.some((r) => this.exceeds(r));
    },

    get totalFull() {
        return this.rows.reduce((s, r) => s + int(r.qty_full_in), 0);
    },

    get totalEmpty() {
        return this.rows.reduce((s, r) => s + int(r.qty_empty_out), 0);
    },

    fmt,
}));

/** Form penyesuaian: baris dinamis, pilihan kondisi bergantung pada jenis penyesuaian. */
Alpine.data('adjustmentForm', ({ type, rows, stocks }) => ({
    type,
    rows,
    stocks,

    init() {
        if (this.rows.length === 0) this.addRow();
        this.$watch('type', () => this.rows.forEach((r) => this.normalize(r)));
    },

    addRow() {
        this.rows.push({ cylinder_type_id: '', condition: 'isi', direction: 'minus', qty: '' });
    },

    removeRow(index) {
        this.rows.splice(index, 1);
        if (this.rows.length === 0) this.addRow();
    },

    conditions() {
        return this.type === 'rusak' ? ['isi', 'kosong'] : ['isi', 'kosong', 'rusak'];
    },

    normalize(row) {
        if (!this.conditions().includes(row.condition)) row.condition = 'isi';
    },

    isOutgoing(row) {
        return this.type !== 'koreksi' || row.direction === 'minus';
    },

    available(row) {
        return this.stocks[row.cylinder_type_id]?.[row.condition] ?? 0;
    },

    exceeds(row) {
        return row.cylinder_type_id && this.isOutgoing(row) && int(row.qty) > this.available(row);
    },

    get hasError() {
        return this.rows.some((r) => this.exceeds(r));
    },

    fmt,
}));

/**
 * Lonceng notifikasi: data awal dari server, diperbarui tiap 30 detik dan saat tab kembali aktif.
 * Klik notifikasi membuka /notifications/{id} yang menandai dibaca lalu mengarahkan ke dokumennya.
 */
Alpine.data('notificationBell', ({ feed, url, readAllUrl }) => ({
    open: false,
    unread: feed.unread,
    items: feed.items,
    timer: null,

    init() {
        this.timer = setInterval(() => this.refresh(), 30000);
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') this.refresh();
        });
    },

    destroy() {
        clearInterval(this.timer);
    },

    toggle() {
        this.open = !this.open;
        if (this.open) this.refresh();
    },

    apply(data) {
        this.unread = data.unread;
        this.items = data.items;
    },

    async refresh() {
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (res.ok) this.apply(await res.json());
        } catch {
            // Jaringan putus: biarkan data lama tampil.
        }
    },

    async readAll() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const res = await fetch(readAllUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token },
        });
        if (res.ok) this.apply(await res.json());
    },
}));

/** Grafik batang distribusi 30 hari; Chart.js dimuat hanya di halaman yang membutuhkannya. */
Alpine.data('distributionChart', ({ labels, datasets }) => ({
    async init() {
        const { default: Chart } = await import('chart.js/auto');
        const css = getComputedStyle(document.documentElement);
        Chart.defaults.font.family = css.getPropertyValue('--font-sans');
        Chart.defaults.color = '#5b6474';

        new Chart(this.$refs.canvas, {
            type: 'bar',
            data: { labels, datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, useBorderRadius: true, borderRadius: 2 } },
                    tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${fmt(c.parsed.y)} tabung` } },
                },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12 } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#efece5' }, border: { display: false }, ticks: { callback: (v) => fmt(v) } },
                },
            },
        });
    },
}));

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();
