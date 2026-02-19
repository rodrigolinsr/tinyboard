import { proxyRoutes } from '../constants/apiRoutes'
import type { ApiError } from './client'

type FetchOptions = RequestInit & {
  headers?: HeadersInit
}

async function clientRequest<T>(path: string, options: FetchOptions = {}) {
  const response = await fetch(path, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(options.headers ?? {}),
    },
  })

  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    const message = payload?.message ?? 'Request failed'
    const error: ApiError = { message, status: response.status }
    throw error
  }

  const payload = await response.json()
  return payload?.data as T
}

export function registerUser(payload: { name: string; email: string; password: string; passwordConfirm: string }) {
  return clientRequest<{ user: unknown }>(proxyRoutes.auth.register, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function loginUser(payload: { email: string; password: string }) {
  return clientRequest<{ token: string; user: { id: number; name: string; email: string } }>(proxyRoutes.auth.login, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function logoutUser() {
  return clientRequest<{ ok: boolean }>(proxyRoutes.auth.logout, { method: 'POST' })
}
