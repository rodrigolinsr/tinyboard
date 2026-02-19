"use client"

import { useEffect, useMemo, useState, useSyncExternalStore } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import {
  DndContext,
  DragEndEvent,
  DragOverlay,
  PointerSensor,
  closestCenter,
  useDroppable,
  useSensor,
  useSensors,
} from '@dnd-kit/core'
import { arrayMove, SortableContext, useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'
import {
  ArrowRight,
  CalendarDays,
  CheckCircle2,
  ClipboardCheck,
  ClipboardList,
  Flame,
  LayoutGrid,
  Loader2,
  MoreHorizontal,
  PanelLeft,
  Plus,
  ShieldCheck,
  Sparkles,
  UserRound,
  XCircle,
} from 'lucide-react'

import { Button } from '@/src/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/src/components/ui/card'
import { Badge } from '@/src/components/ui/badge'
import { Input } from '@/src/components/ui/input'
import { Textarea } from '@/src/components/ui/textarea'
import { Label } from '@/src/components/ui/label'
import { Separator } from '@/src/components/ui/separator'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/src/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/src/components/ui/dialog'
import { Tabs, TabsList, TabsTrigger } from '@/src/components/ui/tabs'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/src/components/ui/dropdown-menu'
import { Alert, AlertDescription, AlertTitle } from '@/src/components/ui/alert'
import { ScrollArea } from '@/src/components/ui/scroll-area'
import {
  createApiKey,
  createBoard,
  createComment,
  createTask,
  deleteBoard,
  deleteComment,
  deleteTask,
  changePassword,
  listApiKeys,
  listBoards,
  listComments,
  listTasks,
  revokeApiKey,
  updateBoard,
  updateTask,
  updateProfile,
} from '@/src/lib/api/api'
import type { Board, Task, TaskStatus } from '@/src/lib/api/types'
import { loginUser, registerUser } from '@/src/lib/api/session'
import {
  clearSession,
  getSessionServerSnapshot,
  getSessionSnapshot,
  storeSession,
  subscribeToSession,
  updateSessionUser,
  type AuthSession,
} from '@/src/lib/state/authStore'
import {
  getBoardServerSnapshot,
  getBoardSnapshot,
  storeActiveBoardId,
  subscribeToBoard,
} from '@/src/lib/state/boardStore'
import { formatDate, formatDateTime } from '@/src/lib/utils/date'
import { cn } from '@/lib/utils'

const statusConfig: Record<
  TaskStatus,
  { label: string; color: string; icon: React.ReactNode; hint: string; card: string; title: string }
> = {
  todo: {
    label: 'Todo',
    color: 'bg-zinc-100 text-zinc-600',
    icon: <ClipboardList className="h-4 w-4" />,
    hint: 'Fresh items waiting to be pulled.',
    card: 'bg-zinc-50/50',
    title: 'text-zinc-700',
  },
  in_progress: {
    label: 'In Progress',
    color: 'bg-amber-100 text-amber-700',
    icon: <Flame className="h-4 w-4" />,
    hint: 'Currently active and moving forward.',
    card: 'bg-amber-50/50',
    title: 'text-amber-800',
  },
  completed: {
    label: 'Completed',
    color: 'bg-emerald-100 text-emerald-700',
    icon: <CheckCircle2 className="h-4 w-4" />,
    hint: 'Wrapped up and ready to archive.',
    card: 'bg-emerald-50/50',
    title: 'text-emerald-800',
  },
  blocked: {
    label: 'Blocked',
    color: 'bg-rose-100 text-rose-700',
    icon: <XCircle className="h-4 w-4" />,
    hint: 'Needs attention before it can move.',
    card: 'bg-rose-50/50',
    title: 'text-rose-800',
  },
}

const statusOrder: TaskStatus[] = ['todo', 'in_progress', 'completed', 'blocked']

function useSession() {
  const session = useSyncExternalStore(
    subscribeToSession,
    getSessionSnapshot,
    getSessionServerSnapshot
  )

  const saveSession = (next: AuthSession) => {
    storeSession(next)
  }

  const clear = () => {
    clearSession()
  }

  return { session, saveSession, clear }
}

function AuthScreen({ onAuth }: { onAuth: (session: AuthSession) => void }) {
  const [mode, setMode] = useState<'login' | 'register'>('login')
  const [error, setError] = useState<string | null>(null)

  const loginMutation = useMutation({
    mutationFn: loginUser,
    onSuccess: (data) => {
      setError(null)
      onAuth({ token: data.token, user: data.user })
    },
    onError: (err: { message?: string }) => setError(err?.message ?? 'Unable to login.'),
  })

  const registerMutation = useMutation({
    mutationFn: registerUser,
    onSuccess: (_, variables) => {
      setError(null)
      loginMutation.mutate({ email: variables.email, password: variables.password })
    },
    onError: (err: { message?: string }) => setError(err?.message ?? 'Unable to register.'),
  })

  const isLoading = loginMutation.isPending || registerMutation.isPending

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    const formData = new FormData(event.currentTarget)
    if (mode === 'login') {
      loginMutation.mutate({
        email: String(formData.get('email')),
        password: String(formData.get('password')),
      })
    } else {
      registerMutation.mutate({
        name: String(formData.get('name')),
        email: String(formData.get('email')),
        password: String(formData.get('password')),
        passwordConfirm: String(formData.get('passwordConfirm')),
      })
    }
  }

  return (
    <div className="min-h-screen px-6 py-12 lg:px-14">
      <div className="mx-auto flex w-full max-w-6xl flex-col gap-10 lg:grid lg:grid-cols-[1.1fr_0.9fr]">
        <div className="flex flex-col gap-8 rounded-md border border-border/70 bg-gradient-to-br from-white/80 via-white/70 to-white/50 p-10 shadow-xl shadow-foreground/5">
          <div className="flex items-center gap-3 text-primary">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10">
              <Sparkles className="h-6 w-6" />
            </div>
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.3em] text-primary/60">TinyBoard</p>
              <h1 className="text-3xl font-semibold text-foreground">A kanban workspace built for focus.</h1>
            </div>
          </div>
          <p className="max-w-xl text-lg text-muted-foreground">
            Create boards, track progress, and keep OpenClaw automation aligned with your team in a single, elegant
            space.
          </p>
          <div className="grid gap-6 sm:grid-cols-2">
            {[
              {
                title: 'Boards that breathe',
                description: 'Switch between initiatives without losing context.',
                icon: <LayoutGrid className="h-5 w-5" />,
              },
              {
                title: 'Frictionless flow',
                description: 'Drag tasks between columns and keep status in sync.',
                icon: <ArrowRight className="h-5 w-5" />,
              },
              {
                title: 'Actionable detail',
                description: 'Capture notes and unblock work with threaded comments.',
                icon: <ClipboardCheck className="h-5 w-5" />,
              },
              {
                title: 'Secure access',
                description: 'Manage API keys per user without exposing internals.',
                icon: <ShieldCheck className="h-5 w-5" />,
              },
            ].map((item) => (
              <Card key={item.title} className="border-border/60 bg-white/70">
                <CardHeader className="pb-4">
                  <div className="flex items-center gap-3 text-primary">
        <div className="flex h-10 w-10 items-center justify-center rounded-md bg-primary/10">
                      {item.icon}
                    </div>
                    <CardTitle>{item.title}</CardTitle>
                  </div>
                </CardHeader>
                <CardContent>
                  <CardDescription>{item.description}</CardDescription>
                </CardContent>
              </Card>
            ))}
          </div>
        </div>
        <div className="flex items-center">
          <Card className="w-full max-w-lg border-border/70 bg-white/80 p-10">
            <CardHeader className="px-0 pb-6">
              <CardTitle>{mode === 'login' ? 'Welcome back' : 'Create your account'}</CardTitle>
              <CardDescription>
                {mode === 'login'
                  ? 'Pick up right where you left off.'
                  : 'Set up your workspace and invite OpenClaw.'}
              </CardDescription>
            </CardHeader>
            <CardContent className="px-0">
              <form className="space-y-5" onSubmit={handleSubmit}>
                {mode === 'register' && (
                  <div className="space-y-2">
                    <Label htmlFor="name">Name</Label>
                    <Input id="name" name="name" placeholder="Alex Morgan" required />
                  </div>
                )}
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input id="email" name="email" type="email" placeholder="you@company.com" required />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="password">Password</Label>
                  <Input id="password" name="password" type="password" required />
                </div>
                {mode === 'register' && (
                  <div className="space-y-2">
                    <Label htmlFor="passwordConfirm">Confirm password</Label>
                    <Input id="passwordConfirm" name="passwordConfirm" type="password" required />
                  </div>
                )}
                {error && (
                  <Alert variant="destructive">
                    <AlertTitle>Action needed</AlertTitle>
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}
                <Button type="submit" size="lg" className="w-full" disabled={isLoading}>
                  {isLoading && <Loader2 className="h-4 w-4 animate-spin" />}
                  {mode === 'login' ? 'Sign in' : 'Create account'}
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  className="w-full"
                  onClick={() => setMode(mode === 'login' ? 'register' : 'login')}
                >
                  {mode === 'login' ? 'Need an account? Register' : 'Already have an account? Sign in'}
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  )
}

function BoardCard({
  board,
  isActive,
  onSelect,
  onEdit,
  onDelete,
}: {
  board: Board
  isActive: boolean
  onSelect: () => void
  onEdit: () => void
  onDelete: () => void
}) {
  return (
    <div
      role="button"
      tabIndex={0}
      onClick={onSelect}
      onKeyDown={(event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault()
          onSelect()
        }
      }}
      className="group w-full cursor-pointer rounded-md border border-border/60 bg-white/80 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md"
    >
      <div className="flex items-start justify-between gap-3">
        <div className="space-y-1">
          <div className="flex items-center gap-2">
            <span className="text-sm font-semibold text-foreground">{board.name}</span>
            {isActive && <Badge>Active</Badge>}
          </div>
          <p className="text-xs text-muted-foreground">
            {board.description ? board.description : 'No description yet.'}
          </p>
        </div>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <span onClick={(event) => event.stopPropagation()}>
              <Button variant="ghost" size="icon" className="opacity-0 transition group-hover:opacity-100">
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </span>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuLabel>Board actions</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={(event) => {
                event.stopPropagation()
                onEdit()
              }}
            >
              Edit board
            </DropdownMenuItem>
            <DropdownMenuItem
              className="text-destructive focus:text-destructive"
              onClick={(event) => {
                event.stopPropagation()
                onDelete()
              }}
            >
              Delete board
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  )
}

function ColumnDrop({ id, children }: { id: string; children: React.ReactNode }) {
  const { setNodeRef, isOver } = useDroppable({ id })
  return (
    <div ref={setNodeRef} className={cn('h-full rounded-md transition', isOver && 'bg-primary/5 ring-2 ring-primary/20')}>
      {children}
    </div>
  )
}

function TaskCard({ task, onClick, dragging }: { task: Task; onClick?: () => void; dragging?: boolean }) {
  return (
    <div
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : -1}
      onClick={onClick}
      onKeyDown={(event) => {
        if (!onClick) return
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault()
          onClick()
        }
      }}
      className={cn(
        'relative w-full cursor-grab rounded-md border border-border/60 bg-white/90 p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md active:cursor-grabbing',
        dragging && 'opacity-60'
      )}
    >
      <span
        className={cn(
          'absolute right-3 top-3 h-2 w-2 rounded-full',
          task.status === 'todo' && 'bg-zinc-400',
          task.status === 'in_progress' && 'bg-amber-400',
          task.status === 'completed' && 'bg-emerald-500',
          task.status === 'blocked' && 'bg-rose-500'
        )}
        aria-hidden
      />
      <div className="flex items-center justify-between gap-3">
        <div>
          <p className="text-sm font-semibold text-foreground line-clamp-1">{task.title}</p>
          <p className="text-xs text-muted-foreground line-clamp-2">{task.description || 'No description yet.'}</p>
        </div>
      </div>
    </div>
  )
}

function SortableTaskCard({ task, onClick }: { task: Task; onClick: () => void }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: task.id })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  }

  return (
    <div ref={setNodeRef} style={style} {...attributes} {...listeners}>
      <TaskCard task={task} onClick={onClick} dragging={isDragging} />
    </div>
  )
}

export default function Home() {
  const { session, saveSession, clear } = useSession()
  const queryClient = useQueryClient()
  const storedBoardId = useSyncExternalStore(subscribeToBoard, getBoardSnapshot, getBoardServerSnapshot)
  const [activeBoardId, setActiveBoardId] = useState<number | null>(storedBoardId)
  const [boardModalOpen, setBoardModalOpen] = useState(false)
  const [editingBoard, setEditingBoard] = useState<Board | null>(null)
  const [taskModalOpen, setTaskModalOpen] = useState(false)
  const [activeTask, setActiveTask] = useState<Task | null>(null)
  const [activeDragId, setActiveDragId] = useState<number | null>(null)
  const [newTaskStatus, setNewTaskStatus] = useState<TaskStatus>('todo')
  const [newTaskBody, setNewTaskBody] = useState('')
  const [editTaskTitle, setEditTaskTitle] = useState('')
  const [editTaskDescription, setEditTaskDescription] = useState('')
  const [editTaskStatus, setEditTaskStatus] = useState<TaskStatus>('todo')
  const [apiKeyModalOpen, setApiKeyModalOpen] = useState(false)
  const [generatedKey, setGeneratedKey] = useState<string | null>(null)
  const [sidebarOpen, setSidebarOpen] = useState(true)
  const [profileModalOpen, setProfileModalOpen] = useState(false)
  const [taskPanel, setTaskPanel] = useState<'comments' | 'edit'>('comments')
  const [isHydrated, setIsHydrated] = useState(false)
  const [profileNotice, setProfileNotice] = useState<{ tone: 'success' | 'error'; message: string } | null>(null)
  const [passwordNotice, setPasswordNotice] = useState<{ tone: 'success' | 'error'; message: string } | null>(null)

  useEffect(() => {
    setIsHydrated(true)
  }, [])

  useEffect(() => {
    if (typeof window === 'undefined') return
    const stored = window.localStorage.getItem('my-board:sidebar-open')
    if (stored !== null) {
      setSidebarOpen(stored === 'true')
    }
  }, [])

  useEffect(() => {
    if (typeof window === 'undefined') return
    window.localStorage.setItem('my-board:sidebar-open', String(sidebarOpen))
  }, [sidebarOpen])

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 6 } }))

  const boardsQuery = useQuery({
    queryKey: ['boards', session?.token],
    queryFn: () => listBoards(session?.token ?? ''),
    enabled: Boolean(session?.token),
  })

  const boards = useMemo(() => boardsQuery.data?.boards ?? [], [boardsQuery.data?.boards])
  const resolvedBoardId = useMemo(() => {
    if (activeBoardId && boards.some((board) => board.id === activeBoardId)) {
      return activeBoardId
    }
    if (storedBoardId && boards.some((board) => board.id === storedBoardId)) {
      return storedBoardId
    }
    return boards[0]?.id ?? null
  }, [activeBoardId, storedBoardId, boards])

  const activeBoard = boards.find((board) => board.id === resolvedBoardId) ?? null

  const tasksQuery = useQuery({
    queryKey: ['tasks', resolvedBoardId ?? 0],
    queryFn: () => listTasks(session?.token ?? '', resolvedBoardId ?? 0),
    enabled: Boolean(session?.token && resolvedBoardId),
  })

  const tasks = useMemo(() => tasksQuery.data?.tasks ?? [], [tasksQuery.data?.tasks])

  const commentsQuery = useQuery({
    queryKey: ['comments', activeTask?.id],
    queryFn: () => listComments(session?.token ?? '', resolvedBoardId ?? 0, activeTask?.id ?? 0),
    enabled: Boolean(session?.token && resolvedBoardId && activeTask?.id),
  })

  const apiKeysQuery = useQuery({
    queryKey: ['api-keys', session?.token],
    queryFn: () => listApiKeys(session?.token ?? ''),
    enabled: Boolean(session?.token),
  })

  const groupedTasks = useMemo(() => {
    const groups: Record<TaskStatus, Task[]> = {
      todo: [],
      in_progress: [],
      completed: [],
      blocked: [],
    }
    tasks
      .slice()
      .sort((a, b) => a.position - b.position)
      .forEach((task) => {
        groups[task.status].push(task)
      })
    return groups
  }, [tasks])

  const handleBoardModalChange = (open: boolean) => {
    setBoardModalOpen(open)
    if (!open) setEditingBoard(null)
  }

  const handleResolvedBoardChange = (nextId: number | null) => {
    setActiveBoardId(nextId)
    storeActiveBoardId(nextId)
  }

  const openTaskEditor = (task: Task) => {
    setEditTaskTitle(task.title)
    setEditTaskDescription(task.description ?? '')
    setEditTaskStatus(task.status)
    setTaskPanel('comments')
    setActiveTask(task)
  }

  const createBoardMutation = useMutation({
    mutationFn: (payload: { name: string; description?: string | null }) =>
      createBoard(session?.token ?? '', payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['boards'] })
      setBoardModalOpen(false)
      setActiveBoardId(data.board.id)
      storeActiveBoardId(data.board.id)
    },
  })

  const updateBoardMutation = useMutation({
    mutationFn: (payload: { id: number; name: string; description?: string | null }) =>
      updateBoard(session?.token ?? '', payload.id, { name: payload.name, description: payload.description }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['boards'] })
      setBoardModalOpen(false)
      setEditingBoard(null)
    },
  })

  const deleteBoardMutation = useMutation({
    mutationFn: (id: number) => deleteBoard(session?.token ?? '', id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['boards'] })
      storeActiveBoardId(null)
      setActiveBoardId(null)
    },
  })

  const createTaskMutation = useMutation({
    mutationFn: (payload: { title: string; description?: string | null; status: TaskStatus }) =>
      createTask(session?.token ?? '', resolvedBoardId ?? 0, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', resolvedBoardId] })
      setTaskModalOpen(false)
    },
  })

  const updateTaskMutation = useMutation({
    mutationFn: (payload: { id: number; status?: TaskStatus; title?: string; description?: string | null; position?: number }) =>
      updateTask(session?.token ?? '', resolvedBoardId ?? 0, payload.id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', resolvedBoardId] })
    },
  })

  const deleteTaskMutation = useMutation({
    mutationFn: (id: number) => deleteTask(session?.token ?? '', resolvedBoardId ?? 0, id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['tasks', resolvedBoardId] })
      setActiveTask(null)
    },
  })

  const createCommentMutation = useMutation({
    mutationFn: (payload: { body: string }) =>
      createComment(session?.token ?? '', resolvedBoardId ?? 0, activeTask?.id ?? 0, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['comments', activeTask?.id] })
      setNewTaskBody('')
    },
  })

  const deleteCommentMutation = useMutation({
    mutationFn: (id: number) => deleteComment(session?.token ?? '', resolvedBoardId ?? 0, activeTask?.id ?? 0, id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['comments', activeTask?.id] })
    },
  })

  const createApiKeyMutation = useMutation({
    mutationFn: (payload: { label: string }) => createApiKey(session?.token ?? '', payload),
    onSuccess: (data) => {
      setGeneratedKey(data.key.plain)
      queryClient.invalidateQueries({ queryKey: ['api-keys'] })
      setApiKeyModalOpen(false)
    },
  })

  const revokeKeyMutation = useMutation({
    mutationFn: (id: number) => revokeApiKey(session?.token ?? '', id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['api-keys'] })
    },
  })

  const updateProfileMutation = useMutation({
    mutationFn: (payload: { name: string }) => updateProfile(session?.token ?? '', payload),
    onSuccess: (data) => {
      updateSessionUser(data.user)
      setProfileNotice({ tone: 'success', message: 'Name updated successfully.' })
    },
    onError: (err: { message?: string }) =>
      setProfileNotice({ tone: 'error', message: err?.message ?? 'Unable to update profile.' }),
  })

  const changePasswordMutation = useMutation({
    mutationFn: (payload: { currentPassword: string; password: string; passwordConfirm: string }) =>
      changePassword(session?.token ?? '', payload),
    onSuccess: () => {
      setPasswordNotice({ tone: 'success', message: 'Password updated. Use it next time you sign in.' })
    },
    onError: (err: { message?: string }) =>
      setPasswordNotice({ tone: 'error', message: err?.message ?? 'Unable to update password.' }),
  })

  const tasksCount = tasks.length
  const completedCount = tasks.filter((task) => task.status === 'completed').length
  const blockedCount = tasks.filter((task) => task.status === 'blocked').length
  const activeDragTask = tasks.find((task) => task.id === activeDragId) ?? null

  if (!isHydrated) {
    return (
      <div className="min-h-screen px-6 py-12 lg:px-14">
        <div className="mx-auto flex w-full max-w-3xl items-center justify-center">
          <Card className="w-full max-w-md border-border/70 bg-white/80 p-10">
            <CardHeader className="px-0 pb-4">
              <CardTitle>Loading your workspace</CardTitle>
              <CardDescription>Checking your session and preferences.</CardDescription>
            </CardHeader>
            <CardContent className="flex items-center gap-2 px-0 text-sm text-muted-foreground">
              <Loader2 className="h-4 w-4 animate-spin" /> Loading...
            </CardContent>
          </Card>
        </div>
      </div>
    )
  }

  if (!session) {
    return <AuthScreen onAuth={saveSession} />
  }

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event
    if (!over || !resolvedBoardId) return

    const activeId = active.id as number
    const overId = over.id as number | string

    const activeTaskItem = tasks.find((task) => task.id === activeId)
    if (!activeTaskItem) return

    const isColumnDrop = typeof overId === 'string' && overId.startsWith('column:')

    if (isColumnDrop) {
      const nextStatus = overId.replace('column:', '') as TaskStatus
      updateTaskMutation.mutate({ id: activeId, status: nextStatus, position: 0 })
      queryClient.setQueryData(['tasks', resolvedBoardId], (prev: { tasks: Task[] } | undefined) => {
        if (!prev) return prev
        return {
          tasks: prev.tasks.map((task) =>
            task.id === activeId ? { ...task, status: nextStatus, position: 0 } : task
          ),
        }
      })
      return
    }

    const overTask = tasks.find((task) => task.id === overId)
    if (!overTask) return

    if (activeTaskItem.status === overTask.status) {
      const columnTasks = groupedTasks[activeTaskItem.status]
      const oldIndex = columnTasks.findIndex((task) => task.id === activeTaskItem.id)
      const newIndex = columnTasks.findIndex((task) => task.id === overTask.id)
      const reordered = arrayMove(columnTasks, oldIndex, newIndex)
      const updated = tasks.map((task) => task)
      reordered.forEach((task, index) => {
        const target = updated.find((item) => item.id === task.id)
        if (target) target.position = index
      })
      queryClient.setQueryData(['tasks', resolvedBoardId], { tasks: updated })
      updateTaskMutation.mutate({ id: activeTaskItem.id, position: newIndex })
      return
    }

    updateTaskMutation.mutate({ id: activeTaskItem.id, status: overTask.status, position: 0 })
    queryClient.setQueryData(['tasks', resolvedBoardId], (prev: { tasks: Task[] } | undefined) => {
      if (!prev) return prev
      return {
        tasks: prev.tasks.map((task) =>
          task.id === activeTaskItem.id ? { ...task, status: overTask.status, position: 0 } : task
        ),
      }
    })
  }

  return (
    <div className="min-h-screen">
      <header className="sticky top-0 z-20 border-b border-border/60 bg-background/80 backdrop-blur">
        <div className="mx-auto flex w-full flex-col gap-5 px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-10">
          <div className="flex items-center gap-4">
            <div className="flex h-12 w-12 items-center justify-center rounded-md bg-primary/10 text-primary">
              <LayoutGrid className="h-5 w-5" />
            </div>
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.3em] text-primary/60">TinyBoard</p>
              <h2 className="text-2xl font-semibold">Welcome, {session.user.name}</h2>
              <p className="text-sm text-muted-foreground">Stay close to the flow of work.</p>
            </div>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <Button
              variant="outline"
              onClick={() => setSidebarOpen((open) => !open)}
              aria-label="Toggle sidebar"
            >
              <PanelLeft className="h-4 w-4" />
              {sidebarOpen ? 'Hide sidebar' : 'Show sidebar'}
            </Button>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  <UserRound className="h-4 w-4" />
                  {session.user.email}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuLabel>Account</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={() => setProfileModalOpen(true)}>Profile</DropdownMenuItem>
                <DropdownMenuItem
                  onClick={() => {
                    clear()
                  }}
                >
                  Sign out
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </header>

      <main
        className={cn(
          'mx-auto grid w-full gap-8 px-4 py-10 sm:px-6 xl:gap-10 xl:px-10',
          sidebarOpen ? 'xl:grid-cols-[320px_minmax(0,1fr)]' : 'xl:grid-cols-1'
        )}
      >
        {sidebarOpen && (
          <aside className="space-y-8 lg:sticky lg:top-28 lg:self-start" aria-label="Boards sidebar">
          <Card className="bg-white/80">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <LayoutGrid className="h-5 w-5 text-primary" />
                Boards
              </CardTitle>
              <CardDescription>Switch context without losing momentum.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-5">
              {boardsQuery.isLoading ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" /> Loading boards...
                </div>
              ) : boards.length === 0 ? (
                <div className="rounded-md border border-dashed border-border/70 bg-white/70 p-4 text-sm text-muted-foreground">
                  No boards yet. Create your first board to get moving.
                </div>
              ) : (
                <div className="space-y-4">
                  {boards.map((board) => (
                    <BoardCard
                      key={board.id}
                      board={board}
                      isActive={board.id === resolvedBoardId}
                      onSelect={() => {
                        handleResolvedBoardChange(board.id)
                      }}
                      onEdit={() => {
                        setEditingBoard(board)
                        setBoardModalOpen(true)
                      }}
                      onDelete={() => deleteBoardMutation.mutate(board.id)}
                    />
                  ))}
                </div>
              )}
              <Button className="w-full" onClick={() => setBoardModalOpen(true)}>
                <Plus className="h-4 w-4" /> New board
              </Button>
            </CardContent>
          </Card>
          <Card className="bg-white/70">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <CalendarDays className="h-5 w-5 text-primary" /> Overview
              </CardTitle>
              <CardDescription>Snapshot of the selected board.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Total tasks</span>
                <span className="font-semibold text-foreground">{tasksCount}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Completed</span>
                <span className="font-semibold text-foreground">{completedCount}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Blocked</span>
                <span className="font-semibold text-foreground">{blockedCount}</span>
              </div>
            </CardContent>
          </Card>
          </aside>
        )}

        <section className="space-y-7">
          <Card className="bg-white/80">
            <CardHeader>
              <CardTitle className="flex items-center gap-2 text-lg">
                <Sparkles className="h-5 w-5 text-primary" /> {activeBoard?.name ?? 'Select a board'}
              </CardTitle>
              <CardDescription>{activeBoard?.description ?? 'Choose a board to see the flow.'}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap items-center gap-4">
                <Button onClick={() => setTaskModalOpen(true)} disabled={!resolvedBoardId}>
                  <Plus className="h-4 w-4" /> Add task
                </Button>
                <Button
                  variant="outline"
                  onClick={() => {
                    if (!activeBoard) return
                    setEditingBoard(activeBoard)
                    setBoardModalOpen(true)
                  }}
                  disabled={!activeBoard}
                >
                  Edit board
                </Button>
              </div>
            </CardContent>
          </Card>

          {tasksQuery.isLoading ? (
            <Card className="bg-white/80">
              <CardContent className="flex items-center gap-2 py-10 text-sm text-muted-foreground">
                <Loader2 className="h-4 w-4 animate-spin" /> Loading tasks...
              </CardContent>
            </Card>
          ) : !resolvedBoardId ? (
            <Card className="bg-white/80">
              <CardContent className="py-12 text-sm text-muted-foreground">
                Select a board on the left to start organizing tasks.
              </CardContent>
            </Card>
          ) : (
            <DndContext
              sensors={sensors}
              collisionDetection={closestCenter}
              onDragStart={(event) => setActiveDragId(event.active.id as number)}
              onDragCancel={() => setActiveDragId(null)}
              onDragEnd={(event) => {
                handleDragEnd(event)
                setActiveDragId(null)
              }}
            >
              <div className="grid gap-6 xl:grid-cols-4">
                {statusOrder.map((status) => (
                  <ColumnDrop key={status} id={`column:${status}`}>
                    <Card className={cn('h-full', statusConfig[status].card)}>
                      <CardHeader className="pb-3">
                        <div className="flex items-start justify-between gap-4">
                          <div className="space-y-2">
                            <div className="flex items-center gap-2">
                              <p className={cn('text-sm font-semibold', statusConfig[status].title)}>
                                {statusConfig[status].label}
                              </p>
                              <Badge variant="secondary" className="px-2.5 text-xs">
                                {groupedTasks[status].length}
                              </Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">{statusConfig[status].hint}</p>
                          </div>
                          <div
                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${statusConfig[status].color}`}
                          >
                            {statusConfig[status].icon}
                          </div>
                        </div>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <SortableContext items={groupedTasks[status].map((task) => task.id)}>
                          {groupedTasks[status].length ? (
                            groupedTasks[status].map((task) => (
                              <SortableTaskCard
                                key={task.id}
                                task={task}
                                    onClick={() => {
                                      openTaskEditor(task)
                                    }}
                              />
                            ))
                          ) : (
                            <div className="rounded-md border border-dashed border-border/70 bg-white/70 p-4 text-xs text-muted-foreground">
                              Drag a task here or create a new one.
                            </div>
                          )}
                        </SortableContext>
                      </CardContent>
                    </Card>
                  </ColumnDrop>
                ))}
              </div>
              <DragOverlay>
                {activeDragTask ? (
                  <div className="w-[280px]">
                    <TaskCard task={activeDragTask} dragging />
                  </div>
                ) : null}
              </DragOverlay>
            </DndContext>
          )}
        </section>
      </main>

      <Dialog open={boardModalOpen} onOpenChange={handleBoardModalChange}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{editingBoard ? 'Edit board' : 'Create board'}</DialogTitle>
            <DialogDescription>Give your board a name and optional description.</DialogDescription>
          </DialogHeader>
          <form
            className="space-y-5"
            onSubmit={(event) => {
              event.preventDefault()
              const formData = new FormData(event.currentTarget)
              const name = String(formData.get('name'))
              const description = String(formData.get('description') ?? '')
              if (editingBoard) {
                updateBoardMutation.mutate({ id: editingBoard.id, name, description })
              } else {
                createBoardMutation.mutate({ name, description })
              }
            }}
          >
            <div className="space-y-3">
              <Label htmlFor="board-name">Board name</Label>
              <Input id="board-name" name="name" defaultValue={editingBoard?.name ?? ''} required />
            </div>
            <div className="space-y-3">
              <Label htmlFor="board-description">Description</Label>
              <Textarea
                id="board-description"
                name="description"
                defaultValue={editingBoard?.description ?? ''}
                placeholder="Optional context"
              />
            </div>
            <DialogFooter>
              <Button type="submit">{editingBoard ? 'Save changes' : 'Create board'}</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={taskModalOpen} onOpenChange={setTaskModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create a new task</DialogTitle>
            <DialogDescription>Add detail now to avoid context switching later.</DialogDescription>
          </DialogHeader>
          <form
            className="space-y-4"
            onSubmit={(event) => {
              event.preventDefault()
              const formData = new FormData(event.currentTarget)
              createTaskMutation.mutate({
                title: String(formData.get('title')),
                description: String(formData.get('description') ?? ''),
                status: newTaskStatus,
              })
            }}
          >
            <div className="space-y-3">
              <Label htmlFor="task-title">Title</Label>
              <Input id="task-title" name="title" placeholder="Ship onboarding" required />
            </div>
            <div className="space-y-2">
              <Label htmlFor="task-description">Description</Label>
              <Textarea id="task-description" name="description" placeholder="Add context for the team" />
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={newTaskStatus} onValueChange={(value) => setNewTaskStatus(value as TaskStatus)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select status" />
                </SelectTrigger>
                <SelectContent>
                  {statusOrder.map((status) => (
                    <SelectItem key={status} value={status}>
                      {statusConfig[status].label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <DialogFooter>
              <Button type="submit">Add task</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog
        open={Boolean(activeTask)}
        onOpenChange={(open) => {
          if (!open) {
            setActiveTask(null)
            setTaskPanel('comments')
          }
        }}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{activeTask?.title ?? 'Task detail'}</DialogTitle>
            <DialogDescription>Review activity or edit task details.</DialogDescription>
          </DialogHeader>
          <div className="flex flex-wrap items-center justify-between gap-3">
            <Tabs value={taskPanel} onValueChange={(value) => setTaskPanel(value as 'comments' | 'edit')}>
              <TabsList>
                <TabsTrigger value="comments">Comments</TabsTrigger>
                <TabsTrigger value="edit">Edit details</TabsTrigger>
              </TabsList>
            </Tabs>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => activeTask && deleteTaskMutation.mutate(activeTask.id)}
            >
              Delete task
            </Button>
          </div>
          <Separator className="mt-2" />
          {taskPanel === 'comments' ? (
            <div className="space-y-4 pt-2">
              <div className="space-y-2">
                <p className="text-sm font-semibold">Comments</p>
                <ScrollArea className="h-48 rounded-md border border-border/70 bg-white/70 p-3">
                  <div className="space-y-3">
                    {commentsQuery.data?.comments?.length ? (
                      commentsQuery.data.comments.map((comment) => (
                        <div key={comment.id} className="rounded-md border border-border/60 bg-white/80 p-3">
                          <p className="text-xs font-semibold text-muted-foreground">{comment.author_display}</p>
                          <p className="mt-2 text-sm text-foreground">{comment.body}</p>
                          <div className="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                            <span>{formatDateTime(comment.created_at)}</span>
                            <button
                              className="text-destructive"
                              onClick={() => deleteCommentMutation.mutate(comment.id)}
                            >
                              Remove
                            </button>
                          </div>
                        </div>
                      ))
                    ) : (
                      <p className="text-xs text-muted-foreground">No comments yet.</p>
                    )}
                  </div>
                </ScrollArea>
              </div>
              <form
                className="flex flex-col gap-3"
                onSubmit={(event) => {
                  event.preventDefault()
                  if (!newTaskBody.trim()) return
                  createCommentMutation.mutate({ body: newTaskBody.trim() })
                }}
              >
                <Textarea
                  value={newTaskBody}
                  onChange={(event) => setNewTaskBody(event.target.value)}
                  placeholder="Add a comment here"
                />
                <div className="flex justify-end">
                  <Button type="submit">Add comment</Button>
                </div>
              </form>
            </div>
          ) : (
            <form
              className="space-y-5 pt-2"
              onSubmit={(event) => {
                event.preventDefault()
                if (!activeTask) return
                updateTaskMutation.mutate({
                  id: activeTask.id,
                  title: editTaskTitle.trim(),
                  description: editTaskDescription.trim(),
                  status: editTaskStatus,
                })
                setTaskPanel('comments')
              }}
            >
              <div className="space-y-3">
                <Label htmlFor="edit-task-title">Title</Label>
                <Input
                  id="edit-task-title"
                  value={editTaskTitle}
                  onChange={(event) => setEditTaskTitle(event.target.value)}
                  required
                />
              </div>
              <div className="space-y-3">
                <Label htmlFor="edit-task-description">Description</Label>
                <Textarea
                  id="edit-task-description"
                  value={editTaskDescription}
                  onChange={(event) => setEditTaskDescription(event.target.value)}
                />
              </div>
              <div className="space-y-3">
                <Label>Status</Label>
                <Select value={editTaskStatus} onValueChange={(value) => setEditTaskStatus(value as TaskStatus)}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    {statusOrder.map((status) => (
                      <SelectItem key={status} value={status}>
                        {statusConfig[status].label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <DialogFooter>
                <Button type="submit" disabled={updateTaskMutation.isPending}>
                  {updateTaskMutation.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
                  Save changes
                </Button>
              </DialogFooter>
            </form>
          )}
        </DialogContent>
      </Dialog>

      <Dialog open={apiKeyModalOpen} onOpenChange={setApiKeyModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Create API key</DialogTitle>
            <DialogDescription>Use labels to remember where each key is used.</DialogDescription>
          </DialogHeader>
          <form
            className="space-y-5"
            onSubmit={(event) => {
              event.preventDefault()
              const formData = new FormData(event.currentTarget)
              createApiKeyMutation.mutate({ label: String(formData.get('label')) })
            }}
          >
            <div className="space-y-3">
              <Label htmlFor="api-label">Label</Label>
              <Input id="api-label" name="label" placeholder="OpenClaw agent" required />
            </div>
            <DialogFooter>
              <Button type="submit">Generate key</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog
        open={profileModalOpen}
        onOpenChange={(open) => {
          setProfileModalOpen(open)
          if (!open) {
            setProfileNotice(null)
            setPasswordNotice(null)
          }
        }}
      >
        <DialogContent className="max-w-3xl">
          <DialogHeader>
            <DialogTitle>Profile</DialogTitle>
            <DialogDescription>Keep your personal details and access secure.</DialogDescription>
          </DialogHeader>
          <div className="space-y-6">
            {profileNotice && (
              <Alert variant={profileNotice.tone === 'error' ? 'destructive' : 'default'}>
                <AlertTitle>{profileNotice.tone === 'error' ? 'Action needed' : 'Profile updated'}</AlertTitle>
                <AlertDescription>{profileNotice.message}</AlertDescription>
              </Alert>
            )}
            <form
              className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]"
              onSubmit={(event) => {
                event.preventDefault()
                const formData = new FormData(event.currentTarget)
                const name = String(formData.get('name') ?? '').trim()
                if (!name || name === session.user.name) {
                  setProfileNotice({
                    tone: 'error',
                    message: name ? 'Enter a new name to update.' : 'Name is required.',
                  })
                  return
                }
                setProfileNotice(null)
                updateProfileMutation.mutate({ name })
              }}
            >
              <div className="space-y-2">
                <Label htmlFor="profile-name">Full name</Label>
                <Input id="profile-name" name="name" defaultValue={session.user.name} required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="profile-email">Email</Label>
                <Input
                  id="profile-email"
                  name="email"
                  type="email"
                  value={session.user.email}
                  disabled
                  readOnly
                />
                <p className="text-xs text-muted-foreground">Email changes are managed by your workspace admin.</p>
              </div>
              <div className="flex justify-end lg:col-span-2">
                <Button type="submit" disabled={updateProfileMutation.isPending}>
                  {updateProfileMutation.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
                  Save changes
                </Button>
              </div>
            </form>
            <Separator />
            {passwordNotice && (
              <Alert variant={passwordNotice.tone === 'error' ? 'destructive' : 'default'}>
                <AlertTitle>{passwordNotice.tone === 'error' ? 'Action needed' : 'Password updated'}</AlertTitle>
                <AlertDescription>{passwordNotice.message}</AlertDescription>
              </Alert>
            )}
            <form
              className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)]"
              onSubmit={(event) => {
                event.preventDefault()
                const formData = new FormData(event.currentTarget)
                const currentPassword = String(formData.get('currentPassword') ?? '')
                const password = String(formData.get('password') ?? '')
                const passwordConfirm = String(formData.get('passwordConfirm') ?? '')
                if (!currentPassword || !password || !passwordConfirm) {
                  setPasswordNotice({ tone: 'error', message: 'Fill out all password fields.' })
                  return
                }
                if (password !== passwordConfirm) {
                  setPasswordNotice({ tone: 'error', message: 'New password confirmation does not match.' })
                  return
                }
                setPasswordNotice(null)
                changePasswordMutation.mutate({ currentPassword, password, passwordConfirm })
                event.currentTarget.reset()
              }}
            >
              <div className="space-y-2">
                <Label htmlFor="profile-current-password">Current password</Label>
                <Input id="profile-current-password" name="currentPassword" type="password" required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="profile-new-password">New password</Label>
                <Input id="profile-new-password" name="password" type="password" required />
              </div>
              <div className="space-y-2">
                <Label htmlFor="profile-confirm-password">Confirm password</Label>
                <Input id="profile-confirm-password" name="passwordConfirm" type="password" required />
              </div>
              <div className="flex justify-end lg:col-span-3">
                <Button type="submit" disabled={changePasswordMutation.isPending}>
                  {changePasswordMutation.isPending && <Loader2 className="h-4 w-4 animate-spin" />}
                  Update password
                </Button>
              </div>
            </form>
            <Separator />
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-semibold">API keys</p>
                  <p className="text-xs text-muted-foreground">Generate and revoke keys for external automation.</p>
                </div>
                <Button onClick={() => setApiKeyModalOpen(true)}>
                  <Plus className="h-4 w-4" /> Create key
                </Button>
              </div>
              {generatedKey && (
                <Alert>
                  <AlertTitle>New API key</AlertTitle>
                  <AlertDescription>
                    Copy this key now. It will not be shown again.
                    <div className="mt-3 rounded-md border border-border/70 bg-white/80 p-3 font-mono text-xs">
                      {generatedKey}
                    </div>
                  </AlertDescription>
                </Alert>
              )}
              {apiKeysQuery.isLoading ? (
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  <Loader2 className="h-4 w-4 animate-spin" /> Loading API keys...
                </div>
              ) : apiKeysQuery.data?.keys?.length ? (
                <div className="space-y-3">
                  {apiKeysQuery.data.keys.map((key) => (
                    <Card key={key.id} className="border-border/60 bg-white/80">
                      <CardContent className="flex flex-wrap items-center justify-between gap-4 pt-6">
                        <div>
                          <p className="text-sm font-semibold">{key.label}</p>
                          <p className="text-xs text-muted-foreground">
                            Created {formatDate(key.created_at)}
                            {key.last_used_at ? ` • Last used ${formatDateTime(key.last_used_at)}` : ''}
                          </p>
                        </div>
                        <Button
                          variant="outline"
                          onClick={() => revokeKeyMutation.mutate(key.id)}
                          className="text-destructive"
                        >
                          Revoke
                        </Button>
                      </CardContent>
                    </Card>
                  ))}
                </div>
              ) : (
                <div className="rounded-md border border-dashed border-border/70 bg-white/70 p-4 text-sm text-muted-foreground">
                  No API keys yet. Create one to connect OpenClaw.
                </div>
              )}
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  )
}
