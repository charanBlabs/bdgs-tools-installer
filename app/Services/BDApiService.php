<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BDApiService
{
    public function __construct(
        protected string $baseUrl,
        protected string $apiKey
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    protected function headerName(): string
    {
        return config('bd.auth_header', 'X-API-Key');
    }

    public function verifyToken(): array
    {
        $response = Http::withHeaders([$this->headerName() => $this->apiKey])
            ->get("{$this->baseUrl}/api/v2/token/verify");

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $payload widget_name, widget_type, widget_data, widget_style, widget_javascript, etc.
     */
    public function createWidget(array $payload): array
    {
        $body = $this->normalizePayload($payload);
        $body = $this->ensureWidgetName($body);
        $response = Http::withHeaders([$this->headerName() => $this->apiKey])
            ->asForm()
            ->post("{$this->baseUrl}/api/v2/data_widgets/create", $body);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
            'widget_id' => $response->json('widget_id') ?? $response->json('message.widget_id'),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateWidget(int $widgetId, array $payload): array
    {
        $body = $this->normalizePayload($payload);
        $body = $this->ensureWidgetName($body);
        $body['widget_id'] = $widgetId;
        $response = Http::withHeaders([$this->headerName() => $this->apiKey])
            ->asForm()
            ->put("{$this->baseUrl}/api/v2/data_widgets/update", $body);

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    public function getWidget(int $widgetId): array
    {
        $response = Http::withHeaders([$this->headerName() => $this->apiKey])
            ->get("{$this->baseUrl}/api/v2/data_widgets/get/{$widgetId}");

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'body' => $response->json() ?? [],
        ];
    }

    /**
     * Get widget by property (e.g. widget_name). BD API: GET /get/?property=...&property_value=...
     * Returns widget_id and widget_name if found, else null.
     */
    public function getWidgetByProperty(string $property, string $value): ?array
    {
        $response = Http::withHeaders([$this->headerName() => $this->apiKey])
            ->get("{$this->baseUrl}/api/v2/data_widgets/get/", [
                'property' => $property,
                'property_value' => $value,
            ]);

        if (!$response->successful()) {
            return null;
        }
        $body = $response->json() ?? [];
        $message = $body['message'] ?? null;
        if (is_array($message) && isset($message[0])) {
            $w = $message[0];
            return [
                'widget_id' => isset($w['widget_id']) ? (int) $w['widget_id'] : null,
                'widget_name' => $w['widget_name'] ?? $value,
            ];
        }
        if (is_array($message) && isset($message['widget_id'])) {
            return [
                'widget_id' => (int) $message['widget_id'],
                'widget_name' => $message['widget_name'] ?? $value,
            ];
        }
        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    protected function normalizePayload(array $payload): array
    {
        $defaults = [
            'widget_type' => 'Widget',
            'widget_class' => '',
            'widget_viewport' => 'front',
            'date_updated' => now()->format('Y-m-d H:i:s'),
            'updated_by' => '',
            'short_code' => '',
            'div_id' => '',
            'bootstrap_enabled' => 1,
            'ssl_enabled' => 1,
            'mobile_enabled' => 1,
            'widget_html_element' => 'div',
            'file_type' => '',
            'widget_settings' => '',
            'widget_values' => '',
        ];
        return array_merge($defaults, $payload);
    }

    /**
     * Ensure widget_name is a non-empty string (BD API requires it).
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    protected function ensureWidgetName(array $body): array
    {
        $name = $body['widget_name'] ?? '';
        if ($name === null || (is_string($name) && trim($name) === '')) {
            $body['widget_name'] = 'Widget';
        } else {
            $body['widget_name'] = is_string($name) ? $name : (string) $name;
        }
        return $body;
    }
}
