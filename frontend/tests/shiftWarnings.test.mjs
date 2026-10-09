import test from 'node:test'
import assert from 'node:assert/strict'
import { getShiftWarnings, shiftInterval } from '../src/services/shiftWarnings.mjs'

const shift = (id, date, time_period, ven_name_id = 1) => ({
  id, user_id: 'staff-1', date, time_period, ven_name_id
})

test('same shift and same start on one day have separate warnings', () => {
  const existing = shift(1, '2026-10-09', '08.30-16.30')
  assert.deepEqual(getShiftWarnings(shift(2, '2026-10-09', '08.30-12.30'), [existing]),
    ['sameShift', 'sameStart'])
  assert.deepEqual(getShiftWarnings(shift(2, '2026-10-09', '08.30-12.30', 2), [existing]),
    ['sameStart'])
  assert.deepEqual(getShiftWarnings(shift(2, '2026-10-10', '08.30-12.30'), [existing]), [])
})

test('continuous 24 hours across days and months', () => {
  const night = shift(1, '2026-10-31', '16.30-08.30')
  const day = shift(2, '2026-11-01', '08.30-16.30', 2)
  assert.deepEqual(getShiftWarnings(day, [night]), ['consecutive24h'])
  assert.deepEqual(getShiftWarnings(night, [day]), ['consecutive24h'])
  assert.deepEqual(getShiftWarnings(shift(3, '2026-11-01', '08.31-16.31', 2), [night]), [])
})

test('a same-day chain reaching midnight is 24 hours', () => {
  const morning = shift(1, '2026-10-09', '00.00-12.00')
  const evening = shift(2, '2026-10-09', '12.00-00.00', 2)
  assert.deepEqual(getShiftWarnings(evening, [morning]), ['consecutive24h'])
  assert.deepEqual(getShiftWarnings(evening, [{ ...morning, user_id: 'staff-2' }]), [])
})

test('overlap is counted once and a gap breaks the chain', () => {
  const day = shift(1, '2026-10-09', '08.30-16.30')
  const overlap = shift(2, '2026-10-09', '16.00-20.00', 2)
  assert.deepEqual(getShiftWarnings(overlap, [day]), [])
  const night = shift(3, '2026-10-09', '20.00-08.30', 3)
  assert.deepEqual(getShiftWarnings(night, [day, overlap]), ['consecutive24h'])
})

test('moving a shift excludes its old schedule and respects the 24h toggle', () => {
  const original = shift(1, '2026-10-09', '08.30-16.30')
  const next = shift(2, '2026-10-10', '16.30-08.30', 2)
  const moved = shift(1, '2026-10-10', '08.30-16.30')
  assert.deepEqual(getShiftWarnings(moved, [original, next], { excludeIds: [1] }), ['consecutive24h'])
  assert.deepEqual(getShiftWarnings(moved, [original, next], { excludeIds: [1], check24h: false }), [])
  assert.equal(shiftInterval(shift(1, '2026-10-09', 'bad')), null)
})
