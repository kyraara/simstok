<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** F-03: kelola pengguna (tambah, ubah, nonaktifkan, atur peran). Hanya admin. */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->q, fn ($q, $s) => $q->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('username', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        return view('users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = User::create($data);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} ditambahkan.");
    }

    public function edit(User $user): View
    {
        return view('users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && (! $data['is_active'] || $data['role'] !== 'admin')) {
            return back()->withInput()->withErrors(['general' => 'Anda tidak bisa menonaktifkan atau menurunkan peran akun Anda sendiri.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $user->syncRoles([$data['role']]);

        return redirect()->route('users.index')->with('success', "Pengguna {$user->name} diperbarui.");
    }

    /** Pengguna tidak dihapus permanen karena terkait riwayat transaksi; cukup dinonaktifkan. */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['general' => 'Anda tidak bisa menonaktifkan akun Anda sendiri.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', "Pengguna {$user->name} ".($user->is_active ? 'diaktifkan kembali.' : 'dinonaktifkan.'));
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::min(8)],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
