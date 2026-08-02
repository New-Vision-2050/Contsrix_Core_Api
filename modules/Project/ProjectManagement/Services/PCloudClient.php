<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Project\ProjectManagement\Exceptions\PCloudApiException;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;

class PCloudClient
{
    private ?string $authToken = null;

    public function assertConfigured(): void
    {
        if (! (bool) config('services.pcloud.enabled')) {
            throw new PCloudConfigurationException('pCloud integration is disabled.');
        }

        foreach (['email', 'password', 'root_folder'] as $key) {
            if (! is_string(config("services.pcloud.{$key}")) || trim((string) config("services.pcloud.{$key}")) === '') {
                throw new PCloudConfigurationException("pCloud {$key} is not configured.");
            }
        }
    }

    public function rootFolderName(): string
    {
        $this->assertConfigured();

        return $this->normalizeFolderName((string) config('services.pcloud.root_folder'));
    }

    public function normalizeFolderName(string $name): string
    {
        $name = trim(preg_replace('/[\/\\\\]+/', '-', $name) ?? '');
        $name = trim(preg_replace('/[\x00-\x1F\x7F]+/', '', $name) ?? '');

        return $name !== '' ? $name : 'Untitled';
    }

    /**
     * @return array{folderid:int, created:bool, metadata:array}
     */
    public function ensureFolder(int $parentFolderId, string $name): array
    {
        $data = $this->request('createfolderifnotexists', [
            'auth' => $this->auth(),
            'folderid' => $parentFolderId,
            'name' => $this->normalizeFolderName($name),
        ]);

        $metadata = $data['metadata'] ?? null;
        if (! is_array($metadata) || ! isset($metadata['folderid'])) {
            throw new PCloudApiException('pCloud createfolderifnotexists returned invalid metadata.', 'createfolderifnotexists');
        }

        return [
            'folderid' => (int) $metadata['folderid'],
            'created' => (bool) ($data['created'] ?? false),
            'metadata' => $metadata,
        ];
    }

    /**
     * @param  resource  $stream
     */
    public function uploadFile(int $folderId, mixed $stream, string $filename, ?int $mtime = null): array
    {
        $filename = basename($filename);
        if ($filename === '' || $filename === '.' || $filename === DIRECTORY_SEPARATOR) {
            $filename = 'attachment';
        }

        $params = [
            'auth' => $this->auth(),
            'folderid' => $folderId,
            'filename' => $filename,
            'renameifexists' => 1,
            'nopartial' => 1,
        ];

        if ($mtime !== null) {
            $params['mtime'] = $mtime;
        }

        $response = Http::timeout($this->timeout())
            ->acceptJson()
            ->attach('file', $stream, $filename)
            ->post($this->url('uploadfile'), $params);

        return $this->decodeResponse($response, 'uploadfile');
    }

    private function auth(): string
    {
        $this->assertConfigured();

        if ($this->authToken !== null) {
            return $this->authToken;
        }

        $digestData = $this->request('getdigest');
        $digest = $digestData['digest'] ?? null;

        if (! is_string($digest) || $digest === '') {
            throw new PCloudApiException('pCloud getdigest returned invalid digest.', 'getdigest');
        }

        $email = (string) config('services.pcloud.email');
        $password = (string) config('services.pcloud.password');
        $passwordDigest = sha1($password.sha1(strtolower($email)).$digest);

        $authData = $this->request('userinfo', [
            'getauth' => 1,
            'logout' => 1,
            'username' => $email,
            'digest' => $digest,
            'passworddigest' => $passwordDigest,
        ]);

        $auth = $authData['auth'] ?? null;
        if (! is_string($auth) || $auth === '') {
            throw new PCloudApiException('pCloud userinfo did not return an auth token.', 'userinfo');
        }

        return $this->authToken = $auth;
    }

    private function request(string $method, array $params = []): array
    {
        $response = Http::timeout($this->timeout())
            ->acceptJson()
            ->get($this->url($method), $params);

        return $this->decodeResponse($response, $method);
    }

    private function decodeResponse(Response $response, string $method): array
    {
        if (! $response->successful()) {
            throw new PCloudApiException("pCloud {$method} request failed with HTTP {$response->status()}.", $method);
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new PCloudApiException("pCloud {$method} returned an invalid response.", $method);
        }

        $result = (int) ($data['result'] ?? 0);
        if ($result !== 0) {
            $error = is_string($data['error'] ?? null) ? $data['error'] : 'request failed';

            throw new PCloudApiException("pCloud {$method} failed: {$error}", $method, $result);
        }

        return $data;
    }

    private function url(string $method): string
    {
        return rtrim((string) config('services.pcloud.base_url', 'https://api.pcloud.com'), '/').'/'.ltrim($method, '/');
    }

    private function timeout(): int
    {
        return max(1, (int) config('services.pcloud.timeout', 60));
    }
}
