<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ToolServerAsset extends Model
{
    public const BASE_URL_KEY = '__base_url__';

    protected $fillable = [
        'tool_slug',
        'file_name',
        'storage_path',
        'custom_base_url',
    ];

    /**
     * Get the effective base URL for a tool: custom_base_url if set, else app URL + route path.
     */
    public static function getBaseUrlForTool(string $toolSlug): string
    {
        $row = self::where('tool_slug', $toolSlug)->where('file_name', self::BASE_URL_KEY)->first();
        if ($row && !empty($row->custom_base_url)) {
            return rtrim($row->custom_base_url, '/');
        }
        return rtrim(url('/api/tool-assets/' . $toolSlug), '/');
    }

    /**
     * Get list of server file names (excluding the base URL row) for a tool.
     *
     * @return array<int, string>
     */
    public static function getFileNamesForTool(string $toolSlug): array
    {
        return self::where('tool_slug', $toolSlug)
            ->where('file_name', '!=', self::BASE_URL_KEY)
            ->pluck('file_name')
            ->values()
            ->all();
    }

    /**
     * Resolve storage path to full path on disk.
     */
    public function getStorageFullPath(): string
    {
        return Storage::disk('local')->path($this->storage_path);
    }
}
