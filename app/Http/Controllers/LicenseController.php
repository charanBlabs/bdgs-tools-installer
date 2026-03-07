<?php

namespace App\Http\Controllers;

use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    public function index(Request $request): View
    {
        $query = License::query()->orderByDesc('created_at');

        if ($request->filled('tool')) {
            $query->where('tool_slug', $request->input('tool'));
        }
        if ($request->filled('status') && $request->input('status') !== '') {
            $status = $request->input('status');
            if ($status === 'revoked') {
                $query->where('revoked', true);
            } elseif ($status === 'active') {
                $query->where('revoked', false);
                $query->where(function ($q) {
                    $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                });
                $query->where(function ($q) {
                    $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                });
            } elseif ($status === 'expired') {
                $query->where('revoked', false)->whereNotNull('valid_until')->where('valid_until', '<', now());
            } elseif ($status === 'not_yet_valid') {
                $query->where('revoked', false)->whereNotNull('valid_from')->where('valid_from', '>', now());
            }
        }
        if ($request->filled('search')) {
            $term = '%' . trim($request->input('search')) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('token', 'like', $term)
                    ->orWhere('allowed_domain', 'like', $term)
                    ->orWhere('subscription_id', 'like', $term);
            });
        }

        $licenses = $query->paginate(20)->withQueryString();
        $tools = config('tools.registry', []);
        return view('admin.licenses.index', compact('licenses', 'tools'));
    }

    public function create(): View
    {
        $tools = array_keys(config('tools.registry', []));
        return view('admin.licenses.create', compact('tools'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tool_slug' => 'required|string|in:' . implode(',', array_keys(config('tools.registry', []))),
            'license_type' => 'required|string|in:subscription,lifetime',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'allowed_domain' => 'nullable|string|max:255',
            'subscription_id' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:64',
        ]);
        if ($request->filled('valid_from') && $request->filled('valid_until')) {
            $request->validate(['valid_until' => 'after_or_equal:valid_from']);
        }
        // For lifetime licenses, valid_until is not required
        if ($request->input('license_type') === 'subscription' && !$request->filled('valid_until')) {
            $request->validate(['valid_until' => 'required'], ['valid_until.required' => 'Valid until is required for subscription licenses.']);
        }
        // Parse datetimes in user's timezone so "5 mins from now" in the form is stored correctly (app is UTC)
        $tz = $request->input('timezone') && in_array($request->input('timezone'), timezone_identifiers_list(), true)
            ? $request->input('timezone')
            : config('app.timezone');
        $request->session()->put('license_form_timezone', $tz);
        $license = new License();
        $license->token = $this->licenseService->generateToken();
        $license->tool_slug = $request->input('tool_slug');
        $license->license_type = $request->input('license_type');
        $license->valid_from = $request->input('valid_from')
            ? \Carbon\Carbon::parse($request->input('valid_from'), $tz)->utc()
            : null;
        $license->valid_until = $request->input('license_type') === 'lifetime'
            ? null
            : ($request->input('valid_until')
                ? \Carbon\Carbon::parse($request->input('valid_until'), $tz)->utc()
                : null);
        $license->allowed_domain = $request->input('allowed_domain') ?: null;
        $license->subscription_id = $request->input('subscription_id') ?: null;
        $license->revoked = false;
        $license->save();
        return redirect()->route('admin.licenses.index')->with('success', 'License created. Token: ' . $license->token);
    }

    public function show(License $license): View
    {
        $tools = array_keys(config('tools.registry', []));
        return view('admin.licenses.show', compact('license', 'tools'));
    }

    public function update(Request $request, License $license)
    {
        $request->validate([
            'license_type' => 'required|string|in:subscription,lifetime',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date',
            'allowed_domain' => 'nullable|string|max:255',
            'subscription_id' => 'nullable|string|max:255',
            'timezone' => 'nullable|string|max:64',
        ]);
        if ($request->filled('valid_from') && $request->filled('valid_until')) {
            $request->validate(['valid_until' => 'after_or_equal:valid_from']);
        }
        // For lifetime licenses, valid_until is not required
        if ($request->input('license_type') === 'subscription' && !$request->filled('valid_until') && !$license->isLifetime()) {
            $request->validate(['valid_until' => 'required'], ['valid_until.required' => 'Valid until is required for subscription licenses.']);
        }
        $tz = $request->input('timezone') && in_array($request->input('timezone'), timezone_identifiers_list(), true)
            ? $request->input('timezone')
            : config('app.timezone');
        $request->session()->put('license_form_timezone', $tz);
        $license->license_type = $request->input('license_type');
        $license->valid_from = $request->input('valid_from')
            ? \Carbon\Carbon::parse($request->input('valid_from'), $tz)->utc()
            : null;
        $license->valid_until = $request->input('license_type') === 'lifetime'
            ? null
            : ($request->input('valid_until')
                ? \Carbon\Carbon::parse($request->input('valid_until'), $tz)->utc()
                : null);
        $license->allowed_domain = $request->input('allowed_domain') ?: null;
        $license->subscription_id = $request->input('subscription_id') ?: null;
        if ($request->has('revoked')) {
            $license->revoked = (bool) $request->input('revoked');
        }
        $license->save();
        return redirect()->route('admin.licenses.show', $license)->with('success', 'License updated.');
    }

    public function destroy(License $license)
    {
        $license->update(['revoked' => true]);
        return redirect()->route('admin.licenses.index')->with('success', 'License revoked.');
    }

    /**
     * Permanently delete multiple licenses (bulk delete).
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:licenses,id',
        ]);

        $count = License::whereIn('id', $request->input('ids'))->delete();
        return redirect()->route('admin.licenses.index')->with('success', $count . ' ' . Str::plural('license', $count) . ' permanently deleted.');
    }

    /**
     * Revoke multiple licenses (bulk revoke).
     */
    public function bulkRevoke(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:licenses,id',
        ]);

        $count = License::whereIn('id', $request->input('ids'))->update(['revoked' => true]);
        return redirect()->route('admin.licenses.index')->with('success', $count . ' ' . Str::plural('license', $count) . ' revoked.');
    }
}
