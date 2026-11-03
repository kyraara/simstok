@props(['formats' => ['pdf', 'xlsx']])
@php $query = request()->except('export'); @endphp
<div class="flex gap-2">
    @if (in_array('pdf', $formats))
        <a href="{{ request()->url().'?'.http_build_query($query + ['export' => 'pdf']) }}" class="btn btn-secondary"><x-icon name="download" class="size-4" /> PDF</a>
    @endif
    @if (in_array('xlsx', $formats))
        <a href="{{ request()->url().'?'.http_build_query($query + ['export' => 'xlsx']) }}" class="btn btn-secondary"><x-icon name="sheet" class="size-4" /> Excel</a>
    @endif
</div>
