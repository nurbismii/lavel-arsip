<?php

namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogService
{
    public static function log(string $action, string $description, $subject = null, $user = null): void
    {
        try {
            ActivityLog::create([
                'user_id' => data_get($user ?: auth()->user(), 'id'),
                'action' => $action,
                'description' => $description,
                'subject_type' => is_object($subject) ? get_class($subject) : null,
                'subject_id' => data_get($subject, 'id'),
                'subject_name' => self::resolveSubjectName($subject),
                'ip_address' => optional(request())->ip(),
                'user_agent' => optional(request())->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    private static function resolveSubjectName($subject): ?string
    {
        if (!is_object($subject)) {
            return null;
        }

        foreach (['judul', 'nama_lokasi', 'nama_file', 'name', 'email'] as $attribute) {
            $value = data_get($subject, $attribute);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
