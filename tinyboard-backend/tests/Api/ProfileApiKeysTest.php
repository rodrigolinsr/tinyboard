<?php

declare(strict_types=1);

namespace Tests\Api;

class ProfileApiKeysTest extends ApiTestCase
{
    public function testApiKeyLifecycle(): void
    {
        $token = $this->registerAndLogin();
        $headers = $this->withJsonHeaders(['X-Session-Token' => $token]);

        $create = $this->request('POST', '/profile/api-keys', ['label' => 'OpenClaw'], $headers);
        $this->assertSame(201, $create['status']);
        $this->assertArrayHasKey('plain', $create['body']['data']['key']);

        $list = $this->request('GET', '/profile/api-keys', [], $headers);
        $this->assertSame(200, $list['status']);
        $this->assertCount(1, $list['body']['data']['keys']);

        $keyId = $list['body']['data']['keys'][0]['id'];
        $delete = $this->request('DELETE', '/profile/api-keys/' . $keyId, [], $headers);
        $this->assertSame(200, $delete['status']);
    }

    public function testApiKeyAllowsExternalAccess(): void
    {
        $token = $this->registerAndLogin();
        $headers = $this->withJsonHeaders(['X-Session-Token' => $token]);

        $create = $this->request('POST', '/profile/api-keys', ['label' => 'External'], $headers);
        $plain = $create['body']['data']['key']['plain'];

        $board = $this->request('POST', '/boards', ['name' => 'External Board'], $this->withJsonHeaders([
            'X-API-Key' => $plain,
        ]));
        $this->assertSame(201, $board['status']);
    }

    private function registerAndLogin(): string
    {
        $headers = $this->withInternalAuthHeaders();

        $this->request('POST', '/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'keys@example.com',
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        $login = $this->request('POST', '/auth/login', [
            'email' => 'keys@example.com',
            'password' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        return $login['body']['data']['token'];
    }
}
