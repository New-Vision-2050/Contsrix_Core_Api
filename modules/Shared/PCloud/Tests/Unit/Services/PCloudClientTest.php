<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Tests\Unit\Services;

use Illuminate\Support\Facades\Http;
use Modules\Shared\PCloud\Services\PCloudClient;
use Modules\Shared\PCloud\Tests\Unit\PCloudTestCase;

final class PCloudClientTest extends PCloudTestCase
{
    private PCloudClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new PCloudClient();
    }

    public function test_is_configured_requires_enabled_email_and_password(): void
    {
        $this->assertTrue($this->client->isConfigured());

        $this->configurePCloud(['enabled' => false]);
        $this->assertFalse($this->client->isConfigured());

        $this->configurePCloud(['enabled' => true, 'email' => '']);
        $this->assertFalse($this->client->isConfigured());

        $this->configurePCloud(['email' => 'test@example.com', 'password' => '']);
        $this->assertFalse($this->client->isConfigured());
    }

    public function test_ensure_folder_path_creates_nested_folders_with_digest_auth(): void
    {
        $digest = 'testdigest123';
        $expectedPasswordDigest = sha1('secret-pass' . sha1(strtolower('test@example.com')) . $digest);

        Http::fake([
            'https://api.pcloud.com/getdigest' => Http::sequence()
                ->push(['result' => 0, 'digest' => $digest])
                ->push(['result' => 0, 'digest' => $digest]),
            'https://api.pcloud.com/createfolderifnotexists' => Http::sequence()
                ->push(['result' => 0, 'metadata' => ['folderid' => 10, 'name' => 'Constrix Archive']])
                ->push(['result' => 0, 'metadata' => ['folderid' => 20, 'name' => 'Company A']]),
        ]);

        $folderId = $this->client->ensureFolderPath('Constrix Archive/Company A');

        $this->assertSame(20, $folderId);

        Http::assertSent(function ($request) use ($expectedPasswordDigest) {
            if (!str_contains($request->url(), 'createfolderifnotexists')) {
                return false;
            }

            $data = $request->data();

            return ($data['username'] ?? null) === 'test@example.com'
                && ($data['passworddigest'] ?? null) === $expectedPasswordDigest
                && isset($data['digest'])
                && !array_key_exists('password', $data);
        });
    }

    public function test_upload_file_uses_put_with_image_content_type(): void
    {
        $digest = 'uploaddigest';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        Http::fake([
            'https://api.pcloud.com/getdigest' => Http::response(['result' => 0, 'digest' => $digest]),
            'https://api.pcloud.com/uploadfile*' => Http::response([
                'result' => 0,
                'metadata' => [[
                    'fileid' => 999,
                    'name' => 'photo.png',
                    'contenttype' => 'image/png',
                    'size' => strlen($png),
                    'category' => 1,
                ]],
            ]),
        ]);

        $result = $this->client->uploadFile(20, 'photo.png', $png, 'image/png');

        $this->assertSame(0, $result['result']);
        $this->assertSame(999, $result['metadata'][0]['fileid']);
        $this->assertSame('image/png', $result['metadata'][0]['contenttype']);

        Http::assertSent(function ($request) use ($png) {
            if ($request->method() !== 'PUT') {
                return false;
            }

            if (!str_contains($request->url(), '/uploadfile')) {
                return false;
            }

            $this->assertStringContainsString('folderid=20', $request->url());
            $this->assertStringContainsString('filename=photo.png', $request->url());
            $this->assertSame('image/png', $request->header('Content-Type')[0] ?? null);
            $this->assertSame($png, $request->body());

            return true;
        });
    }

    public function test_upload_file_adds_extension_from_mime_when_missing(): void
    {
        $digest = 'extdigest';

        Http::fake([
            'https://api.pcloud.com/getdigest' => Http::response(['result' => 0, 'digest' => $digest]),
            'https://api.pcloud.com/uploadfile*' => function ($request) {
                parse_str(parse_url($request->url(), PHP_URL_QUERY) ?? '', $query);

                return Http::response([
                    'result' => 0,
                    'metadata' => [[
                        'fileid' => 1,
                        'name' => $query['filename'] ?? '',
                        'contenttype' => 'image/jpeg',
                    ]],
                ]);
            },
        ]);

        $result = $this->client->uploadFile(1, 'holiday-photo', 'binary', 'image/jpeg');

        $this->assertSame('holiday-photo.jpg', $result['metadata'][0]['name']);
    }

    public function test_upload_file_throws_on_api_error(): void
    {
        Http::fake([
            'https://api.pcloud.com/getdigest' => Http::response(['result' => 0, 'digest' => 'd']),
            'https://api.pcloud.com/uploadfile*' => Http::response([
                'result' => 2000,
                'error' => 'Log in failed.',
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('pCloud uploadfile failed');

        $this->client->uploadFile(1, 'a.txt', 'hello', 'text/plain');
    }

    public function test_sanitize_keeps_arabic_folder_names(): void
    {
        $digest = 'ar';

        Http::fake([
            'https://api.pcloud.com/getdigest' => Http::response(['result' => 0, 'digest' => $digest]),
            'https://api.pcloud.com/createfolderifnotexists' => Http::response([
                'result' => 0,
                'metadata' => ['folderid' => 7, 'name' => 'الصيانه و الطوارئ'],
            ]),
        ]);

        $id = $this->client->ensureFolderPath('الصيانه و الطوارئ');
        $this->assertSame(7, $id);

        Http::assertSent(function ($request) {
            if (!str_contains($request->url(), 'createfolderifnotexists')) {
                return false;
            }

            return ($request->data()['name'] ?? null) === 'الصيانه و الطوارئ';
        });
    }
}
