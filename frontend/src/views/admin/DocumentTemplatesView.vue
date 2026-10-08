<template>
  <div class="container py-4" style="max-width: 1050px">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-dark text-white py-3"><h5 class="mb-0"><i class="bi bi-file-earmark-word me-2"></i>เทมเพลดเอกสารเวร</h5></div>
      <div class="card-body">
        <p>อัปโหลดไฟล์ Word .docx ไม่เกิน 2 MB เพื่อเปลี่ยนใบเปลี่ยนเวร หรือกำหนดรายงานแยกตามประเภทเวรหลัก</p>
        <p class="text-muted">เวรที่ยังไม่กำหนดไฟล์จะใช้ “รายงานเวรเริ่มต้น” หากยังไม่อัปโหลดไฟล์เริ่มต้น ระบบใช้ไฟล์ที่มาพร้อมโปรแกรม</p>
        <div v-if="error" class="alert alert-danger" role="alert">{{ error }} <button class="btn btn-sm btn-outline-danger ms-2" :disabled="busy" @click="load">ลองอีกครั้ง</button></div>
        <p v-if="loading" role="status">กำลังโหลด...</p>
        <div v-else class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>เอกสาร / เวร</th><th>ไฟล์ที่ใช้อยู่</th><th>ปรับปรุงล่าสุด</th><th>จัดการ</th></tr></thead>
            <tbody><tr v-for="row in templates" :key="`${row.kind}-${row.ven_name_id}`">
              <td>{{ row.label }}<div v-if="row.ven_name_id" class="text-muted small">รายงานเฉพาะเวร</div></td>
              <td><span class="badge" :class="row.custom ? 'bg-success' : 'bg-secondary'">{{ row.custom ? 'ไฟล์ที่อัปโหลด' : row.source === 'global' ? 'รายงานเวรเริ่มต้น' : 'ไฟล์จากโปรแกรม' }}</span></td>
              <td class="small">{{ row.updated_at ? new Date(row.updated_at).toLocaleString('th-TH') : '—' }}</td>
              <td><div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-sm btn-outline-primary" :disabled="busy" @click="download(row)">ดาวน์โหลด</button>
                <button class="btn btn-sm btn-primary" :disabled="busy" @click="choose(row)">อัปโหลดใหม่</button>
                <button v-if="row.custom" class="btn btn-sm btn-outline-danger" :disabled="busy" @click="reset(row)">คืนค่าเริ่มต้น</button>
              </div></td>
            </tr></tbody>
          </table>
        </div>
        <input ref="fileInput" class="d-none" type="file" accept=".docx" @change="upload">
        <p v-if="busy" class="text-primary" role="status">กำลังดำเนินการ...</p>
        <details class="mt-3 border rounded p-3">
          <summary class="fw-bold">วิธีปรับไฟล์และตัวแปรที่ใช้ในเทมเพลด</summary>
          <p class="mt-3">ดาวน์โหลดไฟล์ที่ใช้อยู่ เปิดแก้ใน Word แล้วบันทึกเป็น .docx โดยคงชื่อแท็กในวงเล็บปีกกา เช่น <code>{agency_name}</code> ตัวแปรจะถูกเติมตอนดาวน์โหลดรายงาน</p>
          <p class="fw-bold mb-1">ใบเปลี่ยนเวร</p><div class="d-flex gap-2 flex-wrap"><code v-for="field in shiftFields" :key="field">{{ '{' + field + '}' }}</code></div>
          <p class="fw-bold mt-3 mb-1">รายงานเวร</p><div class="d-flex gap-2 flex-wrap"><code v-for="field in dutyFields" :key="field">{{ '{' + field + '}' }}</code></div>
          <p class="mt-3 mb-0">รายชื่อผู้ร่วมเวรใช้ <code>{#shifts}</code> ... <code>{/shifts}</code> และใช้ <code>{no}</code>, <code>{name}</code>, <code>{position}</code>, <code>{duty_name}</code> ภายในช่วงนี้</p>
        </details>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Swal from 'sweetalert2'
import PizZip from 'pizzip'
import Docxtemplater from 'docxtemplater'
import { saveAs } from 'file-saver'
import api from '../../services/api'

const templates = ref([]), loading = ref(false), busy = ref(false), error = ref(''), fileInput = ref(null)
let selected = null
const shiftFields = ['change_no','ref_change_no','agency_name','sendTo','director_name','director_position','admins_name','admins_position','finance_name','finance_position','director_sign','admins_sign','order_no','order_date','ven_name_full','ven_date','command_date','ven_time','command_num','duty_main','duty_main_full','swal_comment','change_date','user1_name','user2_name','user1_dep','user2_dep','export_date']
const dutyFields = ['agency_name','full_agency_name','order_no','order_date','ven_date','ven_time','ven_name_full','user_name','user_position','director_name','director_position','admins_name','admins_position','print_date']
const query = row => `kind=${row.kind}&ven_name_id=${row.ven_name_id}`
async function load() {
  loading.value = true
  error.value = ''
  try { templates.value = (await api.get('?route=admin/templates/list')).data.templates }
  catch (e) { error.value = e.response?.data?.error || 'โหลดข้อมูลเทมเพลดไม่สำเร็จ' }
  finally { loading.value = false }
}
function choose(row) { selected = row; fileInput.value.value = ''; fileInput.value.click() }
async function upload(event) {
  const file = event.target.files[0], row = selected
  if (!file || !row || busy.value) return
  busy.value = true
  try {
    if (!/\.docx$/i.test(file.name) || file.size > 2097152) throw new Error('ใช้ไฟล์ .docx ไม่เกิน 2 MB')
    try { new Docxtemplater(new PizZip(await file.arrayBuffer()), {paragraphLoop: true, linebreaks: true}) }
    catch { throw new Error('ไฟล์ Word หรือรูปแบบแท็กไม่ถูกต้อง กรุณาตรวจวงเล็บและแท็กเปิด/ปิดในเทมเพลด') }
    const confirmation = await Swal.fire({titleText: `ปรับปรุง${row.label}?`, text: `ใช้ไฟล์ ${file.name}`, icon: 'question', showCancelButton: true, confirmButtonText: 'อัปโหลด', cancelButtonText: 'ยกเลิก'})
    if (!confirmation.isConfirmed) return
    const body = new FormData()
    body.append('kind', row.kind); body.append('ven_name_id', row.ven_name_id); body.append('template', file)
    await api.post('?route=admin/templates/upload', body, {headers: {'Content-Type': undefined}})
    await load()
    await Swal.fire({icon: 'success', title: 'ปรับปรุงเทมเพลดแล้ว', timer: 1500, showConfirmButton: false})
  } catch (e) { await Swal.fire('อัปโหลดไม่สำเร็จ', e.response?.data?.error || e.message, 'error') }
  finally { busy.value = false; fileInput.value.value = '' }
}
async function download(row) {
  busy.value = true
  try {
    const response = await api.get(`?route=documents/template&${query(row)}`, {responseType: 'blob'})
    saveAs(response.data, `${row.label.replace(/[\\/:*?"<>|]/g, '_')}.docx`)
  } catch { await Swal.fire('ผิดพลาด', 'ดาวน์โหลดเทมเพลดไม่สำเร็จ', 'error') }
  finally { busy.value = false }
}
async function reset(row) {
  const result = await Swal.fire({title: 'คืนค่าเทมเพลด?', text: `ยกเลิกไฟล์ที่อัปโหลดสำหรับ ${row.label}`, icon: 'warning', showCancelButton: true, confirmButtonText: 'คืนค่าเริ่มต้น', cancelButtonText: 'ยกเลิก'})
  if (!result.isConfirmed) return
  busy.value = true
  try { await api.delete(`?route=admin/templates/reset&${query(row)}`); await load() }
  catch (e) { await Swal.fire('ผิดพลาด', e.response?.data?.error || 'คืนค่าไม่สำเร็จ', 'error') }
  finally { busy.value = false }
}
onMounted(load)
</script>
