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
                'label' => 'Total Arsip',
                'value' => (int) ($statusTotals[Dokumen::STATUS_ARSIP] ?? 0),
                'card_class' => 'bg-light',
                'icon_wrapper_class' => 'dashboard-icon-primary',
                'icon' => 'archive',
            ],
            [
                'label' => 'Total Draft',
                'value' => (int) ($statusTotals[Dokumen::STATUS_DRAFT] ?? 0),
                'card_class' => 'bg-light',
                'icon_wrapper_class' => 'dashboard-icon-warning',
                'icon' => 'draft',
            ],
            [
                'label' => 'Total Aktif',
                'value' => (int) ($statusTotals[Dokumen::STATUS_AKTIF] ?? 0),
                'card_class' => 'bg-light',
                'icon_wrapper_class' => 'dashboard-icon-success',
                'icon' => 'active',
            ],
        ];

        $recentActivities = ActivityLog::with('user')
            ->when(!$isAdmin, function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->latest()
            ->limit(5)
            ->get();

        return view('home', [
            'dashboardStats' => $dashboardStats,
            'canAccessAllFiles' => $canAccessAllFiles,
            'isAdmin' => $isAdmin,
            'recentActivities' => $recentActivities,
        ]);
    }
}
