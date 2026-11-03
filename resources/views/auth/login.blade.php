<x-layouts.guest title="Masuk">
    <div class="grid min-h-screen lg:grid-cols-[1.1fr_1fr]">
        {{-- Panel identitas --}}
        <div class="relative hidden overflow-hidden bg-ink p-12 text-white lg:flex lg:flex-col">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-gas"><x-icon name="cylinder" class="size-6" /></div>
                <div>
                    <div class="text-lg font-bold tracking-tight">SIMSTOK-LPG</div>
                    <div class="text-xs text-white/50">PT Kalindo Goa Utama Gas</div>
                </div>
            </div>

            <div class="mt-auto max-w-md">
                <h1 class="text-4xl leading-tight font-bold tracking-tight">Stok tabung isi, kosong, dan rusak dalam satu catatan.</h1>
                <p class="mt-4 text-white/60">Catat penerimaan dari SPBE, distribusi ke pangkalan, dan stok opname. Saldo selalu terkini dan setiap mutasi bisa ditelusuri.</p>

                <div class="mt-10 grid grid-cols-3 gap-3">
                    @foreach ([['Isi', 'bg-gas'], ['Kosong', 'bg-steel'], ['Rusak', 'bg-flame']] as [$label, $color])
                        <div class="rounded-card border border-white/10 bg-white/5 p-4">
                            <div class="flex items-center gap-2 text-xs font-semibold tracking-wide text-white/60 uppercase">
                                <span class="size-2 rounded-full {{ $color }}"></span>{{ $label }}
                            </div>
                            <div class="mt-3 flex h-12 items-end gap-1">
                                @foreach ([0.5, 0.8, 0.6, 1, 0.7] as $h)
                                    <div class="flex-1 rounded-sm {{ $color }}" style="height: {{ $h * 100 }}%; opacity: {{ 0.35 + $h * 0.5 }}"></div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <svg class="pointer-events-none absolute -top-24 -right-24 size-96 text-white/[0.03]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/></svg>
        </div>

        {{-- Form login --}}
        <div class="flex items-center justify-center px-5 py-12">
            <div class="w-full max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-gas text-white"><x-icon name="cylinder" class="size-6" /></div>
                    <div>
                        <div class="text-lg font-bold tracking-tight">SIMSTOK-LPG</div>
                        <div class="text-xs text-muted">PT Kalindo Goa Utama Gas</div>
                    </div>
                </div>

                <h2 class="text-2xl font-bold tracking-tight">Masuk</h2>
                <p class="mt-1 text-sm text-muted">Gunakan username atau email yang terdaftar.</p>

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                    @csrf
                    <x-field label="Username / Email" name="login">
                        <input id="login" name="login" type="text" value="{{ old('login') }}" required autofocus autocomplete="username"
                            class="input @error('login') input-error @enderror">
                    </x-field>

                    <x-field label="Password" name="password">
                        <div x-data="{ show: false }" class="relative">
                            <input id="password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" class="input pr-10">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center px-3 text-steel hover:text-ink" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                                <x-icon name="eye" class="size-4" />
                            </button>
                        </div>
                    </x-field>

                    <label class="flex items-center gap-2 text-sm text-muted">
                        <input type="checkbox" name="remember" class="size-4 rounded border-line text-gas focus:ring-gas/30">
                        Ingat saya
                    </label>

                    <button type="submit" class="btn btn-primary w-full py-2.5">Masuk</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.guest>
