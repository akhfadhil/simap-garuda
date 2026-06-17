<?php

namespace Tests\Unit;

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
use App\Services\DashboardElectionSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardElectionSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_party_election_types_are_ignored_by_dashboard(): void
    {
        Cache::flush();

        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan A']);
        $desa = Desa::create(['nama' => 'Desa A', 'kecamatan_id' => $kecamatan->id]);
        $tps = Tps::create(['nama' => 'TPS 1', 'desa_id' => $desa->id]);
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin_test',
            'role' => 'admin_partai',
            'password' => 'password',
        ]);

        PemiluSetting::create(['jenis' => 'ppwp', 'is_active' => true]);
        RekapHeader::create([
            'tps_id' => $tps->id,
            'jenis' => 'ppwp',
            'status' => 'final',
            'diinput_oleh' => $admin->id,
        ]);

        $summary = app(DashboardElectionSummary::class)->forUser($admin);

        $this->assertSame([], $summary['sections']);
    }

    public function test_legislative_dashboard_only_shows_garuda_calegs(): void
    {
        Cache::flush();

        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan A']);
        $desa = Desa::create(['nama' => 'Desa A', 'kecamatan_id' => $kecamatan->id]);
        $tps = Tps::create(['nama' => 'TPS 1', 'desa_id' => $desa->id]);
        Tps::create(['nama' => 'TPS 2', 'desa_id' => $desa->id]);
        $kecamatanB = Kecamatan::create(['nama' => 'Kecamatan B']);
        $desaB = Desa::create(['nama' => 'Desa B', 'kecamatan_id' => $kecamatanB->id]);
        $tpsB = Tps::create(['nama' => 'TPS 3', 'desa_id' => $desaB->id]);
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin_garuda_test',
            'role' => 'admin_partai',
            'password' => 'password',
        ]);

        PemiluSetting::create(['jenis' => 'dpr_ri', 'is_active' => true]);
        $garuda = RekapPartai::create(['jenis' => 'dpr_ri', 'nomor_urut' => 11, 'nama_partai' => 'Partai Garuda']);
        $competitor = RekapPartai::create(['jenis' => 'dpr_ri', 'nomor_urut' => 1, 'nama_partai' => 'Partai Kompetitor']);
        $garudaCaleg = RekapCaleg::create(['partai_id' => $garuda->id, 'nomor_urut' => 1, 'nama_caleg' => 'Caleg Garuda']);
        $competitorCaleg = RekapCaleg::create(['partai_id' => $competitor->id, 'nomor_urut' => 1, 'nama_caleg' => 'Caleg Kompetitor']);
        $rekap = RekapHeader::create([
            'tps_id' => $tps->id,
            'jenis' => 'dpr_ri',
            'status' => 'final',
            'diinput_oleh' => $admin->id,
        ]);
        $rekapB = RekapHeader::create([
            'tps_id' => $tpsB->id,
            'jenis' => 'dpr_ri',
            'status' => 'perlu_dicek',
            'catatan_internal' => 'C1 perlu dicocokkan',
            'diinput_oleh' => $admin->id,
        ]);

        RekapPartaiSuara::create(['rekap_id' => $rekap->id, 'partai_id' => $garuda->id, 'suara' => 20]);
        RekapPartaiSuara::create(['rekap_id' => $rekap->id, 'partai_id' => $competitor->id, 'suara' => 200]);
        RekapCalegSuara::create(['rekap_id' => $rekap->id, 'caleg_id' => $garudaCaleg->id, 'suara' => 30]);
        RekapCalegSuara::create(['rekap_id' => $rekap->id, 'caleg_id' => $competitorCaleg->id, 'suara' => 300]);
        RekapPartaiSuara::create(['rekap_id' => $rekapB->id, 'partai_id' => $garuda->id, 'suara' => 5]);
        RekapCalegSuara::create(['rekap_id' => $rekapB->id, 'caleg_id' => $garudaCaleg->id, 'suara' => 3]);

        $summary = app(DashboardElectionSummary::class)->forUser($admin);
        $section = $summary['sections'][0];
        $overview = $summary['overview'];
        $labels = collect($section['rows'])->pluck('label')->all();

        $this->assertSame('DPR RI - '.config('party.short_name'), $section['title']);
        $this->assertContains('Caleg Garuda No. 1', $labels);
        $this->assertNotContains('Caleg Kompetitor No. 1', $labels);
        $this->assertSame(33, $section['total_suara']);
        $this->assertSame(58, $overview['total_suara_partai']);
        $this->assertSame(3, $overview['total_tps']);
        $this->assertSame(2, $overview['input_tps']);
        $this->assertSame(1, $overview['missing_tps_count']);
        $this->assertSame('TPS 2 - Desa A', $overview['missing_tps'][0]['label']);
        $this->assertSame(1, $overview['review_tps_count']);
        $this->assertSame('TPS 3 - Desa B', $overview['review_tps'][0]['label']);
        $this->assertSame('C1 perlu dicocokkan', $overview['review_tps'][0]['note']);
        $this->assertSame('Kecamatan', $overview['regions']['label']);
        $this->assertSame('Kecamatan A', $overview['regions']['strong'][0]['label']);
        $this->assertSame(50, $overview['regions']['strong'][0]['suara']);
        $this->assertSame('Kecamatan B', $overview['regions']['weak'][0]['label']);
        $this->assertSame(8, $overview['regions']['weak'][0]['suara']);
    }
}

