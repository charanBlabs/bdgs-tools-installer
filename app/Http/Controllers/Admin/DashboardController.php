<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalLicenses = License::count();
        $activeLicenses = License::where('revoked', false)->count();
        $allTools = config('tools.registry', []);

        $productType = $request->query('product_type');
        if ($productType !== null && $productType !== '') {
            $tools = array_filter($allTools, fn ($c) => ($c['product_type'] ?? 'tool') === $productType);
        } else {
            $tools = $allTools;
        }

        return view('admin.dashboard', [
            'totalLicenses' => $totalLicenses,
            'activeLicenses' => $activeLicenses,
            'tools' => $tools,
        ]);
    }
}
