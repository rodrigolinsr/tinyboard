<?php

declare(strict_types=1);

namespace Tests\Api;

class AuthTest extends ApiTestCase
{
    public function testAuthEndpointsRequireInternalKey(): void
    {
        $response = $this->request('POST', '/auth/register', [], $this->withJsonHeaders());
        $this->assertSame(401, $response['status']);
    }

    public function testRegisterAndLoginFlow(): void
    {
        $headers = $this->withInternalAuthHeaders();

        $register = $this->request('POST', '/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        $this->assertSame(201, $register['status']);
        $this->assertSame('jane@example.com', $register['body']['data']['user']['email']);

        $login = $this->request('POST', '/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        $this->assertSame(200, $login['status']);
        $this->assertArrayHasKey('token', $login['body']['data']);
    }
}
