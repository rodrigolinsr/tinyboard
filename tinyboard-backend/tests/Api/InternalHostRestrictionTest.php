<?php

declare(strict_types=1);

namespace Tests\Api;

class InternalHostRestrictionTest extends ApiTestCase
{
    public function testInternalKeyRejectedForWrongHost(): void
    {
        $headers = $this->withInternalAuthHeaders();

        $response = $this->request('POST', '/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'blocked@example.com',
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.example.com'), ['HTTP_HOST' => 'app.example.com']);

        $this->assertSame(401, $response['status']);
    }
}
