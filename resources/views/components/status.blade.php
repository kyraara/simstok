{{-- Badge untuk enum apa pun yang punya label() dan color(): status transaksi, kondisi stok, dll. --}}
@props(['value'])
<x-badge :color="$value->color()" {{ $attributes }}>{{ $value->label() }}</x-badge>
