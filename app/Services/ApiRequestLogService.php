<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiRequestLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class ApiRequestLogService
{
    public function store(Request $request, Response $response, float $startedAt): void
    {
        if (! config('api_request_log.enabled', true)) {
            return;
        }

        if (! function_exists('tenancy') || ! tenancy()->initialized) {
            return;
        }

        try {
            ApiRequestLog::query()->create([
                'company_id'      => $this->resolveCompanyId($request),
                'user_id'         => auth('api')->id(),
                'method'          => $request->method(),
                'path'            => Str::limit($request->path(), 500, ''),
                'route_name'      => $request->route()?->getName(),
                'feature'         => $this->resolveFeature($request),
                'response_status' => $response->getStatusCode(),
                'duration_ms'     => (int) round((microtime(true) - $startedAt) * 1000),
                'ip_address'      => $request->ip(),
                'user_agent'      => Str::limit((string) $request->userAgent(), 1023, ''),
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'request_payload' => $this->encodePayload($this->captureRequestPayload($request)),
                'response_body'   => $this->truncate(
                    $this->captureResponseBody($response),
                    config('api_request_log.max_response_bytes', 65536)
                ),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist API request log', [
                'path'    => $request->path(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function resolveFeature(Request $request): ?string
    {
        $path   = $request->path();
        $method = strtoupper($request->method());

        if (! str_contains($path, 'employee-tasks')) {
            return null;
        }

        if ($method === 'POST' && preg_match('#^api/v1/employee-tasks$#', $path)) {
            return 'employee_task.create';
        }

        if ($method === 'POST' && preg_match('#^api/v1/employee-tasks/[^/]+/start$#', $path)) {
            return 'employee_task.start';
        }

        if ($method === 'POST' && preg_match('#^api/v1/employee-tasks/[^/]+/request-approval$#', $path)) {
            return 'employee_task.request_approval';
        }

        if (str_starts_with($path, 'api/v1/admin/employee-tasks')) {
            return 'employee_task.admin.' . strtolower($method);
        }

        return 'employee_task.' . strtolower($method);
    }

    private function resolveCompanyId(Request $request): ?string
    {
        $tenant = $request->header('X-Tenant');

        if ($tenant) {
            return (string) $tenant;
        }

        $user = auth('api')->user();

        return $user?->company_id ? (string) $user->company_id : null;
    }

    private function captureRequestPayload(Request $request): array
    {
        $payload = [
            'query' => $request->query(),
            'body'  => $this->sanitizeArray($request->except(array_keys($request->files->all()))),
        ];

        $files = [];

        foreach ($request->allFiles() as $key => $file) {
            $files[$key] = $this->describeUploadedFile($file);
        }

        if ($files !== []) {
            $payload['files'] = $files;
        }

        if ($request->getContent() !== '' && $request->files->count() === 0) {
            $raw = $request->getContent();

            if ($this->isJsonRequest($request)) {
                $decoded = json_decode($raw, true);
                $payload['raw'] = is_array($decoded)
                    ? $this->sanitizeArray($decoded)
                    : ['_truncated' => $this->truncate($raw, 4096)];
            } else {
                $payload['raw'] = $this->truncate($raw, 4096);
            }
        }

        return $payload;
    }

    private function captureResponseBody(Response $response): string
    {
        $content = $response->getContent();

        if ($content === false || $content === '') {
            return '';
        }

        if ($this->looksLikeJson($content)) {
            $decoded = json_decode($content, true);

            if (is_array($decoded)) {
                return json_encode(
                    $this->sanitizeArray($decoded),
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
                );
            }
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        $sensitive = array_map('strtolower', config('api_request_log.sensitive_keys', []));

        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $sanitized[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * @param  array<string, array<int, string>>  $headers
     * @return array<string, array<int, string>>
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = array_map('strtolower', config('api_request_log.sensitive_headers', []));

        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $sensitive, true)) {
                $headers[$name] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    private function encodePayload(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this->truncate($json, config('api_request_log.max_payload_bytes', 65536));
    }

    private function truncate(string $value, int $maxBytes): string
    {
        if (strlen($value) <= $maxBytes) {
            return $value;
        }

        return substr($value, 0, $maxBytes) . '…[truncated]';
    }

    private function isJsonRequest(Request $request): bool
    {
        return str_contains(strtolower((string) $request->header('Content-Type')), 'json');
    }

    private function looksLikeJson(string $content): bool
    {
        $trimmed = ltrim($content);

        return $trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[');
    }

    /**
     * @param  UploadedFile|array<int, UploadedFile>|array<string, mixed>  $file
     * @return array<string, mixed>|list<array<string, mixed>>
     */
    private function describeUploadedFile(UploadedFile|array $file): array
    {
        if ($file instanceof UploadedFile) {
            return [
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ];
        }

        return array_map(fn ($item) => $this->describeUploadedFile($item), $file);
    }
}
