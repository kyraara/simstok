<x-layouts.app title="Profil">
    <x-page-header title="Profil & Password" subtitle="Ubah data diri dan ganti password akun Anda." />

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" class="card p-6">
            @csrf @method('PUT')
            <h2 class="font-bold">Data Profil</h2>
            <p class="mt-1 text-sm text-muted">Peran: <span class="font-semibold text-ink">{{ $user->roleLabel() }}</span></p>

            <div class="mt-5 space-y-4">
                <x-field label="Nama lengkap" name="name" required>
                    <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') input-error @enderror">
                </x-field>
                <x-field label="Username" name="username" required>
                    <input id="username" name="username" value="{{ old('username', $user->username) }}" required class="input @error('username') input-error @enderror">
                </x-field>
                <x-field label="Email" name="email" required>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="input @error('email') input-error @enderror">
                </x-field>
            </div>
            <div class="mt-6 flex justify-end"><button class="btn btn-primary"><x-icon name="save" class="size-4" /> Simpan Profil</button></div>
        </form>

        <form method="POST" action="{{ route('profile.password') }}" class="card p-6">
            @csrf @method('PUT')
            <h2 class="font-bold">Ganti Password</h2>
            <p class="mt-1 text-sm text-muted">Minimal 8 karakter.</p>

            <div class="mt-5 space-y-4">
                @foreach (['current_password' => 'Password saat ini', 'password' => 'Password baru', 'password_confirmation' => 'Ulangi password baru'] as $name => $label)
                    <div>
                        <label for="{{ $name }}" class="label">{{ $label }}</label>
                        <input id="{{ $name }}" name="{{ $name }}" type="password" required autocomplete="{{ $name === 'current_password' ? 'current-password' : 'new-password' }}"
                            class="input {{ $errors->password->has($name) ? 'input-error' : '' }}">
                        @if ($errors->password->has($name))
                            <p class="mt-1 text-xs font-medium text-flame">{{ $errors->password->first($name) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="mt-6 flex justify-end"><button class="btn btn-primary"><x-icon name="key" class="size-4" /> Ganti Password</button></div>
        </form>
    </div>
</x-layouts.app>
