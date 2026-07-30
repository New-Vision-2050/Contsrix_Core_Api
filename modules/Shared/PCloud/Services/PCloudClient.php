<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin HTTP client for pCloud's JSON protocol using email/password digest auth.
 *
 * Business accounts often do not return an `auth` token from userinfo?getauth=1,
 * so every request authenticates with username + digest + passworddigest.
 *
 * @see https://docs.pcloud.com/protocols/http_json_protocol/
 * @see https://docs.pcloud.com/methods/intro/authentication.html
 */
class PCloudClient
{
    public function isConfigured(): bool
    {
        return (bool) config('pcloud.enabled')
            && filled(config('pcloud.email'))
            && filled(config('pcloud.password'));
    }

    /**
     * Ensure nested folders exist and return the leaf folder id.
     */
    public function ensureFolderPath(string $path): int
    {
        $folderId = 0;
        $segments = array_values(array_filter(
            explode('/', str_replace('\\', '/', trim($path))),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.'
        ));

        foreach ($segments as $segment) {
            $response = $this->request('createfolderifnotexists', [
                'folderid' => $folderId,
                'name' => $this->sanitizeName($segment),
            ]);

            $folderId = (int) ($response['metadata']['folderid'] ?? 0);
            if ($folderId <= 0) {
                throw new RuntimeException('pCloud createfolderifnotexists did not return folderid.');
            }
        }

        return $folderId;
    }

    /**
     * Upload file contents into an existing pCloud folder.
     *
     * Uses PUT with raw binary body so images/PDFs keep the correct bytes and
     * Content-Type (multipart string attach can be treated as text).
     *
     * @see https://docs.pcloud.com/protocols/http_json_protocol/uploading_files.html
     *
     * @return array<string, mixed>
     */
    public function uploadFile(
        int $folderId,
        string $filename,
        string $contents,
        ?string $mimeType = null,
    ): array {
        $safeName = $this->sanitizeName($filename);
        $safeName = $this->ensureExtension($safeName, $mimeType);
        $contentType = $this->resolveContentType($safeName, $mimeType);
        $host = rtrim((string) config('pcloud.default_api_host'), '/');
        $query = http_build_query(array_merge($this->freshCredentials(), [
            'folderid' => $folderId,
            'filename' => $safeName,
            'renameifexists' => 1,
        ]));

        $response = Http::timeout((int) config('pcloud.timeout', 120))
            ->withBody($contents, $contentType)
            ->withHeaders([
                'Content-Type' => $contentType,
                'Content-Length' => (string) strlen($contents),
            ])
            ->put($host . '/uploadfile?' . $query);

        if (!$response->successful()) {
            throw new RuntimeException('pCloud uploadfile HTTP error: ' . $response->status());
        }

        $payload = $response->json() ?? [];
        if (($payload['result'] ?? 1) !== 0) {
            throw new RuntimeException(
                'pCloud uploadfile failed: ' . ($payload['error'] ?? json_encode($payload))
            );
        }

        return $payload;
    }

    /**
     * @param  array<string, scalar>  $params
     * @return array<string, mixed>
     */
    public function request(string $method, array $params = []): array
    {
        $host = rtrim((string) config('pcloud.default_api_host'), '/');
        $payloadParams = array_merge($this->freshCredentials(), $params);

        $response = Http::timeout((int) config('pcloud.timeout', 120))
            ->asForm()
            ->post($host . '/' . ltrim($method, '/'), $payloadParams);

        if (!$response->successful()) {
            throw new RuntimeException("pCloud {$method} HTTP error: " . $response->status());
        }

        $payload = $response->json() ?? [];
        if (($payload['result'] ?? 1) !== 0) {
            throw new RuntimeException(
                "pCloud {$method} failed: " . ($payload['error'] ?? json_encode($payload))
            );
        }

        return $payload;
    }

    /**
     * Build one-time digest credentials.
     *
     * passworddigest = sha1(password + sha1(lowercase(email)) + digest)
     *
     * @return array{username: string, digest: string, passworddigest: string}
     */
    private function freshCredentials(): array
    {
        $email = (string) config('pcloud.email');
        $password = (string) config('pcloud.password');
        $host = rtrim((string) config('pcloud.default_api_host'), '/');

        $digestResponse = Http::timeout((int) config('pcloud.timeout', 120))
            ->get($host . '/getdigest');

        if (!$digestResponse->successful()) {
            throw new RuntimeException('pCloud getdigest HTTP error: ' . $digestResponse->status());
        }

        $digestPayload = $digestResponse->json() ?? [];
        if (($digestPayload['result'] ?? 1) !== 0 || empty($digestPayload['digest'])) {
            Log::error('pCloud getdigest failed', [
                'result' => $digestPayload['result'] ?? null,
                'error' => $digestPayload['error'] ?? null,
            ]);
            throw new RuntimeException(
                'pCloud getdigest failed: ' . ($digestPayload['error'] ?? 'missing digest')
            );
        }

        $digest = (string) $digestPayload['digest'];

        return [
            'username' => $email,
            'digest' => $digest,
            'passworddigest' => sha1($password . sha1(strtolower($email)) . $digest),
        ];
    }

    private function sanitizeName(string $name): string
    {
        $name = trim(str_replace(["\0", '/', '\\'], ['', '-', '-'], $name));
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return $name !== '' ? $name : 'untitled';
    }

    private function ensureExtension(string $filename, ?string $mimeType): string
    {
        if (pathinfo($filename, PATHINFO_EXTENSION) !== '') {
            return $filename;
        }

        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/heic' => 'heic',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
        ];

        $ext = $map[strtolower((string) $mimeType)] ?? null;

        return $ext ? "{$filename}.{$ext}" : $filename;
    }

    private function resolveContentType(string $filename, ?string $mimeType): string
    {
        if (filled($mimeType) && $mimeType !== 'application/octet-stream') {
            return $mimeType;
        }

        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return $map[$ext] ?? ($mimeType ?: 'application/octet-stream');
    }
}
