{{-- Logo perusahaan (public/logo.png) di atas ubin putih, karena latar gambarnya putih. --}}
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-lg bg-white p-1 shadow-sm']) }}>
    <img src="{{ asset('logo.png') }}" alt="Logo" class="size-full object-contain">
</span>
