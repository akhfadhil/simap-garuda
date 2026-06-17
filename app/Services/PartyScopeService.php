<?php

namespace App\Services;

use App\Models\Desa;
use App\Models\Kecamatan;
use App\Models\Tps;
use App\Models\User;

class PartyScopeService
{
    public function canAccessKecamatan(User $user, Kecamatan $kecamatan): bool
    {
        return match ($user->role) {
            'admin_partai' => true,
            'korcam' => $user->kecamatan_id === $kecamatan->id,
            default => false,
        };
    }

    public function canAccessDesa(User $user, Desa $desa): bool
    {
        return match ($user->role) {
            'admin_partai' => true,
            'korcam' => $user->kecamatan_id === $desa->kecamatan_id,
            'kordes' => $user->desa_id === $desa->id,
            default => false,
        };
    }

    public function canAccessTps(User $user, Tps $tps): bool
    {
        return match ($user->role) {
            'admin_partai' => true,
            'korcam' => $tps->desa?->kecamatan_id === $user->kecamatan_id,
            'kordes' => $tps->desa_id === $user->desa_id,
            'saksi_tps' => $user->tps_id === $tps->id,
            default => false,
        };
    }

    public function dashboardScopeFor(User $user): array
    {
        if (session('admin_view_tps_id')) {
            $tps = Tps::with('desa.kecamatan')->find(session('admin_view_tps_id'));

            if ($tps && $this->canAccessTps($user, $tps)) {
                return $this->scopeFromTps($tps);
            }
        }

        if (session('admin_view_desa_id')) {
            $desa = Desa::with('kecamatan')->find(session('admin_view_desa_id'));

            if ($desa && $this->canAccessDesa($user, $desa)) {
                return $this->scopeFromDesa($desa);
            }
        }

        if (session('admin_view_kecamatan_id')) {
            $kecamatan = Kecamatan::find(session('admin_view_kecamatan_id'));

            if ($kecamatan && $this->canAccessKecamatan($user, $kecamatan)) {
                return $this->scopeFromKecamatan($kecamatan);
            }
        }

        if ($user->role === 'korcam') {
            return [
                'type' => 'kecamatan',
                'id' => $user->kecamatan_id,
                'label' => 'Kecamatan '.($user->kecamatan?->nama ?? '-'),
                'dapil_id' => $user->kecamatan?->dapil_id,
            ];
        }

        if ($user->role === 'kordes') {
            return [
                'type' => 'desa',
                'id' => $user->desa_id,
                'label' => 'Desa '.($user->desa?->nama ?? '-'),
                'dapil_id' => $user->desa?->kecamatan?->dapil_id,
            ];
        }

        if ($user->role === 'saksi_tps') {
            return [
                'type' => 'tps',
                'id' => $user->tps_id,
                'label' => $user->tps?->nama.' - '.($user->tps?->desa?->nama ?? '-'),
                'dapil_id' => $user->tps?->desa?->kecamatan?->dapil_id,
            ];
        }

        return [
            'type' => 'kabupaten',
            'id' => null,
            'label' => 'Kabupaten Banyuwangi',
            'dapil_id' => null,
        ];
    }

    public function activeKecamatanFor(User $user): Kecamatan
    {
        if ($user->role === 'admin_partai') {
            abort_if(! session('admin_view_kecamatan_id'), 403, 'Pilih kecamatan yang ingin dilihat.');

            return Kecamatan::findOrFail(session('admin_view_kecamatan_id'));
        }

        abort_if(! $user->kecamatan_id, 403, 'Akun belum di-assign ke Kecamatan.');

        return Kecamatan::findOrFail($user->kecamatan_id);
    }

    public function activeDesaFor(User $user): Desa
    {
        if ($user->role === 'admin_partai') {
            abort_if(! session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');

            return Desa::findOrFail(session('admin_view_desa_id'));
        }

        if ($user->role === 'korcam') {
            abort_if(! session('admin_view_desa_id'), 403, 'Pilih desa yang ingin dilihat.');
            $desa = Desa::findOrFail(session('admin_view_desa_id'));
            abort_if(! $this->canAccessDesa($user, $desa), 403, 'Akses ditolak.');

            return $desa;
        }

        abort_if(! $user->desa_id, 403, 'Akun belum di-assign ke Desa.');

        return Desa::findOrFail($user->desa_id);
    }

    public function activeTpsFor(User $user): Tps
    {
        if (in_array($user->role, ['admin_partai', 'korcam', 'kordes'], true)) {
            abort_if(! session('admin_view_tps_id'), 403, 'Pilih TPS yang ingin dilihat.');
            $tps = Tps::with('desa.kecamatan.dapil')->findOrFail(session('admin_view_tps_id'));
            abort_if(! $this->canAccessTps($user, $tps), 403, 'Akses ditolak.');

            return $tps;
        }

        abort_if(! $user->tps_id, 403, 'Akun belum di-assign ke TPS.');

        return Tps::with('desa.kecamatan.dapil')->findOrFail($user->tps_id);
    }

    public function scopeFromKecamatan(Kecamatan $kecamatan): array
    {
        return [
            'type' => 'kecamatan',
            'id' => $kecamatan->id,
            'label' => 'Kecamatan '.($kecamatan->nama ?? '-'),
            'dapil_id' => $kecamatan->dapil_id,
        ];
    }

    public function scopeFromDesa(Desa $desa): array
    {
        return [
            'type' => 'desa',
            'id' => $desa->id,
            'label' => 'Desa '.($desa->nama ?? '-'),
            'dapil_id' => $desa->kecamatan?->dapil_id,
        ];
    }

    public function scopeFromTps(Tps $tps): array
    {
        return [
            'type' => 'tps',
            'id' => $tps->id,
            'label' => $tps->nama.' - '.($tps->desa?->nama ?? '-'),
            'dapil_id' => $tps->desa?->kecamatan?->dapil_id,
        ];
    }
}
