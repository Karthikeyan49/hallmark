<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ComplianceItem;

final class DashboardController extends Controller
{
    public function index(Request $request): string
    {
        $this->requireAuth();

        $stats = ComplianceItem::stats();
        $recent = ComplianceItem::all(5);

        return $this->view('dashboard/index', [
            'title'  => 'Dashboard',
            'stats'  => $stats,
            'recent' => $recent,
        ]);
    }
}
