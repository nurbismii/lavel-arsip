<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class HrisAuthService
{
    public function findValidUser(string $email, string $password): ?object
    {
        try {
            $hrisUser = DB::connection(config('services.hris_auth.connection', 'hris'))
                ->table(config('services.hris_auth.users_table', 'users'))
                ->where('email', $email)
                ->first();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if (!$hrisUser || empty($hrisUser->password)) {
            return null;
        }

        if (!$this->isActive($hrisUser)) {
            return null;
        }

        return Hash::check($password, $hrisUser->password) ? $hrisUser : null;
    }

    public function displayName(object $hrisUser): string
    {
        foreach (['name', 'nama_karyawan', 'nama'] as $field) {
            if (property_exists($hrisUser, $field) && filled($hrisUser->{$field})) {
                return (string) $hrisUser->{$field};
            }
        }

        return (string) ($hrisUser->email ?? 'User HRIS');
    }

    private function isActive(object $hrisUser): bool
    {
        if (!property_exists($hrisUser, 'status') || blank($hrisUser->status)) {
            return true;
        }

        $status = strtolower(trim((string) $hrisUser->status));

        return in_array($status, ['aktif', 'active', '1', 'true', 'yes', 'ya'], true);
    }
}
