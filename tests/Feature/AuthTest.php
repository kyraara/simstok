<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Skenario black box 1, 2, 8 (DESIGN.md §8). */
class AuthTest extends TestCase
{
    public function test_login_dengan_username_berhasil_masuk_dashboard(): void
    {
        $this->post('/login', ['login' => 'gudang', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->user('gudang'));
    }

    public function test_login_dengan_email_berhasil(): void
    {
        $this->post('/login', ['login' => 'pimpinan@simstok.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_login_password_salah_menampilkan_error(): void
    {
        $this->from('/login')->post('/login', ['login' => 'admin', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $this->user('gudang')->update(['is_active' => false]);

        $this->post('/login', ['login' => 'gudang', 'password' => 'password'])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_petugas_gudang_tidak_boleh_membuka_halaman_pengguna(): void
    {
        $this->actingAs($this->user('gudang'))->get('/users')->assertForbidden();
    }

    public function test_pimpinan_tidak_boleh_input_distribusi_dan_master(): void
    {
        $this->actingAs($this->user('pimpinan'));

        $this->get('/distributions/create')->assertForbidden();
        $this->get('/master/outlets/create')->assertForbidden();
        $this->get('/master/outlets')->assertOk();
    }

    public function test_gudang_tidak_boleh_menyetujui_penyesuaian(): void
    {
        $this->openingStock(['LPG3' => ['isi' => 10]]);
        $this->actingAs($this->user('gudang'));
        $this->post('/adjustments', [
            'date' => now()->toDateString(), 'type' => 'rusak', 'reason' => 'Tabung bocor',
            'items' => [['cylinder_type_id' => $this->type('LPG3')->id, 'condition' => 'isi', 'qty' => 2]],
        ]);

        $this->post('/adjustments/2/approve')->assertForbidden();
    }
}
