@unless ($doc->isPosted())
    <div class="mb-6 flex items-start gap-3 rounded-card border border-flame/30 bg-flame/5 px-4 py-3 text-sm">
        <x-icon name="ban" class="mt-0.5 size-5 shrink-0 text-flame" />
        <div>
            <div class="font-semibold text-flame">Dokumen ini dibatalkan</div>
            <div class="mt-0.5 text-ink">{{ $doc->cancel_reason }}</div>
            <div class="mt-1 text-xs text-muted">oleh {{ $doc->canceller?->name ?? '—' }} · {{ $doc->cancelled_at?->translatedFormat('d M Y H:i') }}. Stok sudah dikembalikan.</div>
        </div>
    </div>
@endunless
