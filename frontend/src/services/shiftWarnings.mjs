const DAY = 24 * 60

const dateMinute = (date) => {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(date || ''))
  if (!match) return null
  const [, year, month, day] = match.map(Number)
  const value = Date.UTC(year, month - 1, day) / 60000
  const parsed = new Date(value * 60000)
  return parsed.getUTCFullYear() === year && parsed.getUTCMonth() === month - 1 && parsed.getUTCDate() === day ? value : null
}

const clockMinute = (value) => {
  const match = /^(?:[01]\d|2[0-3])[.:][0-5]\d$/.exec(String(value || ''))
  if (!match) return null
  const [hours, minutes] = value.replace('.', ':').split(':').map(Number)
  return hours * 60 + minutes
}

export const shiftInterval = (shift) => {
  const date = dateMinute(shift.date || shift.ven_date)
  const period = shift.time_period || shift.shift_period || shift.ven_time_text ||
    String(shift.shift_type || '').match(/\(([^)]+)\)/)?.[1]
  const parts = String(period || '').split(/\s*-\s*/)
  if (date === null || parts.length !== 2) return null
  const startClock = clockMinute(parts[0])
  const endClock = clockMinute(parts[1])
  if (startClock === null || endClock === null || startClock === endClock) return null
  const start = date + startClock
  const end = date + endClock + (endClock < startClock ? DAY : 0)
  return { start, end, startClock }
}

const sameId = (a, b) => String(a) === String(b)

export const getShiftWarnings = (candidate, schedules, { check24h = true, excludeIds = [] } = {}) => {
  const current = shiftInterval(candidate)
  if (!current || candidate.user_id == null) return []
  const peers = schedules.filter(shift =>
    sameId(shift.user_id, candidate.user_id) &&
    !excludeIds.some(id => sameId(id, shift.id ?? shift.ven_id)) &&
    ((candidate.id ?? candidate.ven_id) == null || !sameId(shift.id ?? shift.ven_id, candidate.id ?? candidate.ven_id))
  ).map(shift => ({ shift, interval: shiftInterval(shift) })).filter(item => item.interval)
  const warnings = []
  const date = candidate.date || candidate.ven_date
  if (peers.some(({ shift }) => (shift.date || shift.ven_date) === date &&
    candidate.ven_name_id != null && shift.ven_name_id != null && sameId(shift.ven_name_id, candidate.ven_name_id))) {
    warnings.push('sameShift')
  }
  if (peers.some(({ shift, interval }) => (shift.date || shift.ven_date) === date && interval.startClock === current.startClock)) {
    warnings.push('sameStart')
  }
  if (check24h) {
    const intervals = [...peers.map(item => item.interval), current].sort((a, b) => a.start - b.start)
    let groupStart = null
    let groupEnd = null
    let hasCandidate = false
    const completeGroup = () => {
      if (hasCandidate && groupEnd - groupStart >= DAY) warnings.push('consecutive24h')
    }
    for (const interval of intervals) {
      if (groupEnd === null || interval.start > groupEnd) {
        if (groupEnd !== null) completeGroup()
        groupStart = interval.start
        groupEnd = interval.end
        hasCandidate = interval === current
      } else {
        groupEnd = Math.max(groupEnd, interval.end)
        hasCandidate ||= interval === current
      }
    }
    if (groupEnd !== null) completeGroup()
  }
  return [...new Set(warnings)]
}

export const warningLabels = {
  sameShift: 'อยู่เวรเดียวกันในวันเดียวกัน',
  sameStart: 'เวลาเริ่มเวรตรงกันในวันเดียวกัน',
  consecutive24h: 'ปฏิบัติงานต่อเนื่องอย่างน้อย 24 ชั่วโมง'
}
