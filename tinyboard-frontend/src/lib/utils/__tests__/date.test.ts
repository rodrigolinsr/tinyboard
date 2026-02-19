import { formatDate, formatDateTime } from '../date'

describe('date formatting', () => {
  it('formats date as DD/MM/YYYY', () => {
    expect(formatDate('2026-02-19T00:00:00Z')).toBe('19/02/2026')
  })

  it('formats date time as DD/MM/YYYY HH:mm', () => {
    expect(formatDateTime('2026-02-19T00:30:00Z')).toBe('19/02/2026 00:30')
  })
})
