<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        // =====================
        // KECAMATAN
        // =====================
        $kecamatans = [
            ['Bangorejo'],
            ['Banyuwangi'],
            ['Blimbingsari'],
            ['Cluring'],
            ['Gambiran'],
            ['Genteng'],
            ['Giri'],
            ['Glagah'],
            ['Glenmore'],
            ['Kabat'],
            ['Kalibaru'],
            ['Kalipuro'],
            ['Licin'],
            ['Muncar'],
            ['Pesanggaran'],
            ['Purwoharjo'],
            ['Rogojampi'],
            ['Sempu'],
            ['Siliragung'],
            ['Singojuruh'],
            ['Songgon'],
            ['Srono'],
            ['Tegaldlimo'],
            ['Tegalsari'],
            ['Wongsorejo'],
        ];

        foreach ($kecamatans as $k) {
            $exists = DB::table('kecamatans')->where('nama', $k[0])->exists();
            if (!$exists) {
                DB::table('kecamatans')->insert([
                    'nama'       => $k[0],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // =====================
        // DESA
        // =====================
        $desaData = [
            'Bangorejo' => ['Bangorejo', 'Kebondalem', 'Ringintelu', 'Sambimulyo', 'Sambirejo', 'Sukorejo', 'Temurejo'],
            'Banyuwangi' => ['Kampungmandar', 'Kampungmelayu', 'Karangrejo', 'Kebalenan', 'Kepatihan', 'Kertosari', 'Lateng', 'Pakis', 'Panderejo', 'Penganjuran', 'Pengantigan', 'Singonegaran', 'Singotrunan', 'Sobo', 'Sumberrejo', 'Tamanbaru', 'Temenggungan', 'Tukangkayu'],
            'Blimbingsari' => ['Badean', 'Blimbingsari', 'Bomo', 'Gintangan', 'Kaligung', 'Kaotan', 'Karangrejo', 'Patoman', 'Sukojati', 'Watukebo'],
            'Cluring' => ['Benculuk', 'Cluring', 'Kaliploso', 'Plampangrejo', 'Sarimulyo', 'Sembulung', 'Sraten', 'Tamanagung', 'Tampo'],
            'Gambiran' => ['Gambiran', 'Jajag', 'Purwodadi', 'Wringinagung', 'Wringinrejo', 'Yosomulyo'],
            'Genteng' => ['Genteng Kulon', 'Genteng Wetan', 'Kaligondo', 'Kembiritan', 'Setail'],
            'Giri' => ['Boyolangu', 'Giri', 'Grogol', 'Jambesari', 'Mojopanggung', 'Penataban'],
            'Glagah' => ['Bakungan', 'Banjarsari', 'Glagah', 'Kampunganyar', 'Kemiren', 'Kenjo', 'Olehsari', 'Paspan', 'Rejosari', 'Tamansuruh'],
            'Glenmore' => ['Bumiharjo', 'Karangharjo', 'Margomulyo', 'Sepanjang', 'Sumbergondo', 'Tegalharjo', 'Tulungrejo'],
            'Kabat' => ['Bareng', 'Benelan Lor', 'Bunder', 'Dadapan', 'Gombolirang', 'Kabat', 'Kalirejo', 'Kedayunan', 'Labanasem', 'Macan Putih', 'Pakistaji', 'Pendarungan', 'Pondoknongko', 'Tambong'],
            'Kalibaru' => ['Banyuanyar', 'Kajarharjo', 'Kalibarukulon', 'Kalibarumanis', 'Kalibaruwetan', 'Kebonrejo'],
            'Kalipuro' => ['Bulusan', 'Bulusari', 'Gombengsari', 'Kalipuro', 'Kelir', 'Ketapang', 'Klatak', 'Pesucen', 'Telemung'],
            'Licin' => ['Banjar', 'Gumuk', 'Jelun', 'Kluncing', 'Licin', 'Pakel', 'Segobang', 'Tamansari'],
            'Muncar' => ['Blambangan', 'Kedungrejo', 'Kedungringin', 'Kumendung', 'Sumberberas', 'Sumbersewu', 'Tambakrejo', 'Tapanrejo', 'Tembokrejo', 'Wringinputih'],
            'Pesanggaran' => ['Kandangan', 'Pesanggaran', 'Sarongan', 'Sumberagung', 'Sumbermulyo'],
            'Purwoharjo' => ['Bulurejo', 'Glagahagung', 'Grajagan', 'Karetan', 'Kradenan', 'Purwoharjo', 'Sidorejo', 'Sumberasri'],
            'Rogojampi' => ['Aliyan', 'Bubuk', 'Gitik', 'Gladag', 'Karangbendo', 'Kedaleman', 'Lemahbangdewo', 'Mangir', 'Pengatigan', 'Rogojampi'],
            'Sempu' => ['Gendoh', 'Jambewangi', 'Karangsari', 'Sempu', 'Tegalarum', 'Temuasri', 'Temuguruh'],
            'Siliragung' => ['Barurejo', 'Buluagung', 'Kesilir', 'Seneporejo', 'Siliragung'],
            'Singojuruh' => ['Alasmalang', 'Benelan Kidul', 'Cantuk', 'Gambor', 'Gumirih', 'Kemiri', 'Lemahbangkulon', 'Padang', 'Singojuruh', 'Singolatren', 'Sumberbaru'],
            'Songgon' => ['Balak', 'Bangunsari', 'Bayu', 'Bedewang', 'Parangharjo', 'Songgon', 'Sragi', 'Sumberarum', 'Sumberbulu'],
            'Srono' => ['Bagorejo', 'Kebaman', 'Kepundungan', 'Parijatah Kulon', 'Parijatah Wetan', 'Rejoagung', 'Sukomaju', 'Sukonatar', 'Sumbersari', 'Wonosobo'],
            'Tegaldlimo' => ['Kalipait', 'Kedungasri', 'Kedunggebang', 'Kedungwungu', 'Kendalrejo', 'Purwoagung', 'Purwoasri', 'Tegaldlimo', 'Wringinpitu'],
            'Tegalsari' => ['Dasri', 'Karangdoro', 'Karangmulyo', 'Tamansari', 'Tegalrejo', 'Tegalsari'],
            'Wongsorejo' => ['Alasbuluh', 'Alasrejo', 'Bajulmati', 'Bangsring', 'Bengkak', 'Bimorejo', 'Sidodadi', 'Sidowangi', 'Sumberanyar', 'Sumberkencono', 'Watukebo', 'Wongsorejo'],
        ];

        foreach ($desaData as $kecNama => $desas) {
            $kec = DB::table('kecamatans')->where('nama', $kecNama)->first();
            if (!$kec) continue;
            foreach ($desas as $desaNama) {
                $exists = DB::table('desas')
                    ->where('kecamatan_id', $kec->id)
                    ->where('nama', $desaNama)
                    ->exists();
                if (!$exists) {
                    DB::table('desas')->insert([
                        'nama'          => $desaNama,
                        'kecamatan_id'  => $kec->id,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }
    }
}
