import { loginUser, registerUser } from '../session'
import { proxyRoutes } from '../../constants/apiRoutes'

describe('session api', () => {
  beforeEach(() => {
    global.fetch = vi.fn().mockResolvedValue({
      ok: true,
      json: () => Promise.resolve({ data: { token: 'token', user: { id: 1, name: 'Sam', email: 's@t.com' } } }),
    }) as unknown as typeof fetch
  })

  it('posts to login proxy route', async () => {
    await loginUser({ email: 's@t.com', password: 'pass' })
    expect(global.fetch).toHaveBeenCalledWith(proxyRoutes.auth.login, expect.any(Object))
  })

  it('posts to register proxy route', async () => {
    await registerUser({ name: 'Sam', email: 's@t.com', password: 'pass', passwordConfirm: 'pass' })
    expect(global.fetch).toHaveBeenCalledWith(proxyRoutes.auth.register, expect.any(Object))
  })
})
