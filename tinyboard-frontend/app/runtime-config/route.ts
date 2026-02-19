export const GET = () => {
  const config = {
    apiBaseUrl:
      globalThis.__APP_CONFIG__?.apiBaseUrl ??
      process.env.NEXT_PUBLIC_API_BASE_URL ??
      process.env.API_BASE_URL ??
      "",
  }

  return new Response(`window.__APP_CONFIG__ = ${JSON.stringify(config)};`, {
    headers: {
      "Content-Type": "application/javascript; charset=utf-8",
      "Cache-Control": "no-store, must-revalidate",
    },
  })
}
