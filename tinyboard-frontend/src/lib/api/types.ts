export type User = {
  id: number
  name: string
  email: string
  created_at?: string
}

export type Board = {
  id: number
  user_id: number
  name: string
  description?: string | null
  created_at: string
  updated_at: string
}

export type TaskStatus = 'todo' | 'in_progress' | 'completed' | 'blocked'

export type Task = {
  id: number
  board_id: number
  title: string
  description?: string | null
  status: TaskStatus
  position: number
  created_at: string
  updated_at: string
}

export type Comment = {
  id: number
  task_id: number
  body: string
  created_at: string
}

export type ApiKey = {
  id: number
  label: string
  created_at: string
  last_used_at?: string | null
  revoked_at?: string | null
}
