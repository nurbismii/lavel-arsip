<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Dokumen;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();
        $canAccessAllFiles = $user->canAccessAllFiles();

        $dokumenQuery = Dokumen::query()
            ->whereHas('pekerjaan', function ($query) use ($user) {
                $query->visibleTo($user);
            });

        $statusTotals = (clone $dokumenQuery)
            ->selectRaw('status_dokumen, COUNT(*) as total')
            ->groupBy('status_dokumen')
            ->pluck('total', 'status_dokumen');

        $dashboardStats = [
            [
                'label' => 'Dalam Proses',
                'value' => (int) ($statusTotals[Dokumen::STATUS_DRAFT] ?? 0),
                'card_class' => 'bg-light',
                'icon_wrapper_class' => 'dashboard-icon-warning',
                'icon' => 'draft',
                'status' => Dokumen::STATUS_DRAFT,
            ],
            [
                'label' => 'Sedang Digunakan',
                'value' => (int) ($statusTotals[Dokumen::STATUS_AKTIF] ?? 0),
                'card_class' => 'bg-light',
                'icon_wrapper_class' => 'dashboard-icon-primary',
                'icon' => 'active',
                'status' => Dokumen::STATUS_AKTIF,
            ],
            [
                'label' => 'Selesai',
                'value' => (int) ($statusTotals[Dokumen::STATUS_ARSIP] ?? 0),
                'card_class' => 'bg-light',
                'icon_wrapper_class' => 'dashboard-icon-success',
                'icon' => 'archive',
                'status' => Dokumen::STATUS_ARSIP,
            ],
        ];

        $statusBadgeClasses = [
            Dokumen::STATUS_DRAFT => 'bg-warning text-dark',
            Dokumen::STATUS_AKTIF => 'bg-primary text-white',
            Dokumen::STATUS_ARSIP => 'bg-success text-white',
        ];

        $statusBoards = [];

        foreach (Dokumen::statusOptions() as $status => $label) {
            $statusBoards[] = [
                'status' => $status,
                'label' => $label,
                'total' => (int) ($statusTotals[$status] ?? 0),
                'badge_class' => $statusBadgeClasses[$status] ?? 'bg-secondary text-white',
                'documents' => (clone $dokumenQuery)
                    ->with(['pekerjaan.lokasi', 'pekerjaan.team', 'peminjam'])
                    ->where('status_dokumen', $status)
                    ->latest()
                    ->limit(5)
                    ->get(),
            ];
        }

        $deadlineAlertThreshold = now()->addDays(3)->toDateString();

        $deadlineAlerts = (clone $dokumenQuery)
            ->with(['pekerjaan.lokasi', 'pekerjaan.team'])
            ->where('status_dokumen', '!=', Dokumen::STATUS_ARSIP)
            ->whereHas('pekerjaan', function ($query) use ($deadlineAlertThreshold) {
                $query->whereNotNull('tanggal_target_penyelesaian')
                    ->whereDate('tanggal_target_penyelesaian', '<=', $deadlineAlertThreshold);
            })
            ->get()
            ->sortBy(function ($dokumen) {
                return optional(optional($dokumen->pekerjaan)->tanggal_target_penyelesaian)->timestamp ?? PHP_INT_MAX;
            })
            ->take(8)
            ->values();

        $hasCriticalDeadlineAlerts = $deadlineAlerts->contains(function ($dokumen) {
            $pekerjaan = $dokumen->pekerjaan;

            return $pekerjaan && $pekerjaan->hari_menuju_target !== null && $pekerjaan->hari_menuju_target <= 2;
        });

        $recentActivities = ActivityLog::with('user')
            ->when(!$isAdmin, function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('home', [
            'dashboardStats' => $dashboardStats,
            'statusBoards' => $statusBoards,
            'deadlineAlerts' => $deadlineAlerts,
            'hasCriticalDeadlineAlerts' => $hasCriticalDeadlineAlerts,
            'canAccessAllFiles' => $canAccessAllFiles,
            'isAdmin' => $isAdmin,
            'recentActivities' => $recentActivities,
        ]);
    }
}
