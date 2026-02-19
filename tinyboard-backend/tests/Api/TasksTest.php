<?php

declare(strict_types=1);

namespace Tests\Api;

class TasksTest extends ApiTestCase
{
    public function testTaskCrud(): void
    {
        $token = $this->registerAndLogin();
        $headers = $this->withJsonHeaders(['X-Session-Token' => $token]);
        $boardId = $this->createBoard($headers);

        $create = $this->request('POST', '/boards/' . $boardId . '/tasks', [
            'title' => 'First task',
            'status' => 'todo',
            'position' => 1,
        ], $headers);
        $this->assertSame(201, $create['status']);
        $taskId = $create['body']['data']['task']['id'];

        $list = $this->request('GET', '/boards/' . $boardId . '/tasks', [], $headers);
        $this->assertSame(200, $list['status']);
        $this->assertCount(1, $list['body']['data']['tasks']);

        $update = $this->request('PATCH', '/boards/' . $boardId . '/tasks/' . $taskId, [
            'status' => 'in_progress',
            'title' => 'Updated task',
        ], $headers);
        $this->assertSame(200, $update['status']);
        $this->assertSame('in_progress', $update['body']['data']['task']['status']);

        $delete = $this->request('DELETE', '/boards/' . $boardId . '/tasks/' . $taskId, [], $headers);
        $this->assertSame(200, $delete['status']);
    }

    private function registerAndLogin(): string
    {
        $headers = $this->withInternalAuthHeaders();

        $this->request('POST', '/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'tasks@example.com',
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        $login = $this->request('POST', '/auth/login', [
            'email' => 'tasks@example.com',
            'password' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        return $login['body']['data']['token'];
    }

    private function createBoard(array $headers): int
    {
        $create = $this->request('POST', '/boards', ['name' => 'Main'], $headers);
        return (int) $create['body']['data']['board']['id'];
    }
}
