<template>
  <div class="container py-4" style="max-width: 900px">
    <div class="card shadow-sm">
      <div class="card-header bg-dark text-white py-3"><h5 class="mb-0"><i class="bi bi-cloud-arrow-down me-2"></i>อัปเดตโปรแกรม</h5></div>
      <div class="card-body">
        <p>ตรวจและดึงเวอร์ชันจาก <a href="https://github.com/e29ckg/vengg3" target="_blank" rel="noopener">GitHub e29ckg/vengg3</a> สาขา main</p>
        <p class="text-muted">รองรับ XAMPP บน Windows ที่ติดตั้งจาก Git ต้องมี Git และ Composer ใน PATH การอัปเดตนี้เปลี่ยนไฟล์โปรแกรมและ dependencies โดยไม่เรียกนำเข้า SQL</p>
        <div v-if="error" class="alert alert-danger" role="alert">{{ error }}</div>
        <dl v-if="version" class="row">
          <dt class="col-sm-4">เวอร์ชันติดตั้ง</dt><dd class="col-sm-8"><code>{{ version.current.slice(0, 7) }}</code></dd>
          <dt class="col-sm-4">เวอร์ชันล่าสุด</dt><dd class="col-sm-8"><code>{{ version.latest.slice(0, 7) }}</code></dd>
          <dt class="col-sm-4">สถานะ</dt><dd class="col-sm-8">{{ version.available ? 'มีเวอร์ชันใหม่' : 'เป็นเวอร์ชันล่าสุดแล้ว' }}</dd>
        </dl>
        <div v-if="version?.dirty" class="alert alert-warning">มีไฟล์โปรแกรมที่แก้ไว้ในเครื่อง กรุณาบันทึกการแก้ไขก่อนอัปเดต</div>
        <div v-if="version && version.branch !== 'main'" class="alert alert-warning">ต้องใช้ checkout สาขา main เพื่ออัปเดตอัตโนมัติ</div>
        <button class="btn btn-outline-primary me-2" :disabled="busy || checking" @click="checkVersion">{{ checking ? 'กำลังตรวจสอบ...' : 'ตรวจเวอร์ชัน' }}</button>
        <button class="btn btn-primary" :disabled="busy || checking || !version?.canUpdate" @click="update">{{ busy ? 'กำลังอัปเดต...' : 'อัปเดตจาก GitHub' }}</button>
        <div v-if="message" class="alert mt-3" :class="success ? 'alert-success' : 'alert-info'" role="status">{{ message }}</div>
        <div v-if="logs.length" class="progress mt-3" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" :style="{width: `${progress}%`}">{{ progress }}%</div></div>
        <pre v-if="logs.length" class="bg-dark text-light rounded p-3 mt-3 update-log">{{ logs.join('\n') }}</pre>
        <button v-if="success" class="btn btn-success" @click="reload">โหลดหน้าเว็บเวอร์ชันใหม่</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Swal from 'sweetalert2'
import api from '../../services/api'

const version = ref(null), error = ref(''), message = ref(''), logs = ref([])
const checking = ref(false), busy = ref(false), success = ref(false), progress = ref(0)
const reload = () => window.location.reload()
async function checkVersion() {
  checking.value = true
  error.value = ''
  version.value = null
  try {
    version.value = (await api.get('?route=admin/program/status', {timeout: 90000})).data
  } catch (e) {
    error.value = e.response?.data?.error || 'ตรวจ GitHub ไม่สำเร็จ กรุณาตรวจ Git และการเชื่อมต่ออินเทอร์เน็ต'
  } finally { checking.value = false }
}
async function update() {
  if (busy.value || !version.value?.canUpdate) return
  const confirmation = await Swal.fire({title: 'อัปเดตโปรแกรมจาก GitHub?', text: 'ผู้ใช้อาจใช้งานไม่ได้ชั่วคราวระหว่างเปลี่ยนไฟล์โปรแกรม', icon: 'question', showCancelButton: true, confirmButtonText: 'เริ่มอัปเดต', cancelButtonText: 'ยกเลิก'})
  if (!confirmation.isConfirmed) return
  busy.value = true
  error.value = ''
  message.value = 'กำลังเตรียมและอัปเดตโปรแกรม...'
  success.value = false
  logs.value = []
  progress.value = 0
  try {
    const url = new URL(api.defaults.baseURL || '/api/', window.location.origin)
    url.searchParams.set('route', 'admin/program/update')
    const response = await fetch(url, {method: 'POST', headers: {'Content-Type': 'application/json', Authorization: `Bearer ${localStorage.getItem('token') || ''}`}, body: JSON.stringify({latest: version.value.latest})})
    if (!response.ok) {
      const result = await response.json()
      throw new Error(result.error || `HTTP ${response.status}`)
    }
    const reader = response.body.getReader(), decoder = new TextDecoder()
    let buffer = '', finished = false
    while (true) {
      const {value, done} = await reader.read()
      buffer += decoder.decode(value || new Uint8Array(), {stream: !done})
      const records = buffer.split('\n')
      buffer = records.pop()
      for (const record of records) {
        if (!record.trim()) continue
        const event = JSON.parse(record)
        if (event.type === 'line') {
          logs.value.push(event.text)
          const step = event.text.match(/^\[(\d+)\/5\]/)
          if (step) progress.value = Math.round((Number(step[1]) - 1) * 100 / 5)
        } else if (event.type === 'done') {
          finished = true
          success.value = event.ok
          message.value = event.ok ? 'อัปเดตสำเร็จ กดโหลดหน้าเว็บเวอร์ชันใหม่' : 'อัปเดตไม่สำเร็จ ตรวจรายละเอียดและเวอร์ชันอีกครั้ง'
          if (event.ok) progress.value = 100
        }
      }
      if (done) break
    }
    if (!finished) throw new Error('การเชื่อมต่อสิ้นสุดก่อนรับผลอัปเดต กรุณาตรวจเวอร์ชันอีกครั้งก่อนเริ่มใหม่')
  } catch (e) { error.value = e.message }
  finally { busy.value = false; await checkVersion() }
}
onMounted(checkVersion)
</script>

<style scoped>
.update-log { max-height: 350px; overflow: auto; white-space: pre-wrap; overflow-wrap: anywhere; }
</style>
