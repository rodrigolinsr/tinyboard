type FetchOptions = RequestInit & {
  headers?: HeadersInit
}

import { apiRoutes } from '@/lib/constants/apiRoutes'

export type ApiError = {
  message: string
  status: number
}

const getRuntimeBaseUrl = () => {
  if (typeof window === 'undefined') {
    return (
      globalThis.__APP_CONFIG__?.apiBaseUrl ??
      process.env.NEXT_PUBLIC_API_BASE_URL ??
      process.env.API_BASE_URL
    )
  }

  const runtime = (window as typeof window & { __APP_CONFIG__?: { apiBaseUrl?: string } }).__APP_CONFIG__
    ?.apiBaseUrl
  return runtime ?? process.env.NEXT_PUBLIC_API_BASE_URL
}

async function request<T>(path: string, options: FetchOptions = {}) {
  const apiBaseUrl = getRuntimeBaseUrl()
  if (!apiBaseUrl) {
    throw new Error('API base URL is not configured')
  }

  const response = await fetch(`${apiBaseUrl}${path}`, {
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

export function internalRequest<T>(path: string, options: FetchOptions = {}) {
  const internalKey = process.env.NEXT_PUBLIC_INTERNAL_API_KEY
  return request<T>(path, {
    ...options,
    headers: {
      ...(options.headers ?? {}),
      ...(internalKey ? { 'X-Internal-API-Key': internalKey } : {}),
    },
  })
}

export function sessionRequest<T>(path: string, token: string, options: FetchOptions = {}) {
  return request<T>(path, {
    ...options,
    headers: {
      ...(options.headers ?? {}),
      'X-Session-Token': token,
    },
  })
}

export const apiClient = {
  auth: {
    register: (payload: { name: string; email: string; password: string; passwordConfirm: string }) =>
      internalRequest<{ user: unknown }>(apiRoutes.auth.register, {
        method: 'POST',
        body: JSON.stringify(payload),
      }),
    login: (payload: { email: string; password: string }) =>
      internalRequest<{ token: string; user: unknown }>(apiRoutes.auth.login, {
        method: 'POST',
        body: JSON.stringify(payload),
      }),
  },
}
