<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pendukung;
use App\Models\Tps;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PemetaanDukunganTest extends TestCase
{
    use RefreshDatabase;

    private Kecamatan $kecamatanA;
    private Kecamatan $kecamatanB;
    private Desa $desaA;
    private Desa $desaB;
    private Tps $tpsA;
    private Tps $tpsB;

    private User $admin;
    private User $korcamA;
    private User $kordesA;
    private User $saksiA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kecamatanA = Kecamatan::create(['nama' => 'Kecamatan A']);
        $this->kecamatanB = Kecamatan::create(['nama' => 'Kecamatan B']);
        $this->desaA = Desa::create(['nama' => 'Desa A', 'kecamatan_id' => $this->kecamatanA->id]);
        $this->desaB = Desa::create(['nama' => 'Desa B', 'kecamatan_id' => $this->kecamatanB->id]);
        $this->tpsA = Tps::create(['nama' => 'TPS A', 'desa_id' => $this->desaA->id]);
        $this->tpsB = Tps::create(['nama' => 'TPS B', 'desa_id' => $this->desaB->id]);

        $this->admin = $this->user('admin_partai');
        $this->korcamA = $this->user('korcam', ['kecamatan_id' => $this->kecamatanA->id]);
        $this->kordesA = $this->user('kordes', ['desa_id' => $this->desaA->id]);
        $this->saksiA = $this->user('saksi_tps', ['tps_id' => $this->tpsA->id]);
    }

    private function user(string $role, array $extra = []): User
    {
        $defaults = [
            'name' => ucfirst($role),
            'username' => $role . '_' . str()->random(6),
            'password' => Hash::make('password'),
            'role' => $role,
        ];
        return User::create(array_merge($defaults, $extra));
    }

    public function test_saksi_tps_is_forbidden_from_accessing_pemetaan_dukungan(): void
    {
        $this->actingAs($this->saksiA)
            ->get(route('pemetaan-dukungan.index'))
            ->assertForbidden();

        $this->actingAs($this->saksiA)
            ->get(route('pemetaan-dukungan.create'))
            ->assertForbidden();
    }

    public function test_admin_can_access_and_see_all_supporters(): void
    {
        $pendukungA = Pendukung::create([
            'nama' => 'Pendukung A',
            'nik' => '1234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat A',
            'kecamatan_id' => $this->kecamatanA->id,
            'desa_id' => $this->desaA->id,
            'created_by' => $this->admin->id,
        ]);

        $pendukungB = Pendukung::create([
            'nama' => 'Pendukung B',
            'nik' => '2234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat B',
            'kecamatan_id' => $this->kecamatanB->id,
            'desa_id' => $this->desaB->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('pemetaan-dukungan.index'));

        $response->assertOk();
        $response->assertSee('Pendukung A');
        $response->assertSee('Pendukung B');
    }

    public function test_korcam_can_only_see_supporters_in_their_kecamatan(): void
    {
        $pendukungA = Pendukung::create([
            'nama' => 'Pendukung A',
            'nik' => '1234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat A',
            'kecamatan_id' => $this->kecamatanA->id,
            'desa_id' => $this->desaA->id,
            'created_by' => $this->admin->id,
        ]);

        $pendukungB = Pendukung::create([
            'nama' => 'Pendukung B',
            'nik' => '2234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat B',
            'kecamatan_id' => $this->kecamatanB->id,
            'desa_id' => $this->desaB->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->korcamA)
            ->get(route('pemetaan-dukungan.index'));

        $response->assertOk();
        $response->assertSee('Pendukung A');
        $response->assertDontSee('Pendukung B');
    }

    public function test_kordes_can_only_see_supporters_in_their_desa(): void
    {
        $pendukungA = Pendukung::create([
            'nama' => 'Pendukung A',
            'nik' => '1234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat A',
            'kecamatan_id' => $this->kecamatanA->id,
            'desa_id' => $this->desaA->id,
            'created_by' => $this->admin->id,
        ]);

        $desaC = Desa::create(['nama' => 'Desa C', 'kecamatan_id' => $this->kecamatanA->id]);
        $pendukungC = Pendukung::create([
            'nama' => 'Pendukung C',
            'nik' => '3234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat C',
            'kecamatan_id' => $this->kecamatanA->id,
            'desa_id' => $desaC->id,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->kordesA)
            ->get(route('pemetaan-dukungan.index'));

        $response->assertOk();
        $response->assertSee('Pendukung A');
        $response->assertDontSee('Pendukung C');
    }

    public function test_supporter_creation_validation(): void
    {
        // 1. NIK must be 16 characters
        $this->actingAs($this->admin)
            ->post(route('pemetaan-dukungan.store'), [
                'nama' => 'Ahmad',
                'nik' => '12345',
                'no_hp' => '0812345',
                'alamat' => 'Alamat',
                'kecamatan_id' => $this->kecamatanA->id,
                'desa_id' => $this->desaA->id,
            ])
            ->assertSessionHasErrors('nik');

        // 2. NIK must be unique
        Pendukung::create([
            'nama' => 'Satu',
            'nik' => '1234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat',
            'kecamatan_id' => $this->kecamatanA->id,
            'desa_id' => $this->desaA->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('pemetaan-dukungan.store'), [
                'nama' => 'Dua',
                'nik' => '1234567890123456',
                'no_hp' => '0812345',
                'alamat' => 'Alamat',
                'kecamatan_id' => $this->kecamatanA->id,
                'desa_id' => $this->desaA->id,
            ])
            ->assertSessionHasErrors('nik');
    }

    public function test_supporters_can_be_stored_and_edited(): void
    {
        Storage::fake();

        $file = UploadedFile::fake()->image('ktp.jpg');

        $response = $this->actingAs($this->admin)
            ->post(route('pemetaan-dukungan.store'), [
                'nama' => 'Pendukung Baru',
                'nik' => '9876543210123456',
                'no_hp' => '0854321',
                'alamat' => 'Alamat Baru',
                'kecamatan_id' => $this->kecamatanA->id,
                'desa_id' => $this->desaA->id,
                'ktp' => $file,
            ]);

        $response->assertRedirect(route('pemetaan-dukungan.index'));

        $this->assertDatabaseHas('pendukungs', [
            'nama' => 'Pendukung Baru',
            'nik' => '9876543210123456',
        ]);

        $pendukung = Pendukung::where('nik', '9876543210123456')->first();
        $this->assertNotNull($pendukung->ktp_path);
        Storage::assertExists($pendukung->ktp_path);

        // Edit
        $this->actingAs($this->admin)
            ->put(route('pemetaan-dukungan.update', $pendukung), [
                'nama' => 'Pendukung Baru Update',
                'nik' => '9876543210123456',
                'no_hp' => '085432199',
                'alamat' => 'Alamat Baru Update',
                'kecamatan_id' => $this->kecamatanA->id,
                'desa_id' => $this->desaA->id,
            ])
            ->assertRedirect(route('pemetaan-dukungan.index'));

        $this->assertDatabaseHas('pendukungs', [
            'id' => $pendukung->id,
            'nama' => 'Pendukung Baru Update',
            'no_hp' => '085432199',
        ]);
    }

    public function test_ktp_download_authorization(): void
    {
        Storage::fake();
        $file = UploadedFile::fake()->image('ktp.jpg');

        $pendukung = Pendukung::create([
            'nama' => 'Pendukung A',
            'nik' => '1234567890123456',
            'no_hp' => '0812345',
            'alamat' => 'Alamat A',
            'kecamatan_id' => $this->kecamatanA->id,
            'desa_id' => $this->desaA->id,
            'created_by' => $this->admin->id,
            'ktp_path' => $file->store('private/ktp'),
        ]);

        // Admin can download
        $this->actingAs($this->admin)
            ->get(route('pemetaan-dukungan.ktp', $pendukung))
            ->assertOk();

        // Korcam of same kecamatan can download
        $this->actingAs($this->korcamA)
            ->get(route('pemetaan-dukungan.ktp', $pendukung))
            ->assertOk();

        // Kordes of same desa can download
        $this->actingAs($this->kordesA)
            ->get(route('pemetaan-dukungan.ktp', $pendukung))
            ->assertOk();

        // Kordes of different desa cannot download
        $kordesB = $this->user('kordes', ['desa_id' => $this->desaB->id]);
        $this->actingAs($kordesB)
            ->get(route('pemetaan-dukungan.ktp', $pendukung))
            ->assertForbidden();
    }

    public function test_excel_export_access(): void
    {
        $this->actingAs($this->admin)
            ->get(route('pemetaan-dukungan.export'))
            ->assertOk();

        $this->actingAs($this->korcamA)
            ->get(route('pemetaan-dukungan.export'))
            ->assertOk();

        $this->actingAs($this->kordesA)
            ->get(route('pemetaan-dukungan.export'))
            ->assertForbidden();
    }
}
