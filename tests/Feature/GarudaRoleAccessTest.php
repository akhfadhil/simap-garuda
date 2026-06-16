<?php

namespace Tests\Feature;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\PemiluSetting;
use App\Models\RekapCaleg;
use App\Models\RekapCalegSuara;
use App\Models\RekapHeader;
use App\Models\RekapPartai;
use App\Models\RekapPartaiSuara;
use App\Models\Tps;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GarudaRoleAccessTest extends TestCase
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
        PemiluSetting::create(['jenis' => 'dpr_ri', 'is_active' => true]);

        $this->admin = $this->user('admin');
        $this->korcamA = $this->user('ppk', ['kecamatan_id' => $this->kecamatanA->id]);
        $this->kordesA = $this->user('pps', ['desa_id' => $this->desaA->id]);
        $this->saksiA = $this->user('kpps', ['tps_id' => $this->tpsA->id]);
    }

    public function test_admin_can_enter_all_garuda_view_levels(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.kecamatan.view', $this->kecamatanB))
            ->assertRedirect(route('dashboard.ppk'));

        $this->actingAs($this->admin)
            ->get(route('admin.desa.view', $this->desaB))
            ->assertRedirect(route('dashboard.pps'));

        $this->actingAs($this->admin)
            ->get(route('admin.tps.view', $this->tpsB))
            ->assertRedirect(route('dashboard.kpps'));
    }

    public function test_korcam_can_only_open_kordes_inside_own_kecamatan(): void
    {
        $this->actingAs($this->korcamA)
            ->get(route('ppk.view-pps', $this->desaA))
            ->assertRedirect(route('dashboard.pps'));

        $this->actingAs($this->korcamA)
            ->get(route('ppk.view-pps', $this->desaB))
            ->assertForbidden();
    }

    public function test_kordes_can_only_open_saksi_tps_inside_own_desa(): void
    {
        $this->actingAs($this->kordesA)
            ->get(route('pps.view-tps', $this->tpsA))
            ->assertRedirect(route('dashboard.kpps'));

        $this->actingAs($this->kordesA)
            ->get(route('pps.view-tps', $this->tpsB))
            ->assertForbidden();
    }

    public function test_legacy_kpu_roles_cannot_login_to_garuda(): void
    {
        $komisioner = $this->user('komisioner', [
            'username' => 'komisioner',
            'password' => Hash::make('secret123'),
        ]);

        $this->post(route('login.post'), [
            'username' => $komisioner->username,
            'password' => 'secret123',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_removed_kpu_document_routes_are_not_registered(): void
    {
        $this->assertFalse(\Route::has('dokumen.admin'));
        $this->assertFalse(\Route::has('dokumen.upload'));
        $this->assertFalse(\Route::has('admin.rekap.unlock'));
        $this->assertFalse(\Route::has('admin.rekap.inline-update'));
    }

    public function test_non_party_rekap_types_are_not_accessible(): void
    {
        $this->actingAs($this->saksiA)
            ->get(route('rekap.form', 'ppwp'))
            ->assertNotFound();

        $this->actingAs($this->admin)
            ->post(route('admin.setup.ppwp.store'), [
                'calons' => [
                    ['nomor_urut' => 1, 'nama_paslon' => 'Paslon Legacy'],
                ],
            ])
            ->assertStatus(410);
    }

    public function test_admin_can_only_create_garuda_party_master(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.setup.index'))
            ->post(route('admin.setup.partai.store'), [
                'jenis' => 'dpr_ri',
                'partais' => [
                    ['nomor_urut' => 1, 'nama_partai' => 'Partai Kompetitor'],
                ],
            ])
            ->assertRedirect(route('admin.setup.index'))
            ->assertSessionHasErrors('partais');

        $this->assertDatabaseMissing('rekap_partais', [
            'jenis' => 'dpr_ri',
            'nomor_urut' => 1,
            'nama_partai' => 'Partai Kompetitor',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.setup.partai.store'), [
                'jenis' => 'dpr_ri',
                'partais' => [
                    ['nomor_urut' => 11, 'nama_partai' => 'Partai Garuda'],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rekap_partais', [
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);
    }

    public function test_admin_cannot_add_caleg_to_non_garuda_party(): void
    {
        $competitor = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 1,
            'nama_partai' => 'Partai Kompetitor',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.setup.caleg.store', $competitor), [
                'nomor_urut' => 1,
                'nama_caleg' => 'Caleg Kompetitor',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('rekap_calegs', [
            'partai_id' => $competitor->id,
            'nama_caleg' => 'Caleg Kompetitor',
        ]);
    }

    public function test_rekap_form_and_store_only_use_garuda_party(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);
        $competitor = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 1,
            'nama_partai' => 'Partai Kompetitor',
        ]);

        $response = $this->actingAs($this->saksiA)->get(route('rekap.form', 'dpr_ri'));

        $response->assertOk();
        $response->assertSee('Partai Garuda');
        $response->assertDontSee('Partai Kompetitor');
        $response->assertDontSee('Data Pemilih');
        $response->assertDontSee('Surat suara');
        $response->assertDontSee('Disabilitas');
        $response->assertDontSee('Suara Tidak Sah');

        $this->actingAs($this->saksiA)
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $competitor->id => 99,
                ],
            ])
            ->assertForbidden();

        $this->actingAs($this->saksiA)
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 99,
                ],
            ])
            ->assertRedirect(route('rekap.index'));

        $this->assertDatabaseHas('rekap_partai_suaras', [
            'partai_id' => $garuda->id,
            'suara' => 99,
        ]);
        $this->assertDatabaseHas('rekap_headers', [
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'dpt_lk' => 0,
            'dpt_pr' => 0,
            'suara_tidak_sah' => 0,
        ]);
        $this->assertDatabaseMissing('rekap_partai_suaras', [
            'partai_id' => $competitor->id,
        ]);
    }

    public function test_garuda_rekap_sheet_export_omits_legacy_kpu_fields(): void
    {
        [$master, $rekaps] = $this->createGarudaRekapForExport();

        $export = new \App\Exports\RekapSheetExport(
            'dpr_ri',
            'DPR RI',
            $rekaps,
            $master,
            collect([$this->tpsA, $this->tpsB]),
            'pps',
            'Desa A'
        );

        $content = $this->flattenExportRows($export->array());

        $this->assertStringContainsString('Partai Garuda', $content);
        $this->assertStringContainsString('Caleg Garuda', $content);
        $this->assertStringContainsString('Total Suara Garuda', $content);
        $this->assertStringContainsString('Status', $content);
        $this->assertStringContainsString('Final', $content);
        $this->assertStringContainsString('Kosong', $content);
        $this->assertStringNotContainsString('DPT', $content);
        $this->assertStringNotContainsString('Pengguna Hak Pilih', $content);
        $this->assertStringNotContainsString('Surat Suara', $content);
        $this->assertStringNotContainsString('Disabilitas', $content);
        $this->assertStringNotContainsString('Tidak Sah', $content);
    }

    public function test_garuda_total_export_omits_legacy_kpu_fields(): void
    {
        [$master, $rekaps] = $this->createGarudaRekapForExport();

        $export = new \App\Exports\RekapTotalSheetExport(
            'dpr_ri',
            'Rekap_DPR RI',
            $rekaps,
            $master['dpr_ri'],
            collect([$this->desaA, $this->desaB]),
            'ppk',
            'Kecamatan A'
        );

        $content = $this->flattenExportRows($export->array());

        $this->assertStringContainsString('Partai Garuda', $content);
        $this->assertStringContainsString('Caleg Garuda', $content);
        $this->assertStringContainsString('Total Suara Garuda', $content);
        $this->assertStringContainsString('1/1 final, 1 masuk', $content);
        $this->assertStringNotContainsString('DPT', $content);
        $this->assertStringNotContainsString('Pengguna Hak Pilih', $content);
        $this->assertStringNotContainsString('Surat Suara', $content);
        $this->assertStringNotContainsString('Disabilitas', $content);
        $this->assertStringNotContainsString('Tidak Sah', $content);
    }

    public function test_admin_chart_defaults_to_garuda_total_and_calegs(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);
        $competitor = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 1,
            'nama_partai' => 'Partai Kompetitor',
        ]);
        $garudaCaleg = RekapCaleg::create([
            'partai_id' => $garuda->id,
            'nomor_urut' => 1,
            'nama_caleg' => 'Caleg Garuda',
        ]);
        $competitorCaleg = RekapCaleg::create([
            'partai_id' => $competitor->id,
            'nomor_urut' => 1,
            'nama_caleg' => 'Caleg Kompetitor',
        ]);
        $rekap = RekapHeader::create([
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'final',
            'diinput_oleh' => $this->saksiA->id,
        ]);
        RekapPartaiSuara::create([
            'rekap_id' => $rekap->id,
            'partai_id' => $garuda->id,
            'suara' => 20,
        ]);
        RekapPartaiSuara::create([
            'rekap_id' => $rekap->id,
            'partai_id' => $competitor->id,
            'suara' => 200,
        ]);
        RekapCalegSuara::create([
            'rekap_id' => $rekap->id,
            'caleg_id' => $garudaCaleg->id,
            'suara' => 30,
        ]);
        RekapCalegSuara::create([
            'rekap_id' => $rekap->id,
            'caleg_id' => $competitorCaleg->id,
            'suara' => 300,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.rekap.chart.data', [
                'jenis' => 'dpr_ri',
                'level' => 'kabupaten',
            ]));

        $response->assertOk()
            ->assertJsonPath('labels.0', 'Total Suara Garuda')
            ->assertJsonPath('data.0.suara.0', 50)
            ->assertJsonMissing(['label' => 'Caleg Kompetitor'])
            ->assertJsonMissing(['meta' => 'Partai Kompetitor']);

        $payload = $response->json();
        $this->assertSame(['Total Suara Garuda'], $payload['labels']);
        $this->assertSame('Caleg Garuda', $payload['candidate_rank'][0]['label']);
        $this->assertSame(30, $payload['candidate_rank'][0]['suara']);
    }

    public function test_admin_can_mark_rekap_as_perlu_dicek_with_internal_note(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);

        $this->actingAs($this->admin)
            ->withSession([
                'admin_view_tps_id' => $this->tpsA->id,
                'admin_rekap_return_url' => route('admin.rekap.show', 'dpr_ri'),
            ])
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 77,
                ],
                'status_internal' => 'perlu_dicek',
                'catatan_internal' => 'Foto C1 perlu dicocokkan ulang.',
            ])
            ->assertRedirect(route('admin.rekap.show', 'dpr_ri'));

        $this->assertDatabaseHas('rekap_headers', [
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'perlu_dicek',
            'catatan_internal' => 'Foto C1 perlu dicocokkan ulang.',
        ]);
    }

    public function test_saksi_cannot_set_internal_review_status(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);

        $this->actingAs($this->saksiA)
            ->from(route('rekap.form', 'dpr_ri'))
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 77,
                ],
                'status_internal' => 'perlu_dicek',
            ])
            ->assertRedirect(route('rekap.form', 'dpr_ri'))
            ->assertSessionHasErrors('status_internal');

        $this->assertDatabaseMissing('rekap_headers', [
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'perlu_dicek',
        ]);
    }

    public function test_kordes_can_input_and_finalize_tps_rekap_inside_own_desa(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);

        $this->actingAs($this->kordesA)
            ->get(route('pps.view-tps', $this->tpsA))
            ->assertRedirect(route('dashboard.kpps'));

        $this->actingAs($this->kordesA)
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 88,
                ],
                'finalisasi' => '1',
            ])
            ->assertRedirect(route('rekap.index'));

        $this->assertDatabaseHas('rekap_headers', [
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'final',
            'diinput_oleh' => $this->kordesA->id,
        ]);
        $this->assertDatabaseHas('rekap_partai_suaras', [
            'partai_id' => $garuda->id,
            'suara' => 88,
        ]);
    }

    public function test_korcam_can_input_tps_rekap_inside_own_kecamatan(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);

        $this->actingAs($this->korcamA)
            ->get(route('ppk.view-pps', $this->desaA))
            ->assertRedirect(route('dashboard.pps'));

        $this->actingAs($this->korcamA)
            ->get(route('pps.view-tps', $this->tpsA))
            ->assertRedirect(route('dashboard.kpps'));

        $this->actingAs($this->korcamA)
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 99,
                ],
            ])
            ->assertRedirect(route('rekap.index'));

        $this->assertDatabaseHas('rekap_headers', [
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'draft',
            'diinput_oleh' => $this->korcamA->id,
        ]);
        $this->assertDatabaseHas('rekap_partai_suaras', [
            'partai_id' => $garuda->id,
            'suara' => 99,
        ]);
    }

    public function test_kordes_and_korcam_cannot_input_tps_rekap_outside_scope(): void
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);

        $this->actingAs($this->kordesA)
            ->withSession(['admin_view_tps_id' => $this->tpsB->id])
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 77,
                ],
            ])
            ->assertForbidden();

        $this->actingAs($this->korcamA)
            ->withSession(['admin_view_tps_id' => $this->tpsB->id])
            ->post(route('rekap.store', 'dpr_ri'), [
                'suara_partai' => [
                    $garuda->id => 66,
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('rekap_headers', [
            'tps_id' => $this->tpsB->id,
            'jenis' => 'dpr_ri',
        ]);
    }

    public function test_admin_can_download_missing_and_review_tps_exports(): void
    {
        RekapHeader::create([
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'perlu_dicek',
            'catatan_internal' => 'C1 perlu dicocokkan.',
            'diinput_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.rekap.export.missing-tps'))
            ->assertOk()
            ->assertDownload('TPS_Belum_Masuk.xlsx');

        $this->actingAs($this->admin)
            ->get(route('admin.rekap.export.review-tps'))
            ->assertOk()
            ->assertDownload('TPS_Perlu_Dicek.xlsx');
    }

    private function user(string $role, array $extra = []): User
    {
        $defaults = [
            'name' => ucfirst($role),
            'username' => $role.'_'.str()->random(6),
            'password' => Hash::make('password'),
            'role' => $role,
        ];

        return User::create(array_merge($defaults, $extra));
    }

    private function createGarudaRekapForExport(): array
    {
        $garuda = RekapPartai::create([
            'jenis' => 'dpr_ri',
            'nomor_urut' => 11,
            'nama_partai' => 'Partai Garuda',
        ]);
        $caleg = RekapCaleg::create([
            'partai_id' => $garuda->id,
            'nomor_urut' => 1,
            'nama_caleg' => 'Caleg Garuda',
        ]);
        $rekap = RekapHeader::create([
            'tps_id' => $this->tpsA->id,
            'jenis' => 'dpr_ri',
            'status' => 'final',
            'dpt_lk' => 10,
            'dpt_pr' => 11,
            'pengguna_dpt_lk' => 8,
            'pengguna_dpt_pr' => 9,
            'ss_diterima' => 30,
            'disabilitas_lk' => 1,
            'suara_tidak_sah' => 2,
            'diinput_oleh' => $this->saksiA->id,
        ]);
        RekapPartaiSuara::create([
            'rekap_id' => $rekap->id,
            'partai_id' => $garuda->id,
            'suara' => 20,
        ]);
        RekapCalegSuara::create([
            'rekap_id' => $rekap->id,
            'caleg_id' => $caleg->id,
            'suara' => 15,
        ]);

        return [
            ['dpr_ri' => ['partais' => RekapPartai::with('calegs')->whereKey($garuda->id)->get()]],
            RekapHeader::with(['partaiSuaras', 'calegSuaras'])->whereKey($rekap->id)->get(),
        ];
    }

    private function flattenExportRows(array $rows): string
    {
        return collect($rows)
            ->flatten()
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->implode(' | ');
    }
}
