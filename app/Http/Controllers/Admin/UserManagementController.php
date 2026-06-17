<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Tps;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    // Menampilkan daftar user setelah filter dipilih.
    public function index()
    {
        $usersLoaded = $this->hasUserFilter(request());
        $users = $usersLoaded
            ? $this->filteredUsers(request())
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString()
            : User::whereRaw('1 = 0')->paginate(15)->withQueryString();

        $kecamatans = Kecamatan::orderBy('nama')->get();
        $desas      = Desa::orderBy('nama')->get(['id', 'nama', 'kecamatan_id']);
        $tpsList    = Tps::orderBy('nama')->get(['id', 'nama', 'desa_id']);

        return view('admin.users.index', compact('users', 'usersLoaded', 'kecamatans', 'desas', 'tpsList'));
    }

    // Mengekspor daftar user sesuai filter ke CSV.
    public function export(Request $request)
    {
        if (!$this->hasUserFilter($request)) {
            return back()->with('error', 'Pilih role/kecamatan/desa dulu sebelum export.');
        }

        $filename = 'daftar-user-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Nama', 'Username', 'Password Awal Default', 'Role', 'Kecamatan', 'Desa', 'TPS']);

            $this->filteredUsers($request)
                ->orderBy('role')
                ->orderBy('name')
                ->chunk(200, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->name,
                            $user->username,
                            $user->username,
                            strtoupper($user->role),
                            $this->userKecamatanName($user),
                            $this->userDesaName($user),
                            $user->tps?->nama ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    // Menyimpan user baru dari form admin.
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'username'     => 'required|string|unique:users|max:50',
            'password'     => 'nullable|string|min:6',
            'role'         => 'required|in:admin,ppk,pps,kpps',
            'kecamatan_id' => 'required_if:role,ppk|nullable|exists:kecamatans,id',
            'desa_id'      => 'required_if:role,pps|nullable|exists:desas,id',
            'tps_id'       => 'required_if:role,kpps|nullable|exists:tps,id',
        ]);

        User::create([
            'name'         => $request->name,
            'username'     => $request->username,
            'password'     => Hash::make($request->filled('password') ? $request->password : $request->username),
            'role'         => $request->role,
            'kecamatan_id' => $request->role === 'ppk'  ? $request->kecamatan_id : null,
            'desa_id'      => $request->role === 'pps'  ? $request->desa_id      : null,
            'tps_id'       => $request->role === 'kpps' ? $request->tps_id       : null,
        ]);

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    // Memperbarui data user yang sudah ada.
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'username'     => 'required|string|max:50|unique:users,username,' . $user->id,
            'password'     => 'nullable|string|min:6',
            'kecamatan_id' => 'nullable|exists:kecamatans,id',
            'desa_id'      => 'nullable|exists:desas,id',
            'tps_id'       => 'nullable|exists:tps,id',
        ]);

        $data = [
            'name'         => $request->name,
            'username'     => $request->username,
            'kecamatan_id' => $user->role === 'ppk'  ? $request->kecamatan_id : null,
            'desa_id'      => $user->role === 'pps'  ? $request->desa_id      : null,
            'tps_id'       => $user->role === 'kpps' ? $request->tps_id       : null,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        return back()->with('success', 'User berhasil diupdate.');
    }

    // Menghapus user.
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return back()->with('error', 'Akun yang sedang dipakai tidak bisa dihapus.');
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun admin/operator.');
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }

    // Menampilkan halaman bulk input user per wilayah.
    public function bulk(Request $request)
    {
        $role = $request->input('role', 'kpps');
        if (!in_array($role, ['ppk', 'pps', 'kpps'], true)) {
            $role = 'kpps';
        }

        $selectedKecamatanId = $request->integer('kecamatan_id') ?: null;
        $selectedDesaId = $request->integer('desa_id') ?: null;

        $kecamatans = Kecamatan::orderBy('nama')->get();
        $desas = $selectedKecamatanId
            ? Desa::where('kecamatan_id', $selectedKecamatanId)->orderBy('nama')->get()
            : collect();

        $rows = match ($role) {
            'ppk' => $this->bulkPpkRows(),
            'pps' => $selectedKecamatanId ? $this->bulkPpsRows($selectedKecamatanId) : collect(),
            'kpps' => $selectedDesaId ? $this->bulkKppsRows($selectedDesaId) : collect(),
        };

        return view('admin.users.bulk', compact(
            'role',
            'kecamatans',
            'desas',
            'rows',
            'selectedKecamatanId',
            'selectedDesaId'
        ));
    }

    // Menyimpan hasil bulk input user.
    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'role' => 'required|in:ppk,pps,kpps',
            'rows' => 'required|array',
        ]);

        $role = $data['role'];
        $selectedRows = collect($data['rows'])
            ->filter(fn($row) => !empty($row['enabled']));

        if ($selectedRows->isEmpty()) {
            throw ValidationException::withMessages([
                'rows' => 'Pilih minimal satu baris user untuk disimpan.',
            ]);
        }

        $created = 0;
        $updated = 0;
        $submittedUsernames = [];

        DB::transaction(function () use ($role, $selectedRows, &$created, &$updated, &$submittedUsernames) {
            foreach ($selectedRows as $entityId => $row) {
                $entityId = (int) $entityId;
                $entity = $this->bulkEntity($role, $entityId);
                $existing = $this->existingUserForRole($role, $entityId);

                $name = trim((string) ($row['name'] ?? ''));
                $username = trim((string) ($row['username'] ?? ''));
                $password = (string) ($row['password'] ?? '');

                if ($name === '' || $username === '') {
                    throw ValidationException::withMessages([
                        'rows' => "Nama dan username wajib diisi untuk {$entity['label']}.",
                    ]);
                }

                if (!$existing && $password !== '' && strlen($password) < 6) {
                    throw ValidationException::withMessages([
                        'rows' => "Password minimal 6 karakter untuk user baru {$entity['label']}.",
                    ]);
                }

                if ($password !== '' && strlen($password) < 6) {
                    throw ValidationException::withMessages([
                        'rows' => "Password minimal 6 karakter untuk {$entity['label']}.",
                    ]);
                }

                $usernameKey = strtolower($username);
                if (isset($submittedUsernames[$usernameKey])) {
                    throw ValidationException::withMessages([
                        'rows' => "Username {$username} dobel di form.",
                    ]);
                }
                $submittedUsernames[$usernameKey] = true;

                $usernameTaken = User::where('username', $username)
                    ->when($existing, fn($query) => $query->where('id', '!=', $existing->id))
                    ->exists();

                if ($usernameTaken) {
                    throw ValidationException::withMessages([
                        'rows' => "Username {$username} sudah dipakai.",
                    ]);
                }

                $payload = [
                    'name' => $name,
                    'username' => $username,
                    'role' => $role,
                    'kecamatan_id' => $role === 'ppk' ? $entityId : null,
                    'desa_id' => $role === 'pps' ? $entityId : null,
                    'tps_id' => $role === 'kpps' ? $entityId : null,
                ];

                if ($password !== '') {
                    $payload['password'] = Hash::make($password);
                } elseif (!$existing) {
                    $payload['password'] = Hash::make($username);
                }

                if ($existing) {
                    $existing->update($payload);
                    $updated++;
                    continue;
                }

                User::create($payload);
                $created++;
            }
        });

        return redirect()
            ->route('admin.users.bulk', $request->only('role', 'kecamatan_id', 'desa_id'))
            ->with('success', "Bulk user selesai. Dibuat: {$created}, diperbarui: {$updated}.");
    }

    // Menyiapkan baris bulk user Korcam per kecamatan.
    private function bulkPpkRows()
    {
        return Kecamatan::with(['users' => fn($query) => $query->where('role', 'ppk')])
            ->orderBy('nama')
            ->get()
            ->map(fn($kecamatan) => $this->bulkRow(
                $kecamatan->id,
                $kecamatan->nama,
                'Kecamatan',
                $kecamatan->users->first(),
                'Korcam ' . $kecamatan->nama,
                $this->suggestUsername('ppk', $kecamatan->id, $kecamatan->nama)
            ));
    }

    // Menyiapkan baris bulk user Kordes per desa.
    private function bulkPpsRows(int $kecamatanId)
    {
        return Desa::with(['kecamatan', 'users' => fn($query) => $query->where('role', 'pps')])
            ->where('kecamatan_id', $kecamatanId)
            ->orderBy('nama')
            ->get()
            ->map(fn($desa) => $this->bulkRow(
                $desa->id,
                $desa->nama,
                $desa->kecamatan?->nama ?? 'Kecamatan',
                $desa->users->first(),
                'Kordes ' . $desa->nama,
                $this->suggestUsername('pps', $desa->id, $desa->nama)
            ));
    }

    // Menyiapkan baris bulk user Saksi TPS per TPS.
    private function bulkKppsRows(int $desaId)
    {
        return Tps::with(['desa.kecamatan', 'users' => fn($query) => $query->where('role', 'kpps')])
            ->where('desa_id', $desaId)
            ->orderBy('nama')
            ->get()
            ->map(fn($tps) => $this->bulkRow(
                $tps->id,
                $tps->nama,
                ($tps->desa?->nama ?? 'Desa') . ' / ' . ($tps->desa?->kecamatan?->nama ?? 'Kecamatan'),
                $tps->users->first(),
                'Saksi TPS ' . $tps->nama . ' ' . ($tps->desa?->nama ?? ''),
                $this->suggestUsername('kpps', $tps->id, $tps->nama)
            ));
    }

    // Membentuk satu baris data untuk tabel bulk.
    private function bulkRow(int $id, string $label, string $scope, ?User $user, string $nameSuggestion, string $usernameSuggestion): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'scope' => $scope,
            'user' => $user,
            'name' => $user?->name ?? $nameSuggestion,
            'username' => $user?->username ?? $usernameSuggestion,
        ];
    }

    // Mengambil entity wilayah untuk role bulk.
    private function bulkEntity(string $role, int $entityId): array
    {
        $entity = match ($role) {
            'ppk' => Kecamatan::find($entityId),
            'pps' => Desa::find($entityId),
            'kpps' => Tps::find($entityId),
        };

        if (!$entity) {
            throw ValidationException::withMessages([
                'rows' => 'Ada wilayah yang tidak ditemukan.',
            ]);
        }

        return ['label' => $entity->nama];
    }

    // Mencari user existing untuk role dan wilayah tertentu.
    private function existingUserForRole(string $role, int $entityId): ?User
    {
        return User::where('role', $role)
            ->when($role === 'ppk', fn($query) => $query->where('kecamatan_id', $entityId))
            ->when($role === 'pps', fn($query) => $query->where('desa_id', $entityId))
            ->when($role === 'kpps', fn($query) => $query->where('tps_id', $entityId))
            ->first();
    }

    // Membuat saran username berdasarkan role dan wilayah.
    private function suggestUsername(string $role, int $id, string $name): string
    {
        $slug = Str::slug($name, '_') ?: 'wilayah';
        return Str::limit("{$role}_{$id}_{$slug}", 50, '');
    }

    // Mengecek apakah user sudah memilih filter daftar pengguna.
    private function hasUserFilter(Request $request): bool
    {
        return $request->filled('role') || $request->filled('kecamatan_id') || $request->filled('desa_id');
    }

    // Membentuk query user sesuai filter.
    private function filteredUsers(Request $request)
    {
        return User::with('kecamatan', 'desa.kecamatan', 'tps.desa.kecamatan')
            ->when($request->filled('role'), fn($query) => $query->where('role', $request->role))
            ->when($request->filled('kecamatan_id'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('kecamatan_id', $request->kecamatan_id)
                        ->orWhereHas('desa', fn($desaQuery) => $desaQuery->where('kecamatan_id', $request->kecamatan_id))
                        ->orWhereHas('tps.desa', fn($desaQuery) => $desaQuery->where('kecamatan_id', $request->kecamatan_id));
                });
            })
            ->when($request->filled('desa_id'), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $query->where('desa_id', $request->desa_id)
                        ->orWhereHas('tps', fn($tpsQuery) => $tpsQuery->where('desa_id', $request->desa_id));
                });
            });
    }

    // Mengambil nama kecamatan dari scope user.
    private function userKecamatanName(User $user): string
    {
        return match ($user->role) {
            'ppk' => $user->kecamatan?->nama ?? '',
            'pps' => $user->desa?->kecamatan?->nama ?? '',
            'kpps' => $user->tps?->desa?->kecamatan?->nama ?? '',
            default => '',
        };
    }

    // Mengambil nama desa dari scope user.
    private function userDesaName(User $user): string
    {
        return match ($user->role) {
            'pps' => $user->desa?->nama ?? '',
            'kpps' => $user->tps?->desa?->nama ?? '',
            default => '',
        };
    }
}
