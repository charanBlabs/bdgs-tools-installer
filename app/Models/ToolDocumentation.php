<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ToolDocumentation extends Model
{
    protected $table = 'tool_documentation';

    protected $fillable = [
        'tool_slug',
        'image_url',
        'screenshots',
        'installation_type',
        'installation_steps',
        'manual_steps',
        'features',
        'ui_description',
        'feature_notes',
        'files_used',
        'version_notes',
    ];

    protected $casts = [
        'screenshots' => 'array',
        'features' => 'array',
        'files_used' => 'array',
    ];

    public const INSTALLATION_TYPES = [
        'quick_install' => '100% Quick Installer (widgets only, no manual steps)',
        'installer_plus_manual' => 'Installer + manual (e.g. CSS/design per client)',
        'manual_only' => 'Manual only (no installer)',
    ];

    public const PRODUCT_TYPE_LABELS = [
        'service' => 'Service',
        'quick_service' => 'Quick Service',
        'flagship_service' => 'Flagship Service',
        'tool' => 'Tool',
    ];

    public function getInstallationTypeLabel(): string
    {
        return self::INSTALLATION_TYPES[$this->installation_type] ?? $this->installation_type;
    }

    /** Get registry config for this tool. */
    public function getConfig(): ?array
    {
        return config("tools.registry.{$this->tool_slug}");
    }

    /** Widget count from config. */
    public function getWidgetCount(): int
    {
        $config = $this->getConfig();
        $widgets = $config['widgets'] ?? [];
        return is_array($widgets) ? count($widgets) : 0;
    }

    /** Unique file keys used across widgets (data, style, script). */
    public function getFilesUsedFromConfig(): array
    {
        $config = $this->getConfig();
        $widgets = $config['widgets'] ?? [];
        $files = [];
        foreach ($widgets as $w) {
            foreach (['widget_data_key', 'widget_style_key', 'widget_javascript_key'] as $key) {
                if (!empty($w[$key])) {
                    $files[$w[$key]] = true;
                }
            }
        }
        return array_keys($files);
    }
}
