<?php

declare(strict_types=1);

namespace Tests\Api;

class BoardsTest extends ApiTestCase
{
    public function testBoardCrud(): void
    {
        $token = $this->registerAndLogin();
        $headers = $this->withJsonHeaders(['X-Session-Token' => $token]);

        $create = $this->request('POST', '/boards', ['name' => 'Product'], $headers);
        $this->assertSame(201, $create['status']);
        $boardId = $create['body']['data']['board']['id'];

        $list = $this->request('GET', '/boards', [], $headers);
        $this->assertSame(200, $list['status']);
        $this->assertCount(1, $list['body']['data']['boards']);

        $update = $this->request('PATCH', '/boards/' . $boardId, ['name' => 'Ops'], $headers);
        $this->assertSame(200, $update['status']);
        $this->assertSame('Ops', $update['body']['data']['board']['name']);

        $delete = $this->request('DELETE', '/boards/' . $boardId, [], $headers);
        $this->assertSame(200, $delete['status']);
    }

    private function registerAndLogin(): string
    {
        $headers = $this->withInternalAuthHeaders();

        $this->request('POST', '/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'boards@example.com',
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        $login = $this->request('POST', '/auth/login', [
            'email' => 'boards@example.com',
            'password' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        return $login['body']['data']['token'];
    }
}
