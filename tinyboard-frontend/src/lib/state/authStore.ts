export type AuthSession = {
  token: string
  user: {
    id: number
    name: string
    email: string
  }
}

const storageKey = 'my-board:session'
const eventName = 'my-board:session-change'
let cachedRaw: string | null = null
let cachedSession: AuthSession | null = null

export function getStoredSession(): AuthSession | null {
  if (typeof window === 'undefined') return null
  const raw = window.localStorage.getItem(storageKey)
  if (raw === cachedRaw) {
    return cachedSession
  }
  cachedRaw = raw
  if (!raw) {
    cachedSession = null
    return null
  }
  try {
    cachedSession = JSON.parse(raw) as AuthSession
    return cachedSession
  } catch {
    cachedSession = null
    return null
  }
}

export function getSessionSnapshot(): AuthSession | null {
  return getStoredSession()
}

export function getSessionServerSnapshot(): AuthSession | null {
  return null
}

export function subscribeToSession(callback: () => void) {
  if (typeof window === 'undefined') return () => {}
  const handler = () => callback()
  window.addEventListener('storage', handler)
  window.addEventListener(eventName, handler)
  return () => {
    window.removeEventListener('storage', handler)
    window.removeEventListener(eventName, handler)
  }
}

function emitSessionChange() {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new Event(eventName))
}

export function storeSession(session: AuthSession) {
  if (typeof window === 'undefined') return
  window.localStorage.setItem(storageKey, JSON.stringify(session))
  emitSessionChange()
}

export function updateSessionUser(user: AuthSession['user']) {
  const current = getStoredSession()
  if (!current) return
  storeSession({ ...current, user })
}

export function clearSession() {
  if (typeof window === 'undefined') return
  window.localStorage.removeItem(storageKey)
  emitSessionChange()
}
