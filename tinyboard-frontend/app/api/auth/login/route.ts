import { NextResponse } from 'next/server'
import { apiRoutes } from '@/lib/constants/apiRoutes'

export async function POST(request: Request) {
  const body = await request.json()
  const internalKey = process.env.INTERNAL_API_KEY
  const apiBaseUrl = process.env.API_BASE_URL ?? 'http://localhost:8080'

  const response = await fetch(`${apiBaseUrl}${apiRoutes.auth.login}`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(internalKey ? { 'X-Internal-API-Key': internalKey } : {}),
    },
    body: JSON.stringify(body),
  })

  const payload = await response.json().catch(() => null)
  return NextResponse.json(payload ?? { message: 'Request failed' }, { status: response.status })
}
