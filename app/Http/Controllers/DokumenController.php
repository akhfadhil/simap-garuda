<?php
namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\Tps;
use App\Models\Desa;
use App\Models\Kecamatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DokumenController extends Controller
{
    // Menampilkan form upload dokumen TPS.
    public function uploadForm()
    {
        $tps = $this->activeTps();
        $isAdminView = Auth::user()->role !== 'kpps';

        $uploaded = Dokumen::where('tps_id', $tps->id)
            ->get()
            ->keyBy('jenis');

        return view('dokumen.upload', compact('tps', 'uploaded', 'isAdminView'));
    }

    // Menyimpan atau mengganti dokumen TPS.
    public function store(Request $request)
    {
        $user = Auth::user();
        abort_if($user->role !== 'kpps', 403, 'Akses ditolak.');

        $tps = $this->activeTps();

        $request->validate([
            'jenis' => 'required|in:' . implode(',', array_keys(Dokumen::JENIS)),
            'file'  => 'required|file|mimes:pdf|max:10240',
        ]);
        abort_if(!in_array(strtolower($request->jenis), \App\Models\PemiluSetting::aktif()), 403, 'Jenis pemilu ini tidak aktif.');

        $existing = Dokumen::where('tps_id', $tps->id)
            ->where('jenis', $request->jenis)
            ->first();

        if ($existing) {
            Storage::delete($existing->file_path);
            $existing->delete();
        }

        $kecFolder  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tps->desa->kecamatan->nama);
        $desaFolder = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tps->desa->nama);
        $tpsFolder  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $tps->nama);

        $file = $request->file('file');
        $path = $file->storeAs(
            "documents/{$kecFolder}/desa/{$desaFolder}/{$tpsFolder}",
            strtolower($request->jenis) . '.pdf'
        );

        Dokumen::create([
            'tps_id'      => $tps->id,
            'kecamatan_id'=> null,
            'uploaded_by' => $user->id,
            'jenis'       => $request->jenis,
            'level'       => 'tps',
            'status'      => 'menunggu_verifikasi',
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
        ]);

        return back()->with('success', Dokumen::JENIS[$request->jenis] . ' berhasil diupload.');
    }
    
    // Menampilkan dokumen TPS untuk diverifikasi PPS.
    public function indexPps(Request $request)
    {
        $desa = $this->activeDesa();
        $isAdminView = Auth::user()->role !== 'pps';

        $tpsOptions = Tps::where('desa_id', $desa->id)->orderBy('nama')->get();
        $selectedTpsId = $request->filled('tps_id') && $tpsOptions->contains('id', (int) $request->tps_id)
            ? (int) $request->tps_id
            : null;

        $tpsList = $selectedTpsId
            ? Tps::where('id', $selectedTpsId)
                ->with(['dokumens' => fn($q) => $q->with('uploader', 'verifier')])
                ->get()
            : collect();

        return view('dokumen.pps', compact('tpsList', 'tpsOptions', 'selectedTpsId', 'desa', 'isAdminView'));
    }

    // Memverifikasi atau menolak dokumen TPS oleh PPS.
    public function verifikasi(Request $request, Dokumen $dokumen)
    {
        $user = Auth::user();
        abort_if($user->role !== 'pps', 403, 'Akses ditolak.');

        $desa = $this->activeDesa();

        $tps = Tps::findOrFail($dokumen->tps_id);
        abort_if($tps->desa_id !== $desa->id, 403);

        $aksi = $request->input('aksi', 'terverifikasi');

        if ($aksi === 'ditolak') {
            $request->validate(['komentar' => 'required|string|max:500']);
            $dokumen->update([
                'status'      => 'ditolak',
                'komentar'    => $request->komentar,
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);
            return back()->with('success', 'Dokumen berhasil ditolak.');
        }

        $dokumen->update([
            'status'      => 'terverifikasi',
            'komentar'    => null,
            'verified_by' => $user->id,
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Dokumen berhasil diverifikasi.');
    }

    // Menampilkan rekap dokumen TPS untuk PPK.
    public function indexPpk(Request $request)
    {
        $user = Auth::user();

        if ($user->role === 'admin' && session('admin_view_kecamatan_id')) {
            $kecamatanId = session('admin_view_kecamatan_id');
            $isAdminView = true;
        } else {
            abort_if(!$user->kecamatan_id, 403, 'Akun belum di-assign ke Kecamatan.');
            $kecamatanId = $user->kecamatan_id;
            $isAdminView = false;
        }

        $kecamatan = \App\Models\Kecamatan::findOrFail($kecamatanId);
        $desaIds   = \App\Models\Desa::where('kecamatan_id', $kecamatanId)->pluck('id');

        $desas = \App\Models\Desa::where('kecamatan_id', $kecamatanId)->get();
        $selectedDesaId = $request->filled('desa_id') && $desas->contains('id', (int) $request->desa_id)
            ? (int) $request->desa_id
            : null;

        $tpsList = $selectedDesaId
            ? Tps::where('desa_id', $selectedDesaId)
                ->with(['desa', 'dokumens.uploader', 'dokumens.verifier'])
                ->get()
            : collect();

        return view('dokumen.ppk', compact('tpsList', 'desas', 'kecamatan', 'isAdminView'));
    }

    // Menampilkan form upload dokumen kecamatan.
    public function uploadFormPpk()
    {
        $kecamatan = $this->activePpkKecamatan();

        $uploaded = Dokumen::where('kecamatan_id', $kecamatan->id)
            ->where('level', 'kecamatan')
            ->get()
            ->keyBy('jenis');

        return view('dokumen.upload_ppk', compact('kecamatan', 'uploaded'));
    }

    // Menyimpan atau mengganti dokumen kecamatan.
    public function storePpk(Request $request)
    {
        $user = Auth::user();
        $kecamatan = $this->activePpkKecamatan();

        $request->validate([
            'jenis' => 'required|in:' . implode(',', array_keys(Dokumen::JENIS)),
            'file'  => 'required|file|mimes:pdf|max:10240',
        ]);
        abort_if(!in_array(strtolower($request->jenis), \App\Models\PemiluSetting::aktif()), 403, 'Jenis pemilu ini tidak aktif.');

        $existing = Dokumen::where('kecamatan_id', $kecamatan->id)
            ->where('level', 'kecamatan')
            ->where('jenis', $request->jenis)
            ->first();

        if ($existing) {
            Storage::delete($existing->file_path);
            $existing->delete();
        }

        $kecFolder = preg_replace('/[^A-Za-z0-9_\-]/', '_', $kecamatan->nama);

        $file = $request->file('file');
        $path = $file->storeAs(
            "documents/{$kecFolder}/d_hasil",
            strtolower($request->jenis) . '.pdf'
        );

        Dokumen::create([
            'kecamatan_id' => $kecamatan->id,
            'tps_id'       => null,
            'uploaded_by'  => $user->id,
            'jenis'        => $request->jenis,
            'level'        => 'kecamatan',
            'status'       => 'menunggu_verifikasi',
            'file_path'    => $path,
            'file_name'    => $file->getClientOriginalName(),
            'file_size'    => $file->getSize(),
        ]);

        return back()->with('success', Dokumen::JENIS[$request->jenis] . ' berhasil diupload.');
    }
    
    // Menampilkan rekap dokumen seluruh wilayah untuk admin.
    public function indexAdmin(Request $request)
    {
        $kecamatans = Kecamatan::all();

        $desaIds = $request->kecamatan_id
            ? Desa::where('kecamatan_id', $request->kecamatan_id)->pluck('id')
            : null;

        $desas = $request->kecamatan_id
            ? Desa::where('kecamatan_id', $request->kecamatan_id)->get()
            : collect();
        $selectedKecamatanId = $request->filled('kecamatan_id') && $kecamatans->contains('id', (int) $request->kecamatan_id)
            ? (int) $request->kecamatan_id
            : null;
        $selectedDesaId = $request->filled('desa_id') && $desas->contains('id', (int) $request->desa_id)
            ? (int) $request->desa_id
            : null;

        // Dokumen TPS hanya dimuat setelah desa dipilih.
        $tpsList = $selectedDesaId
            ? Tps::with(['desa.kecamatan', 'dokumens.uploader', 'dokumens.verifier'])
                ->where('desa_id', $selectedDesaId)
                ->get()
            : collect();

        // Dokumen Kecamatan (PPK) hanya dimuat setelah kecamatan dipilih.
        $dokumenKecamatan = $selectedKecamatanId
            ? Dokumen::where('level', 'kecamatan')
                ->with(['kecamatan', 'uploader', 'verifier'])
                ->where('kecamatan_id', $selectedKecamatanId)
                ->get()
                ->groupBy('kecamatan_id')
            : collect();

        return view('dokumen.admin', compact('tpsList', 'kecamatans', 'desas', 'dokumenKecamatan', 'selectedKecamatanId', 'selectedDesaId'));
    }

    // Memverifikasi atau menolak dokumen oleh admin.
    public function verifikasiAdmin(Request $request, Dokumen $dokumen)
    {
        abort_if(Auth::user()->role !== 'admin', 403);

        $aksi = $request->input('aksi', 'terverifikasi');

        if ($aksi === 'ditolak') {
            $request->validate(['komentar' => 'required|string|max:500']);
            $dokumen->update([
                'status'      => 'ditolak',
                'komentar'    => $request->komentar,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ]);
            return back()->with('success', 'Dokumen berhasil ditolak.');
        }

        $dokumen->update([
            'status'      => 'terverifikasi',
            'komentar'    => null,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return back()->with('success', 'Dokumen berhasil diverifikasi.');
    }

    // Menampilkan preview PDF jika user berhak akses.
    public function preview(Dokumen $dokumen)
    {
        $this->authorizeAccess($dokumen);

        if ($dokumen->is_archived) {
            return response()->view('dokumen.archived', [
                'dokumen'   => $dokumen,
                'isAdmin'   => Auth::user()->role === 'admin',
            ], 200);
        }

        abort_if(!Storage::exists($dokumen->file_path), 404, 'File tidak ditemukan.');

        $path = Storage::path($dokumen->file_path);
        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $dokumen->file_name . '"',
        ]);
    }

    // Mengunduh PDF jika user berhak akses.
    public function download(Dokumen $dokumen)
    {
        $this->authorizeAccess($dokumen);

        if ($dokumen->is_archived) {
            return back()->with('error', 'File ini telah diarsipkan dan tidak dapat diunduh. Hubungi admin untuk restore.');
        }

        abort_if(!Storage::exists($dokumen->file_path), 404, 'File tidak ditemukan.');

        return Storage::download($dokumen->file_path, $dokumen->file_name);
    }

    // Mengembalikan dokumen dari folder backup.
    public function restore(Dokumen $dokumen)
    {
        abort_if(Auth::user()->role !== 'admin', 403);
        abort_if(!$dokumen->is_archived, 400, 'Dokumen ini tidak dalam status diarsipkan.');

        $backupDir  = config('filesystems.backup_path', storage_path('app/backup'));
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $dokumen->file_path;

        if (!file_exists($backupPath)) {
            return back()->with('error', 'File backup tidak ditemukan di server. Hubungi administrator.');
        }

        $storageDir = dirname(Storage::path($dokumen->file_path));
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }

        if (copy($backupPath, Storage::path($dokumen->file_path))) {
            unlink($backupPath);
            $dokumen->update(['is_archived' => false, 'archived_at' => null]);
            return back()->with('success', 'Dokumen berhasil di-restore dan siap diakses kembali.');
        }

        return back()->with('error', 'Gagal melakukan restore. Silakan coba lagi.');
    }

    // Mengecek hak akses user terhadap dokumen.
    private function authorizeAccess(Dokumen $dokumen): void
    {
        $user = Auth::user();

        if ($dokumen->level === 'kecamatan') {
            $allowed = match($user->role) {
                'admin', 'komisioner' => true,
                'ppk'   => $dokumen->kecamatan_id === $user->kecamatan_id,
                default => false,
            };
        } else {
            $tps = Tps::with('desa')->findOrFail($dokumen->tps_id);
            $allowed = match($user->role) {
                'admin', 'komisioner' => true,
                'ppk'   => \App\Models\Desa::where('kecamatan_id', $user->kecamatan_id)
                                ->where('id', $tps->desa_id)->exists(),
                'pps'   => $tps->desa_id === $user->desa_id,
                'kpps'  => $tps->id === $user->tps_id,
                default => false,
            };
        }

        abort_if(!$allowed, 403);
    }

    private function activePpkKecamatan(): Kecamatan
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            abort_if(!session('admin_view_kecamatan_id'), 403, 'Pilih kecamatan yang ingin dilihat.');
            return Kecamatan::findOrFail(session('admin_view_kecamatan_id'));
        }

        abort_if(!$user->kecamatan_id, 403, 'Akun belum di-assign ke Kecamatan.');

        return Kecamatan::findOrFail($user->kecamatan_id);
    }

    private function activeDesa(): Desa
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            abort_if(!session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            return Desa::with('kecamatan')->findOrFail(session('admin_view_desa_id'));
        }

        if ($user->role === 'ppk') {
            abort_if(!session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            $desa = Desa::with('kecamatan')->findOrFail(session('admin_view_desa_id'));
            abort_if($desa->kecamatan_id !== $user->kecamatan_id, 403, 'Akses ditolak.');

            return $desa;
        }

        abort_if(!$user->desa_id, 403, 'Akun belum di-assign ke Desa.');

        return Desa::with('kecamatan')->findOrFail($user->desa_id);
    }

    private function activeTps(): Tps
    {
        $user = Auth::user();

        if (in_array($user->role, ['admin', 'ppk', 'pps'], true)) {
            abort_if(!session('admin_view_tps_id'), 403, 'Pilih TPS yang ingin dilihat.');
            $tps = Tps::with('desa.kecamatan')->findOrFail(session('admin_view_tps_id'));

            $allowed = match ($user->role) {
                'admin' => true,
                'ppk' => $tps->desa?->kecamatan_id === $user->kecamatan_id,
                'pps' => $tps->desa_id === $user->desa_id,
                default => false,
            };

            abort_if(!$allowed, 403, 'Akses ditolak.');

            return $tps;
        }

        abort_if(!$user->tps_id, 403, 'Akun belum di-assign ke TPS.');

        return Tps::with('desa.kecamatan')->findOrFail($user->tps_id);
    }
}
