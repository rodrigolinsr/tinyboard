const storageKey = 'my-board:active-board'
const eventName = 'my-board:active-board-change'
let cachedRaw: string | null = null
let cachedValue: number | null = null

export function getStoredBoardId(): number | null {
  if (typeof window === 'undefined') return null
  const raw = window.localStorage.getItem(storageKey)
  if (raw === cachedRaw) {
    return cachedValue
  }
  cachedRaw = raw
  if (!raw) {
    cachedValue = null
    return null
  }
  const parsed = Number(raw)
  cachedValue = Number.isNaN(parsed) ? null : parsed
  return cachedValue
}

export function getBoardSnapshot(): number | null {
  return getStoredBoardId()
}

export function getBoardServerSnapshot(): number | null {
  return null
}

export function subscribeToBoard(callback: () => void) {
  if (typeof window === 'undefined') return () => {}
  const handler = () => callback()
  window.addEventListener('storage', handler)
  window.addEventListener(eventName, handler)
  return () => {
    window.removeEventListener('storage', handler)
    window.removeEventListener(eventName, handler)
  }
}

function emitBoardChange() {
  if (typeof window === 'undefined') return
  window.dispatchEvent(new Event(eventName))
}

export function storeActiveBoardId(id: number | null) {
  if (typeof window === 'undefined') return
  if (id === null) {
    window.localStorage.removeItem(storageKey)
  } else {
    window.localStorage.setItem(storageKey, String(id))
  }
  cachedRaw = window.localStorage.getItem(storageKey)
  cachedValue = id
  emitBoardChange()
}
