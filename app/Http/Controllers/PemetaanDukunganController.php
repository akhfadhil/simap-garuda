<?php

namespace App\Http\Controllers;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Pendukung;
use App\Models\Tps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PendukungExport;

class PemetaanDukunganController extends Controller
{
    private function checkAccess(Pendukung $pendukung): void
    {
        $user = Auth::user();
        if ($user->role === 'admin_partai') {
            return;
        }

        if ($user->role === 'korcam') {
            abort_if($pendukung->kecamatan_id !== $user->kecamatan_id, 403, 'Akses ditolak.');
            return;
        }

        if ($user->role === 'kordes') {
            abort_if($pendukung->desa_id !== $user->desa_id, 403, 'Akses ditolak.');
            return;
        }

        abort(403, 'Akses ditolak.');
    }

    private function getScopedQuery()
    {
        $user = Auth::user();
        $query = Pendukung::query()->with(['kecamatan', 'desa', 'tps', 'creator']);

        if ($user->role === 'korcam') {
            $query->where('kecamatan_id', $user->kecamatan_id);
        } elseif ($user->role === 'kordes') {
            $query->where('desa_id', $user->desa_id);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $this->getScopedQuery();

        // Search & Filters
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('no_hp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kecamatan_id') && $user->role === 'admin_partai') {
            $query->where('kecamatan_id', $request->input('kecamatan_id'));
        }
        if ($request->filled('desa_id') && in_array($user->role, ['admin_partai', 'korcam'], true)) {
            if ($user->role === 'korcam') {
                $query->where('desa_id', $request->input('desa_id'));
            } else {
                $query->where('desa_id', $request->input('desa_id'));
            }
        }
        if ($request->filled('tps_id')) {
            $query->where('tps_id', $request->input('tps_id'));
        }

        $pendukungs = $query->latest()->paginate(15)->withQueryString();

        // Dropdowns data
        $kecamatans = collect();
        $desas = collect();
        $tpsList = collect();

        if ($user->role === 'admin_partai') {
            $kecamatans = Kecamatan::orderBy('nama')->get();
            $desas = Desa::orderBy('nama')->get();
            $tpsList = Tps::orderBy('nama')->get();
        } elseif ($user->role === 'korcam') {
            $kecamatans = Kecamatan::where('id', $user->kecamatan_id)->get();
            $desas = Desa::where('kecamatan_id', $user->kecamatan_id)->orderBy('nama')->get();
            $tpsList = Tps::whereIn('desa_id', $desas->pluck('id'))->orderBy('nama')->get();
        } elseif ($user->role === 'kordes') {
            $desa = Desa::find($user->desa_id);
            if ($desa) {
                $kecamatans = Kecamatan::where('id', $desa->kecamatan_id)->get();
                $desas = Desa::where('id', $user->desa_id)->get();
                $tpsList = Tps::where('desa_id', $user->desa_id)->orderBy('nama')->get();
            }
        }

        return view('pemetaan-dukungan.index', compact('pendukungs', 'kecamatans', 'desas', 'tpsList'));
    }

    public function create()
    {
        $user = Auth::user();
        $kecamatans = collect();
        $desas = collect();
        $tpsList = collect();

        if ($user->role === 'admin_partai') {
            $kecamatans = Kecamatan::orderBy('nama')->get();
            $desas = Desa::orderBy('nama')->get();
            $tpsList = Tps::orderBy('nama')->get();
        } elseif ($user->role === 'korcam') {
            $kecamatans = Kecamatan::where('id', $user->kecamatan_id)->get();
            $desas = Desa::where('kecamatan_id', $user->kecamatan_id)->orderBy('nama')->get();
            $tpsList = Tps::whereIn('desa_id', $desas->pluck('id'))->orderBy('nama')->get();
        } elseif ($user->role === 'kordes') {
            $desa = Desa::find($user->desa_id);
            if ($desa) {
                $kecamatans = Kecamatan::where('id', $desa->kecamatan_id)->get();
                $desas = Desa::where('id', $user->desa_id)->get();
                $tpsList = Tps::where('desa_id', $user->desa_id)->orderBy('nama')->get();
            }
        }

        return view('pemetaan-dukungan.create', compact('kecamatans', 'desas', 'tpsList'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Rules
        $rules = [
            'nama' => 'required|string|max:255',
            'nik' => 'required|string|size:16|unique:pendukungs,nik',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'tps_id' => 'nullable|exists:tps,id',
            'ktp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'catatan' => 'nullable|string',
        ];

        if ($user->role === 'admin_partai') {
            $rules['kecamatan_id'] = 'required|exists:kecamatans,id';
            $rules['desa_id'] = 'required|exists:desas,id';
        } elseif ($user->role === 'korcam') {
            $rules['desa_id'] = 'required|exists:desas,id';
        }

        $request->validate($rules);

        $data = $request->except(['ktp']);
        $data['created_by'] = $user->id;

        // Force scopes
        if ($user->role === 'korcam') {
            $data['kecamatan_id'] = $user->kecamatan_id;
            // Guard desa_id belongs to korcam kecamatan
            $desa = Desa::findOrFail($request->input('desa_id'));
            abort_if($desa->kecamatan_id !== $user->kecamatan_id, 403, 'Wilayah tidak valid.');
        } elseif ($user->role === 'kordes') {
            $desa = Desa::findOrFail($user->desa_id);
            $data['kecamatan_id'] = $desa->kecamatan_id;
            $data['desa_id'] = $user->desa_id;
        }

        if ($request->filled('tps_id')) {
            $tps = Tps::findOrFail($request->input('tps_id'));
            abort_if($tps->desa_id !== (int)$data['desa_id'], 403, 'TPS tidak berada di wilayah desa yang dipilih.');
        }

        if ($request->hasFile('ktp')) {
            $path = $request->file('ktp')->store('private/ktp');
            $data['ktp_path'] = $path;
        }

        Pendukung::create($data);

        return redirect()->route('pemetaan-dukungan.index')->with('success', 'Data pendukung berhasil ditambahkan.');
    }

    public function edit(Pendukung $pendukung)
    {
        $this->checkAccess($pendukung);
        $user = Auth::user();

        $kecamatans = collect();
        $desas = collect();
        $tpsList = collect();

        if ($user->role === 'admin_partai') {
            $kecamatans = Kecamatan::orderBy('nama')->get();
            $desas = Desa::orderBy('nama')->get();
            $tpsList = Tps::orderBy('nama')->get();
        } elseif ($user->role === 'korcam') {
            $kecamatans = Kecamatan::where('id', $user->kecamatan_id)->get();
            $desas = Desa::where('kecamatan_id', $user->kecamatan_id)->orderBy('nama')->get();
            $tpsList = Tps::whereIn('desa_id', $desas->pluck('id'))->orderBy('nama')->get();
        } elseif ($user->role === 'kordes') {
            $desa = Desa::find($user->desa_id);
            if ($desa) {
                $kecamatans = Kecamatan::where('id', $desa->kecamatan_id)->get();
                $desas = Desa::where('id', $user->desa_id)->get();
                $tpsList = Tps::where('desa_id', $user->desa_id)->orderBy('nama')->get();
            }
        }

        return view('pemetaan-dukungan.edit', compact('pendukung', 'kecamatans', 'desas', 'tpsList'));
    }

    public function update(Request $request, Pendukung $pendukung)
    {
        $this->checkAccess($pendukung);
        $user = Auth::user();

        // Rules
        $rules = [
            'nama' => 'required|string|max:255',
            'nik' => 'required|string|size:16|unique:pendukungs,nik,' . $pendukung->id,
            'no_hp' => 'required|string|max:20',
            'alamat' => 'required|string',
            'tps_id' => 'nullable|exists:tps,id',
            'ktp' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'catatan' => 'nullable|string',
        ];

        if ($user->role === 'admin_partai') {
            $rules['kecamatan_id'] = 'required|exists:kecamatans,id';
            $rules['desa_id'] = 'required|exists:desas,id';
        } elseif ($user->role === 'korcam') {
            $rules['desa_id'] = 'required|exists:desas,id';
        }

        $request->validate($rules);

        $data = $request->except(['ktp']);

        // Force scopes
        if ($user->role === 'korcam') {
            $data['kecamatan_id'] = $user->kecamatan_id;
            $desa = Desa::findOrFail($request->input('desa_id'));
            abort_if($desa->kecamatan_id !== $user->kecamatan_id, 403, 'Wilayah tidak valid.');
        } elseif ($user->role === 'kordes') {
            $desa = Desa::findOrFail($user->desa_id);
            $data['kecamatan_id'] = $desa->kecamatan_id;
            $data['desa_id'] = $user->desa_id;
        }

        if ($request->filled('tps_id')) {
            $tps = Tps::findOrFail($request->input('tps_id'));
            abort_if($tps->desa_id !== (int)$data['desa_id'], 403, 'TPS tidak berada di wilayah desa yang dipilih.');
        }

        if ($request->hasFile('ktp')) {
            if ($pendukung->ktp_path) {
                Storage::delete($pendukung->ktp_path);
            }
            $path = $request->file('ktp')->store('private/ktp');
            $data['ktp_path'] = $path;
        }

        $pendukung->update($data);

        return redirect()->route('pemetaan-dukungan.index')->with('success', 'Data pendukung berhasil diperbarui.');
    }

    public function destroy(Pendukung $pendukung)
    {
        abort_if(Auth::user()->role !== 'admin_partai', 403, 'Hanya Admin yang dapat menghapus data.');

        if ($pendukung->ktp_path) {
            Storage::delete($pendukung->ktp_path);
        }

        $pendukung->delete();

        return redirect()->route('pemetaan-dukungan.index')->with('success', 'Data pendukung berhasil dihapus.');
    }

    public function downloadKtp(Pendukung $pendukung)
    {
        $this->checkAccess($pendukung);
        abort_if(! $pendukung->ktp_path, 404, 'File tidak ditemukan.');

        return Storage::response($pendukung->ktp_path);
    }

    public function statistik()
    {
        $user = Auth::user();
        abort_if($user->role === 'kordes', 403, 'Halaman statistik tidak tersedia untuk Kordes.');

        $totalPendukung = $this->getScopedQuery()->count();
        $sebaranWilayah = [];

        if ($user->role === 'admin_partai') {
            $sebaranWilayah = Kecamatan::withCount('pendukungs')
                ->orderBy('nama')
                ->get()
                ->map(fn($k) => ['nama' => $k->nama, 'jumlah' => $k->pendukungs_count]);
        } elseif ($user->role === 'korcam') {
            $sebaranWilayah = Desa::where('kecamatan_id', $user->kecamatan_id)
                ->withCount('pendukungs')
                ->orderBy('nama')
                ->get()
                ->map(fn($d) => ['nama' => $d->nama, 'jumlah' => $d->pendukungs_count]);
        }

        return view('pemetaan-dukungan.statistik', compact('totalPendukung', 'sebaranWilayah'));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        abort_if($user->role === 'kordes', 403, 'Akses ekspor ditolak.');

        $kecamatanId = $user->role === 'korcam' ? $user->kecamatan_id : $request->input('kecamatan_id');
        $desaId = $request->input('desa_id');
        $tpsId = $request->input('tps_id');
        $search = $request->input('search');

        return Excel::download(
            new PendukungExport($kecamatanId, $desaId, $tpsId, $search),
            'data_pendukung_garuda_' . date('Ymd_His') . '.xlsx'
        );
    }
}
