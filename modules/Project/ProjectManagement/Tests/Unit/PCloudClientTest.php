<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Tests\Unit;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;
use Modules\Project\ProjectManagement\Services\PCloudClient;
use Tests\TestCase;

class PCloudClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.pcloud.enabled' => true,
            'services.pcloud.email' => 'user@example.test',
            'services.pcloud.password' => 'secret-password',
            'services.pcloud.root_folder' => 'Constrix Archive',
            'services.pcloud.base_url' => 'https://api.pcloud.com',
            'services.pcloud.timeout' => 5,
        ]);
    }

    public function test_it_authenticates_with_digest_and_creates_folder_idempotently(): void
    {
        Http::fake([
            'https://api.pcloud.com/getdigest*' => Http::response([
                'result' => 0,
                'digest' => 'digest-token',
            ]),
            'https://api.pcloud.com/userinfo*' => Http::response([
                'result' => 0,
                'auth' => 'auth-token',
            ]),
            'https://api.pcloud.com/createfolderifnotexists*' => Http::response([
                'result' => 0,
                'created' => true,
                'metadata' => [
                    'folderid' => 123,
                    'name' => 'Root-Project',
                ],
            ]),
        ]);

        $folder = (new PCloudClient)->ensureFolder(0, 'Root/Project');

        $this->assertSame(123, $folder['folderid']);
        $this->assertTrue($folder['created']);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/userinfo')
                && $request['username'] === 'user@example.test'
                && $request['digest'] === 'digest-token'
                && $request['passworddigest'] === sha1('secret-password'.sha1('user@example.test').'digest-token')
                && ! isset($request['password']);
        });

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/createfolderifnotexists')
            && $request['auth'] === 'auth-token'
            && (int) $request['folderid'] === 0
            && $request['name'] === 'Root-Project');
    }

    public function test_it_uploads_files_with_pcloud_rename_if_exists_enabled(): void
    {
        Http::fake([
            'https://api.pcloud.com/getdigest*' => Http::response([
                'result' => 0,
                'digest' => 'digest-token',
            ]),
            'https://api.pcloud.com/userinfo*' => Http::response([
                'result' => 0,
                'auth' => 'auth-token',
            ]),
            'https://api.pcloud.com/uploadfile*' => Http::response([
                'result' => 0,
                'fileids' => [456],
                'metadata' => [
                    ['fileid' => 456, 'name' => 'photo.jpg'],
                ],
            ]),
        ]);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'test file contents');
        rewind($stream);

        try {
            $result = (new PCloudClient)->uploadFile(987, $stream, 'photo.jpg', 1234567890);
        } finally {
            fclose($stream);
        }

        $this->assertSame(0, $result['result']);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/uploadfile')
                && $this->multipartValue($request, 'auth') === 'auth-token'
                && (int) $this->multipartValue($request, 'folderid') === 987
                && $this->multipartValue($request, 'filename') === 'photo.jpg'
                && (int) $this->multipartValue($request, 'renameifexists') === 1
                && (int) $this->multipartValue($request, 'nopartial') === 1
                && (int) $this->multipartValue($request, 'mtime') === 1234567890
                && $request->hasFile('file', null, 'photo.jpg');
        });
    }

    public function test_it_rejects_disabled_configuration(): void
    {
        config(['services.pcloud.enabled' => false]);

        $this->expectException(PCloudConfigurationException::class);

        (new PCloudClient)->assertConfigured();
    }

    private function multipartValue(Request $request, string $name): mixed
    {
        foreach ($request->data() as $part) {
            if (($part['name'] ?? null) === $name) {
                return $part['contents'] ?? null;
            }
        }

        return null;
    }
}
