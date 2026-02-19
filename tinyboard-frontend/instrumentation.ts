declare global {
  var __APP_CONFIG__: { apiBaseUrl?: string } | undefined
}

export const register = () => {
  globalThis.__APP_CONFIG__ = {
    apiBaseUrl: process.env.NEXT_PUBLIC_API_BASE_URL ?? process.env.API_BASE_URL ?? "",
  }
}
