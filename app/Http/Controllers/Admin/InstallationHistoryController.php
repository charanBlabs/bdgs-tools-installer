<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstallationHistory;
use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallationHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = InstallationHistory::query()
            ->with('license')
            ->orderByDesc('created_at');

        if ($request->filled('license_id')) {
            $query->where('license_id', $request->input('license_id'));
        }
        if ($request->filled('tool_slug')) {
            $query->where('tool_slug', $request->input('tool_slug'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }
        if ($request->filled('success')) {
            if ($request->input('success') === '1') {
                $query->where('success', true);
            } elseif ($request->input('success') === '0') {
                $query->where('success', false);
            }
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $histories = $query->paginate(20)->withQueryString();
        $tools = config('tools.registry', []);
        $licenses = License::query()->orderByDesc('created_at')->get(['id', 'token', 'tool_slug']);

        return view('admin.installation-history.index', [
            'histories' => $histories,
            'tools' => $tools,
            'licenses' => $licenses,
        ]);
    }

    public function show(InstallationHistory $installationHistory): View
    {
        $installationHistory->load('license');
        $tools = config('tools.registry', []);

        return view('admin.installation-history.show', [
            'history' => $installationHistory,
            'tools' => $tools,
        ]);
    }
}
