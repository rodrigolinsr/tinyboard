export const apiRoutes = {
  auth: {
    register: '/auth/register',
    login: '/auth/login',
    logout: '/auth/logout',
    me: '/me',
  },
  boards: {
    list: '/boards',
    create: '/boards',
    update: (id: number) => `/boards/${id}`,
    remove: (id: number) => `/boards/${id}`,
    tasks: (boardId: number) => `/boards/${boardId}/tasks`,
    task: (boardId: number, taskId: number) => `/boards/${boardId}/tasks/${taskId}`,
    comments: (boardId: number, taskId: number) => `/boards/${boardId}/tasks/${taskId}/comments`,
    comment: (boardId: number, taskId: number, commentId: number) =>
      `/boards/${boardId}/tasks/${taskId}/comments/${commentId}`,
  },
  profile: {
    apiKeys: '/profile/api-keys',
    apiKey: (id: number) => `/profile/api-keys/${id}`,
    update: '/profile',
    password: '/profile/password',
  },
} as const

export const proxyRoutes = {
  auth: {
    register: '/api/auth/register',
    login: '/api/auth/login',
    logout: '/api/auth/logout',
    me: '/api/auth/me',
  },
} as const
