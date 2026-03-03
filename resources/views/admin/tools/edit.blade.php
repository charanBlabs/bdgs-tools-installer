@extends('layouts.admin')

@section('title', 'Edit manual – ' . ($config['name'] ?? $toolSlug))
@section('heading', 'Edit manual: ' . ($config['name'] ?? $toolSlug))

@section('top_actions')
    <a href="{{ route('admin.tools.show', $toolSlug) }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">← Back to tool</a>
@endsection

@section('content')
<form method="post" action="{{ route('admin.tools.update', $toolSlug) }}" class="space-y-6 max-w-4xl">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Images</h2>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <label for="image_url" class="block text-sm font-medium text-slate-700 mb-1">Main image URL</label>
                <input type="url" id="image_url" name="image_url" value="{{ old('image_url', $doc->image_url) }}" placeholder="https://..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
            </div>
            <div>
                <label for="screenshots" class="block text-sm font-medium text-slate-700 mb-1">Screenshots (one URL per line, for carousel)</label>
                <textarea id="screenshots" name="screenshots" rows="4" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="https://...&#10;https://...">{{ old('screenshots', $doc->screenshots ? implode("\n", $doc->screenshots) : '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Installation type</h2>
            <p class="text-xs text-slate-500 mt-1">Choose how this tool is installed so the manual reflects the right scenario.</p>
        </div>
        <div class="p-5">
            <div class="space-y-2">
                @foreach(\App\Models\ToolDocumentation::INSTALLATION_TYPES as $value => $label)
                    <label class="flex items-start gap-3 p-3 border border-slate-200 rounded-lg cursor-pointer hover:bg-slate-50">
                        <input type="radio" name="installation_type" value="{{ $value }}" {{ old('installation_type', $doc->installation_type) === $value ? 'checked' : '' }} class="mt-1">
                        <span class="text-sm text-slate-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Installation steps (step-by-step guide)</h2>
            <p class="text-xs text-slate-500 mt-1">For quick installer: direct steps. For installer+manual or manual-only: describe the process.</p>
        </div>
        <div class="p-5">
            <textarea name="installation_steps" rows="10" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="1. Verify BD API token&#10;2. Select this tool and enter license (if service)&#10;3. Run Install...">{{ old('installation_steps', $doc->installation_steps) }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Manual steps (after install)</h2>
            <p class="text-xs text-slate-500 mt-1">Only for &quot;Installer + manual&quot;: e.g. CSS adjustments per client, design tweaks.</p>
        </div>
        <div class="p-5">
            <textarea name="manual_steps" rows="6" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="Optional: client-specific CSS, design notes...">{{ old('manual_steps', $doc->manual_steps) }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Features</h2>
            <p class="text-xs text-slate-500 mt-1">One feature per line.</p>
        </div>
        <div class="p-5">
            <textarea name="features" rows="6" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="Feature one&#10;Feature two">{{ old('features', $doc->features ? implode("\n", $doc->features) : '') }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">UI description</h2>
            <p class="text-xs text-slate-500 mt-1">Overview of the UI for developers.</p>
        </div>
        <div class="p-5">
            <textarea name="ui_description" rows="5" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('ui_description', $doc->ui_description) }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-sky-50 border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Feature notes (for future versions)</h2>
            <p class="text-xs text-slate-500 mt-1">Ideas for new features. Developers use this to plan and build new versions.</p>
        </div>
        <div class="p-5">
            <textarea name="feature_notes" rows="6" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="e.g. Add dark mode option; support for X...">{{ old('feature_notes', $doc->feature_notes) }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Files used</h2>
            <p class="text-xs text-slate-500 mt-1">One per line or comma-separated. Leave empty to auto-fill from widget config.</p>
        </div>
        <div class="p-5">
            <textarea name="files_used" rows="3" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 font-mono text-sm" placeholder="admin.php, admin.css, ...">{{ old('files_used', $doc->files_used ? implode("\n", $doc->files_used) : '') }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-900">Version notes</h2>
            <p class="text-xs text-slate-500 mt-1">Changelog or version history.</p>
        </div>
        <div class="p-5">
            <textarea name="version_notes" rows="5" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500" placeholder="v1.0 – Initial release&#10;v1.1 – ...">{{ old('version_notes', $doc->version_notes) }}</textarea>
        </div>
    </div>

    <div class="flex flex-wrap gap-3">
        <button type="submit" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-sky-500 rounded-lg hover:bg-sky-600 focus:ring-2 focus:ring-sky-500 focus:ring-offset-2">Save changes</button>
        <a href="{{ route('admin.tools.show', $toolSlug) }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</a>
    </div>
</form>
@endsection
