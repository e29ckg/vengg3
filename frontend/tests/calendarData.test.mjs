import test from 'node:test'
import assert from 'node:assert/strict'
import { computed, reactive } from 'vue'
import { createCalendarWarningLookup, groupCalendarDays, loadCalendarMonths } from '../src/services/calendarData.mjs'
import { getShiftWarnings } from '../src/services/shiftWarnings.mjs'

const duty = (id, date, period, user = 'u1', name = 1) => ({ id, date, time_period: period, user_id: user, ven_name_id: name })

test('calendar index preserves warnings across month boundaries, duplicate starts and edited transfers', () => {
  const rows = [
    duty(1, '2026-10-31', '16.30-08.30'), duty('2', '2026-11-01', '08.30-16.30'),
    duty(3, '2026-11-01', '08.30-12.00'), duty(4, '2026-11-01', '16.30-20.30', 'u2'),
    duty(5, '2026-11-30', '16.30-08.30'), duty(6, '2026-12-01', '08.30-16.30'),
    duty(7, '2026-11-01', 'bad'), { ...duty(8, 'bad', '08.30-16.30'), user_id: null }
  ]
  for (const check24h of [true, false]) {
    const lookup = createCalendarWarningLookup(rows, { check24h })
    for (const candidate of [...rows, { ...rows[1], id: undefined, ven_id: '2' }, { ...rows[1], user_id: 'u2' }, { ...rows[1], time_period: '16.30-20.30' }]) {
      for (const excludeIds of [[], [1, '3'], ['2']]) {
        assert.deepEqual(lookup(candidate, excludeIds), getShiftWarnings(candidate, rows, { check24h, excludeIds }))
      }
    }
  }
})

test('computed warning index invalidates when a peer period/owner or setting changes', () => {
  const state = reactive({ rows: [duty(1, '2026-11-01', '08.30-16.30'), duty(2, '2026-11-01', '16.30-08.30')], check24h: true })
  const lookup = computed(() => createCalendarWarningLookup(state.rows, { check24h: state.check24h }))
  assert.ok(lookup.value(state.rows[0]).includes('consecutive24h'))
  state.rows[1].time_period = '16.30-20.30'
  assert.ok(!lookup.value(state.rows[0]).includes('consecutive24h'))
  state.rows[1].time_period = '08.30-16.30'
  assert.ok(lookup.value(state.rows[0]).includes('sameStart'))
  state.rows[1].user_id = 'u2'
  assert.deepEqual(lookup.value(state.rows[0]), [])
  state.rows = [duty(1, '2026-11-01', '08.30-16.30'), duty(2, '2026-11-01', '16.30-08.30')]
  state.check24h = false
  assert.ok(!lookup.value(state.rows[0]).includes('consecutive24h'))
})

test('randomized warning results remain identical to the uncached rules', () => {
  let seed = 42
  const random = (max) => { seed = (Math.imul(seed, 1664525) + 1013904223) >>> 0; return seed % max }
  const periods = ['08.30-16.30', '16.30-08.30', '16.30-20.30', '08.30-12.00', '12.00-16.30']
  const rows = Array.from({ length: 180 }, (_, i) => duty(i, `2026-11-${String(random(30) + 1).padStart(2, '0')}`, periods[random(periods.length)], String(random(12)), random(4)))
  const lookup = createCalendarWarningLookup(rows)
  for (const row of rows) assert.deepEqual(lookup(row), getShiftWarnings(row, rows))
})

test('month requests start together and preserve month order even if responses finish out of order', async () => {
  const requests = new Map()
  const promise = loadCalendarMonths(month => new Promise(resolve => requests.set(month, resolve)), '2027-01')
  assert.deepEqual([...requests.keys()], ['2027-01', '2026-12', '2027-02'])
  requests.get('2027-02')([{ id: 3 }]); requests.get('2026-12')([{ id: 2 }]); requests.get('2027-01')([{ id: 1 }])
  assert.deepEqual(await promise, { schedules: [{ id: 1 }], context: [{ id: 1 }, { id: 2 }, { id: 3 }] })
})

test('failed neighbour request rejects the load instead of silently dropping cross-month warnings', async () => {
  await assert.rejects(loadCalendarMonths(month => month === '2026-12' ? Promise.reject(new Error('unavailable')) : Promise.resolve([]), '2026-11'), /unavailable/)
})

test('grouping days preserves database ordering and empty day lookups', () => {
  const rows = [duty(2, '2026-11-01', '08.30-16.30'), duty(1, '2026-11-01', '16.30-08.30')]
  const days = groupCalendarDays(rows)
  assert.deepEqual(days.get('2026-11-01'), rows)
  assert.equal(days.get('2026-11-02'), undefined)
})
