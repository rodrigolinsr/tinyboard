import { formatInTimeZone } from 'date-fns-tz'

export function formatDate(dateString?: string | null) {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return ''
  return formatInTimeZone(date, 'UTC', 'dd/MM/yyyy')
}

export function formatDateTime(dateString?: string | null) {
  if (!dateString) return ''
  const date = new Date(dateString)
  if (Number.isNaN(date.getTime())) return ''
  return formatInTimeZone(date, 'UTC', 'dd/MM/yyyy HH:mm')
}
