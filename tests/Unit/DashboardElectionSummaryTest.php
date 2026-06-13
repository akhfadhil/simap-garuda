<?php

namespace Tests\Unit;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\PemiluSetting;
use App\Models\RekapHeader;
use App\Models\RekapPpwpCalon;
use App\Models\RekapPpwpSuara;
use App\Models\Tps;
use App\Models\User;
use App\Services\DashboardElectionSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardElectionSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_rows_include_percentage_from_entered_votes(): void
    {
        Cache::flush();

        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan A']);
        $desa = Desa::create(['nama' => 'Desa A', 'kecamatan_id' => $kecamatan->id]);
        $tps = Tps::create(['nama' => 'TPS 1', 'desa_id' => $desa->id]);
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin_test',
            'role' => 'admin',
            'password' => 'password',
        ]);

        PemiluSetting::create(['jenis' => 'ppwp', 'is_active' => true]);
        $calonA = RekapPpwpCalon::create(['nomor_urut' => 1, 'nama_paslon' => 'Paslon A']);
        $calonB = RekapPpwpCalon::create(['nomor_urut' => 2, 'nama_paslon' => 'Paslon B']);
        $rekap = RekapHeader::create([
            'tps_id' => $tps->id,
            'jenis' => 'ppwp',
            'status' => 'final',
            'diinput_oleh' => $admin->id,
        ]);

        RekapPpwpSuara::create(['rekap_id' => $rekap->id, 'calon_id' => $calonA->id, 'suara' => 30]);
        RekapPpwpSuara::create(['rekap_id' => $rekap->id, 'calon_id' => $calonB->id, 'suara' => 70]);

        $summary = app(DashboardElectionSummary::class)->forUser($admin);
        $section = $summary['sections'][0];
        $rows = collect($section['rows'])->keyBy('label');

        $this->assertSame(100, $section['total_suara']);
        $this->assertSame(70, $rows['Paslon B']['suara']);
        $this->assertEquals(70.0, $rows['Paslon B']['persentase']);
        $this->assertEquals(30.0, $rows['Paslon A']['persentase']);
    }
}
