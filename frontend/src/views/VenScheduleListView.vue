<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
      <h3 class="fw-bold text-primary"><i class="bi bi-list-ul me-2"></i>รายการการจัดเวร</h3>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4 d-print-none">
      <div class="card-body bg-light rounded-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-bold">เดือน</label>
            <select class="form-select" v-model="selectedMonth" @change="fetchCommands">
              <option value="" disabled>-- เลือกเดือน --</option>
              <option v-for="(month, index) in thaiMonths" :key="index" :value="index + 1">{{ month }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">ปี (พ.ศ.)</label>
            <select class="form-select" v-model="selectedYear" @change="fetchCommands">
              <option value="" disabled>-- เลือกปี พ.ศ. --</option>
              <option v-for="year in yearOptions" :key="year.be" :value="year.ce">{{ year.be }}</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">เลขที่คำสั่งเวร</label>
            <select class="form-select" v-model="selectedCommandId" :disabled="isLoadingCommands || !commands.length">
              <option value="" v-if="isLoadingCommands">กำลังโหลดคำสั่ง...</option>
              <option v-for="cmd in commands" :key="cmd.id || cmd.v_com_id" :value="cmd.id || cmd.v_com_id">
                คำสั่งที่ {{ cmd.ven_com_num ? cmd.ven_com_num : 'ไม่ระบุเลขที่' }} {{ cmd.ven_com_name ? `(${cmd.ven_com_name})` : '-' }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 print-card">
      <div class="card-body">
        
        <div v-if="isLoadingSchedules" class="text-center py-5 d-print-none">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-3 text-muted">กำลังดึงข้อมูล...</p>
        </div>

        <div v-else-if="selectedCommandId" class="table-responsive print-table-container">
          
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 d-print-none">
            <div class="btn-group mb-2 mb-md-0">
              <input type="radio" class="btn-check" id="mode1" value="1" v-model="viewMode">
              <label class="btn btn-outline-primary btn-sm" for="mode1">แบบที่ 1 (รายการ)</label>
              <input type="radio" class="btn-check" id="mode2" value="2" v-model="viewMode">
              <label class="btn btn-outline-primary btn-sm" for="mode2">แบบที่ 2 (ตารางขวาง)</label>
            </div>
            <div class="d-flex gap-2">
              <button @click="exportToExcel" class="btn btn-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
              </button>
              <button @click="printTable" class="btn btn-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
              </button>
            </div>
          </div>

          <div class="d-none d-print-block text-center mb-4">
            <h4 class="fw-bold mb-1">บัญชีรายชื่อผู้ปฏิบัติหน้าที่เวรนอกเวลาทำการ</h4>
            <h5 class="mb-1">{{ getSelectedCommandText() }}</h5>
            <p class="mb-0">ประจำเดือน {{ thaiMonths[selectedMonth-1] }} พ.ศ. {{ parseInt(selectedYear) + 543 }}</p>
          </div>

          <table v-if="viewMode === '1'" class="table table-bordered align-middle">
            <thead class="table-primary text-center">
              <tr>
                <!-- <th>ลำดับ</th> -->
                <th>วันที่</th>
                <th>ชื่อ-สกุล</th>
                <th>ตำแหน่ง/หน้าที่</th>
                <th>กลุ่มเวร</th>
                <th>หมายเหตุ</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(group, gIndex) in schedules" :key="group.ven_date">
                <tr v-for="(person, pIndex) in group.staff_list" :key="person.id">
                  <!-- <td v-if="pIndex === 0" :rowspan="group.staff_list.length" class="text-center bg-light">{{ gIndex + 1 }}</td> -->
                  <td v-if="pIndex === 0" :rowspan="group.staff_list.length" class="text-center fw-bold bg-light">{{ formatDateThai(group.ven_date) }}</td>
                  <td>{{ person.full_name }}</td>
                  <td>{{ person.position || '-' }}</td>
                  <td>{{ person.sub_name ? person.sub_name : person.ven_name }}</td>
                  <td class="text-center"></td>
                </tr>
              </template>
            </tbody>
          </table>

          <table v-if="viewMode === '2'" class="table table-bordered align-middle">
            <thead class="text-center table-primary">
              <tr>
                <th width="15%">วันที่</th>
                <th v-for="col in dutyColumns" :key="col">{{ col }}</th>
                <th width="10%">หมายเหตุ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in matrixSchedules" :key="row.ven_date">
                <td class="text-center fw-bold bg-light">{{ formatDateThai(row.ven_date) }}</td>
                <td v-for="col in dutyColumns" :key="col">
                  <div v-for="person in row.duties[col]" :key="person.id">- {{ person.full_name }}</div>
                </td>
                <td></td>
              </tr>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../services/api'
import * as XLSX from 'xlsx'

const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม']
const yearOptions = ref([])
const selectedMonth = ref('')
const selectedYear = ref('')
const selectedCommandId = ref('')
const viewMode = ref('1')
const commands = ref([])
const schedules = ref([])
const isLoadingCommands = ref(false)
const isLoadingSchedules = ref(false)

onMounted(() => {
  const currentDate = new Date()
  selectedMonth.value = currentDate.getMonth() + 1
  selectedYear.value = currentDate.getFullYear()
  for (let i = selectedYear.value - 1; i <= selectedYear.value + 1; i++) {
    yearOptions.value.push({ ce: i, be: i + 543 })
  }
  fetchCommands()
})

watch(selectedCommandId, (newId) => { if (newId) fetchScheduleDetails() })

const dutyColumns = computed(() => {
  const cols = new Set()
  schedules.value.forEach(g => g.staff_list.forEach(p => cols.add(p.sub_name || p.ven_name)))
  return Array.from(cols)
})

const matrixSchedules = computed(() => {
  return schedules.value.map(g => {
    const row = { ven_date: g.ven_date, duties: {} }
    dutyColumns.value.forEach(c => row.duties[c] = [])
    g.staff_list.forEach(p => {
      const d = p.sub_name || p.ven_name
      if (row.duties[d]) row.duties[d].push(p)
    })
    return row
  })
})

const fetchCommands = async () => {
  try {
    isLoadingCommands.value = true
    const res = await api.get('?route=report/monthly', { params: { month: selectedMonth.value, year: selectedYear.value } })
    commands.value = res.data || []
  } catch (e) { console.error(e) } finally { isLoadingCommands.value = false }
}

const fetchScheduleDetails = async () => {
  try {
    isLoadingSchedules.value = true
    const res = await api.get('?route=report/schedule-details', { params: { command_id: selectedCommandId.value } })
    schedules.value = res.data || []
  } catch (e) { console.error(e) } finally { isLoadingSchedules.value = false }
}

// ✨ ฟังก์ชันส่งออก Excel พร้อมหัวรายงาน ✨
const exportToExcel = () => {
  const workbook = XLSX.utils.book_new()
  const sheetName = "ตารางเวร"
  
  // 1. สร้างหัวข้อรายงาน (Header rows)
  const reportHeader = [
    ["บัญชีรายชื่อผู้ปฏิบัติหน้าที่เวรนอกเวลาทำการ"],
    [getSelectedCommandText()],
    [`ประจำเดือน ${thaiMonths[selectedMonth.value-1]} พ.ศ. ${parseInt(selectedYear.value) + 543}`],
    [] // บรรทัดว่าง
  ]

  let tableData = []
  if (viewMode.value === '1') {
    tableData.push(["วันที่", "ชื่อ-สกุล", "ตำแหน่ง/หน้าที่", "กลุ่มเวร", "หมายเหตุ"])
    schedules.value.forEach((group, index) => {
      group.staff_list.forEach(p => {
        tableData.push([formatDateThai(group.ven_date), p.full_name, p.dep || '', p.sub_name || p.ven_name, ''])
      })
    })
  } else {
    tableData.push(["วันที่", ...dutyColumns.value, "หมายเหตุ"])
    matrixSchedules.value.forEach(row => {
      const rowContent = [formatDateThai(row.ven_date)]
      dutyColumns.value.forEach(col => rowContent.push(row.duties[col].map(p => p.full_name).join(', ')))
      rowContent.push("") // หมายเหตุ
      tableData.push(rowContent)
    })
  }

  // รวมหัวข้อกับข้อมูลตาราง
  const finalAoA = [...reportHeader, ...tableData]
  const worksheet = XLSX.utils.aoa_to_sheet(finalAoA)

  // ตั้งค่าความกว้างคอลัมน์ (ตัวอย่าง)
  worksheet['!cols'] = [{ wch: 10 }, { wch: 20 }, { wch: 25 }, { wch: 20 }, { wch: 20 }, { wch: 10 }]

  XLSX.utils.book_append_sheet(workbook, worksheet, sheetName)
  XLSX.writeFile(workbook, `Report_${selectedMonth.value}_${selectedYear.value}.xlsx`)
}

const formatDateThai = (d) => {
  if (!d) return '-'
  const date = new Date(d)
  return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`
}

const getSelectedCommandText = () => {
  const cmd = commands.value.find(c => (c.id || c.v_com_id) === selectedCommandId.value)
  return cmd ? `ตามคำสั่งที่ ${cmd.ven_com_num || ''} ${cmd.ven_com_name || ''}` : ''
}

const printTable = () => { window.print() }
</script>