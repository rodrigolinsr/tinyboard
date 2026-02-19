<?php

declare(strict_types=1);

namespace Tests\Api;

class CommentsTest extends ApiTestCase
{
    public function testCommentCrud(): void
    {
        $token = $this->registerAndLogin();
        $headers = $this->withJsonHeaders(['X-Session-Token' => $token]);
        $boardId = $this->createBoard($headers);
        $taskId = $this->createTask($headers, $boardId);

        $create = $this->request('POST', '/boards/' . $boardId . '/tasks/' . $taskId . '/comments', [
            'body' => 'Hello there',
        ], $headers);
        $this->assertSame(201, $create['status']);
        $commentId = $create['body']['data']['comment']['id'];

        $list = $this->request('GET', '/boards/' . $boardId . '/tasks/' . $taskId . '/comments', [], $headers);
        $this->assertSame(200, $list['status']);
        $this->assertCount(1, $list['body']['data']['comments']);

        $delete = $this->request('DELETE', '/boards/' . $boardId . '/tasks/' . $taskId . '/comments/' . $commentId, [], $headers);
        $this->assertSame(200, $delete['status']);
    }

    private function registerAndLogin(): string
    {
        $headers = $this->withInternalAuthHeaders();

        $this->request('POST', '/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'comments@example.com',
            'password' => 'secret123',
            'passwordConfirm' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        $login = $this->request('POST', '/auth/login', [
            'email' => 'comments@example.com',
            'password' => 'secret123',
        ], $this->withInternalHostHeaders($headers, 'app.localhost'));

        return $login['body']['data']['token'];
    }

    private function createBoard(array $headers): int
    {
        $create = $this->request('POST', '/boards', ['name' => 'Main'], $headers);
        return (int) $create['body']['data']['board']['id'];
    }

    private function createTask(array $headers, int $boardId): int
    {
        $create = $this->request('POST', '/boards/' . $boardId . '/tasks', ['title' => 'Task A'], $headers);
        return (int) $create['body']['data']['task']['id'];
    }
}
