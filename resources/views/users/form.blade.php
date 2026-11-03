@php $editing = $user->exists; @endphp
<x-layouts.app :title="$editing ? 'Ubah Pengguna' : 'Tambah Pengguna'">
    <x-page-header :title="$editing ? 'Ubah Pengguna' : 'Tambah Pengguna'" :back="route('users.index')" />

    <form method="POST" action="{{ $editing ? route('users.update', $user) : route('users.store') }}" class="card max-w-2xl p-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <x-field label="Nama lengkap" name="name" required class="sm:col-span-2">
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') input-error @enderror">
            </x-field>
            <x-field label="Username" name="username" required>
                <input id="username" name="username" value="{{ old('username', $user->username) }}" required maxlength="50" class="input @error('username') input-error @enderror">
            </x-field>
            <x-field label="Email" name="email" required>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="input @error('email') input-error @enderror">
            </x-field>
            <x-field label="Peran" name="role" required class="sm:col-span-2">
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach (\App\Models\User::ROLES as $value => $label)
                        <label class="flex cursor-pointer items-center gap-2 rounded-field border border-line px-3 py-2.5 text-sm has-[:checked]:border-gas has-[:checked]:bg-gas/5">
                            <input type="radio" name="role" value="{{ $value }}" @checked(old('role', $user->roleName()) === $value) class="text-gas focus:ring-gas/30" required>
                            <span class="font-medium">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </x-field>
            <x-field :label="$editing ? 'Password baru' : 'Password'" name="password" :required="! $editing" :hint="$editing ? 'Kosongkan bila tidak diganti.' : 'Minimal 8 karakter.'">
                <input id="password" name="password" type="password" autocomplete="new-password" @required(! $editing) class="input @error('password') input-error @enderror">
            </x-field>
            <x-field label="Ulangi password" name="password_confirmation">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="input">
            </x-field>
            <div class="sm:col-span-2"><x-active-toggle :checked="$user->is_active" hint="Pengguna nonaktif tidak bisa login." /></div>
        </div>

        <div class="mt-6 flex justify-end gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Batal</a>
            <button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan</button>
        </div>
    </form>
</x-layouts.app>
