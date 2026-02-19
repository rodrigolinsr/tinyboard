import { NextResponse } from 'next/server'
import { apiRoutes } from '@/lib/constants/apiRoutes'

export async function GET(request: Request) {
  const internalKey = process.env.INTERNAL_API_KEY
  const apiBaseUrl = process.env.API_BASE_URL ?? 'http://localhost:8080'
  const sessionToken = request.headers.get('x-session-token')

  const response = await fetch(`${apiBaseUrl}${apiRoutes.auth.me}`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      ...(internalKey ? { 'X-Internal-API-Key': internalKey } : {}),
      ...(sessionToken ? { 'X-Session-Token': sessionToken } : {}),
    },
  })

  const payload = await response.json().catch(() => null)
  return NextResponse.json(payload ?? { message: 'Request failed' }, { status: response.status })
}
