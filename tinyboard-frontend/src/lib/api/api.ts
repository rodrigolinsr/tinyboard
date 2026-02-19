import { apiRoutes } from '../constants/apiRoutes'
import type { ApiKey, AuthContext, Board, Comment, Task, User } from './types'
import { sessionRequest } from './client'

export function listBoards(token: string) {
  return sessionRequest<{ boards: Board[] }>(apiRoutes.boards.list, token)
}

export function createBoard(token: string, payload: { name: string; description?: string | null }) {
  return sessionRequest<{ board: Board }>(apiRoutes.boards.create, token, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateBoard(token: string, id: number, payload: { name: string; description?: string | null }) {
  return sessionRequest<{ board: Board }>(apiRoutes.boards.update(id), token, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function deleteBoard(token: string, id: number) {
  return sessionRequest<{ ok: boolean }>(apiRoutes.boards.remove(id), token, {
    method: 'DELETE',
  })
}

export function listTasks(token: string, boardId: number) {
  return sessionRequest<{ tasks: Task[] }>(apiRoutes.boards.tasks(boardId), token)
}

export function createTask(
  token: string,
  boardId: number,
  payload: { title: string; description?: string | null; status?: Task['status']; position?: number }
) {
  return sessionRequest<{ task: Task }>(apiRoutes.boards.tasks(boardId), token, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateTask(
  token: string,
  boardId: number,
  id: number,
  payload: Partial<Pick<Task, 'title' | 'description' | 'status' | 'position'>>
) {
  return sessionRequest<{ task: Task }>(apiRoutes.boards.task(boardId, id), token, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function deleteTask(token: string, boardId: number, id: number) {
  return sessionRequest<{ ok: boolean }>(apiRoutes.boards.task(boardId, id), token, {
    method: 'DELETE',
  })
}

export function listComments(token: string, boardId: number, taskId: number) {
  return sessionRequest<{ comments: Comment[] }>(apiRoutes.boards.comments(boardId, taskId), token)
}

export function createComment(token: string, boardId: number, taskId: number, payload: { body: string }) {
  return sessionRequest<{ comment: Comment }>(apiRoutes.boards.comments(boardId, taskId), token, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function deleteComment(token: string, boardId: number, taskId: number, id: number) {
  return sessionRequest<{ ok: boolean }>(apiRoutes.boards.comment(boardId, taskId, id), token, {
    method: 'DELETE',
  })
}

export function listApiKeys(token: string) {
  return sessionRequest<{ keys: ApiKey[] }>(apiRoutes.profile.apiKeys, token)
}

export function createApiKey(token: string, payload: { label: string }) {
  return sessionRequest<{ key: { plain: string } & ApiKey }>(apiRoutes.profile.apiKeys, token, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function revokeApiKey(token: string, id: number) {
  return sessionRequest<{ ok: boolean }>(apiRoutes.profile.apiKey(id), token, {
    method: 'DELETE',
  })
}

export function updateProfile(token: string, payload: { name: string }) {
  return sessionRequest<{ user: User }>(apiRoutes.profile.update, token, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function changePassword(
  token: string,
  payload: { currentPassword: string; password: string; passwordConfirm: string }
) {
  return sessionRequest<{ ok: boolean }>(apiRoutes.profile.password, token, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function getMe(token: string) {
  return sessionRequest<{ user: User; auth: AuthContext }>(apiRoutes.auth.me, token)
}
