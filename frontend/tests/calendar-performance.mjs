import { readFileSync } from 'node:fs'
import { performance } from 'node:perf_hooks'
import { reactive } from 'vue'
import assert from 'node:assert/strict'
import { getShiftWarnings } from '../src/services/shiftWarnings.mjs'
import { createCalendarWarningLookup, groupCalendarDays } from '../src/services/calendarData.mjs'

const rows = reactive(JSON.parse(readFileSync(process.argv[2], 'utf8')))
const current = rows.filter(row => row.date.startsWith('2026-11'))
const oldRender = () => {
  for (let day = 1; day <= 30; day++) current.filter(row => row.date === `2026-11-${String(day).padStart(2, '0')}`)
  for (const row of current) for (let call = 0; call < 8; call++) getShiftWarnings(row, rows)
}
const newRender = () => {
  const days = groupCalendarDays(current)
  const lookup = createCalendarWarningLookup(rows)
  for (let day = 1; day <= 30; day++) days.get(`2026-11-${String(day).padStart(2, '0')}`)
  for (const row of current) for (let call = 0; call < 8; call++) lookup(row)
}
const lookup = createCalendarWarningLookup(rows)
for (const row of current) assert.deepEqual(lookup(row), getShiftWarnings(row, rows))
const measure = (fn) => {
  fn()
  const samples = Array.from({ length: 7 }, () => { const start = performance.now(); fn(); return performance.now() - start }).sort((a, b) => a - b)
  return samples[3]
}
const before = measure(oldRender), after = measure(newRender)
console.log(JSON.stringify({ context_rows: rows.length, visible_rows: current.length, before_warning_render_ms: Math.round(before * 100) / 100,
  after_warning_render_ms: Math.round(after * 100) / 100, speedup: Math.round(before / after * 10) / 10,
  note: 'Median of 7 runs with Vue reactive rows; warning computation and day grouping only, not full-page/network timing.' }, null, 2))
