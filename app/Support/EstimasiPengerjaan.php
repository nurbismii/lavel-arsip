<?php

namespace App\Support;

class EstimasiPengerjaan
{
    private const MINUTES_PER_WORKDAY = 480;
    private const MINUTES_PER_WORKWEEK = 2400;
    private const MINUTES_PER_WORKMONTH = 9600;

    public static function fromTahaps($tahaps): ?string
    {
        $totalMinutes = collect($tahaps)
            ->map(function ($tahap) {
                return self::parseToMinutes(data_get($tahap, 'estimasi'));
            })
            ->filter(function ($minutes) {
                return $minutes !== null && $minutes > 0;
            })
            ->sum();

        return $totalMinutes > 0 ? self::formatMinutes((int) $totalMinutes) : null;
    }

    public static function parseToMinutes($value): ?int
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);
        $pattern = '/(\d+(?:\.\d+)?)\s*(?:-|s\/d|sd|sampai)?\s*(\d+(?:\.\d+)?)?\s*(hari kerja|hk|menit|mnt|min|jam|hari|hr|minggu|pekan|bulan|m)\b/u';

        if (!preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $totalMinutes = 0;

        foreach ($matches as $match) {
            $amount = isset($match[2]) && $match[2] !== ''
                ? (float) $match[2]
                : (float) $match[1];

            $multiplier = self::unitMultiplier($match[3]);

            if ($multiplier === null) {
                continue;
            }

            $totalMinutes += (int) round($amount * $multiplier);
        }

        return $totalMinutes > 0 ? $totalMinutes : null;
    }

    private static function unitMultiplier(string $unit): ?int
    {
        if (in_array($unit, ['menit', 'mnt', 'min', 'm'], true)) {
            return 1;
        }

        if ($unit === 'jam') {
            return 60;
        }

        if (in_array($unit, ['hari kerja', 'hk', 'hari', 'hr'], true)) {
            return self::MINUTES_PER_WORKDAY;
        }

        if (in_array($unit, ['minggu', 'pekan'], true)) {
            return self::MINUTES_PER_WORKWEEK;
        }

        if ($unit === 'bulan') {
            return self::MINUTES_PER_WORKMONTH;
        }

        return null;
    }

    private static function formatMinutes(int $minutes): string
    {
        $days = intdiv($minutes, self::MINUTES_PER_WORKDAY);
        $remainingMinutes = $minutes % self::MINUTES_PER_WORKDAY;
        $hours = intdiv($remainingMinutes, 60);
        $remainingMinutes %= 60;
        $parts = [];

        if ($days > 0) {
            $parts[] = $days . ' hari kerja';
        }

        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }

        if ($remainingMinutes > 0 || empty($parts)) {
            $parts[] = $remainingMinutes . ' menit';
        }

        return implode(' ', $parts);
    }
}
