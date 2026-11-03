<x-layouts.app title="Pengguna">
    <x-page-header title="Pengguna" subtitle="Akun dan peran pengguna sistem.">
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah Pengguna</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="border-b border-line p-4"><x-search placeholder="Cari nama, username, email…" /></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>Username</th><th>Email</th><th>Peran</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr @class(['opacity-60' => ! $user->is_active])>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex size-8 items-center justify-center rounded-full bg-ink/5 text-xs font-semibold">{{ $user->initials() }}</span>
                                    <span class="font-medium">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="num text-sm">{{ $user->username }}</td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td><x-badge color="ink" :dot="false">{{ $user->roleLabel() }}</x-badge></td>
                            <td>@if ($user->is_active)<x-badge color="gas">Aktif</x-badge>@else<x-badge>Nonaktif</x-badge>@endif</td>
                            <td class="text-right whitespace-nowrap">
                                <a href="{{ route('users.edit', $user) }}" class="btn-ghost btn btn-sm" title="Ubah"><x-icon name="pencil" class="size-4" /></a>
                                @unless ($user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline"
                                        onsubmit="return confirm(@js(($user->is_active ? 'Nonaktifkan ' : 'Aktifkan kembali ').$user->name.'?'))">
                                        @csrf @method('DELETE')
                                        <button class="btn-ghost btn btn-sm {{ $user->is_active ? 'hover:text-flame' : 'hover:text-gas' }}" title="{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <x-icon :name="$user->is_active ? 'ban' : 'check-circle'" class="size-4" />
                                        </button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())<div class="border-t border-line p-4">{{ $users->links() }}</div>@endif
    </div>
</x-layouts.app>
