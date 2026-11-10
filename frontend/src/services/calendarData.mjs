import { getShiftWarnings } from './shiftWarnings.mjs'

const warningKey = (shift) => JSON.stringify([
  String(shift.id ?? shift.ven_id ?? ''), String(shift.user_id ?? ''),
  shift.date || shift.ven_date, String(shift.ven_name_id ?? ''),
  shift.time_period || shift.shift_period || shift.ven_time_text || shift.shift_type
])

// Compute each row once against its owner's shifts, then reuse it throughout rendering.
// Called from a Vue computed value so peer/date/period changes invalidate the whole index.
export const createCalendarWarningLookup = (schedules, { check24h = true } = {}) => {
  const users = new Map()
  for (const shift of schedules) {
    const user = String(shift.user_id)
    if (!users.has(user)) users.set(user, [])
    users.get(user).push(shift)
  }
  const warnings = new Map()
  for (const shift of schedules) {
    warnings.set(warningKey(shift), getShiftWarnings(shift, users.get(String(shift.user_id)), { check24h }))
  }
  // Edited transfer candidates/exclusions must be evaluated against current peers, not
  // the cached result for the original duty. Never retain this cache across data loads.
  return (shift, excludeIds = []) => {
    const key = warningKey(shift)
    return !excludeIds.length && warnings.has(key) ? warnings.get(key) :
      getShiftWarnings(shift, users.get(String(shift.user_id)) || [], { check24h, excludeIds })
  }
}

export const loadCalendarMonths = async (fetchMonth, month) => {
  const [year, number] = month.split('-').map(Number)
  const adjacent = [-1, 1].map(offset =>
    new Date(Date.UTC(year, number - 1 + offset, 1)).toISOString().slice(0, 7)
  )
  const months = await Promise.all([month, ...adjacent].map(fetchMonth))
  const [schedules] = months
  return { schedules, context: months.flat() }
}

export const groupCalendarDays = (schedules) => {
  const days = new Map()
  for (const shift of schedules) {
    if (!days.has(shift.date)) days.set(shift.date, [])
    days.get(shift.date).push(shift)
  }
  return days
}
