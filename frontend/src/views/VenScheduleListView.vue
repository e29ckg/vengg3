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
              <option v-for="(month, index) in thaiMonths" :key="index" :value="index + 1">
                {{ month }}
              </option>
            </select>
          </div>

          <div class="col-md-3">
            <label class="form-label fw-bold">ปี (พ.ศ.)</label>
            <select class="form-select" v-model="selectedYear" @change="fetchCommands">
              <option value="" disabled>-- เลือกปี พ.ศ. --</option>
              <option v-for="year in yearOptions" :key="year.be" :value="year.ce">
                {{ year.be }}
              </option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">เลขที่คำสั่งเวร</label>
            <select class="form-select" v-model="selectedCommandId" :disabled="isLoadingCommands || !commands.length">
              <option value="" v-if="isLoadingCommands">กำลังโหลดคำสั่ง...</option>
              <option value="" v-else-if="commands.length === 0">-- ไม่พบคำสั่งในเดือนที่เลือก --</option>
              <option value="" v-else disabled>-- เลือกคำสั่งเพื่อดูตารางเวร --</option>
              
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
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-3 text-muted">กำลังดึงข้อมูลตารางเวร...</p>
        </div>

        <div v-else-if="!selectedCommandId" class="text-center py-5 d-print-none">
          <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
          <p class="mt-3 text-muted">กรุณาเลือกเดือน ปี และเลขที่คำสั่ง เพื่อดูรายการเวร</p>
        </div>

        <div v-else-if="schedules.length === 0" class="text-center py-5 d-print-none">
          <p class="text-danger fw-bold mb-0">ไม่พบรายชื่อผู้อยู่เวรในคำสั่งนี้</p>
        </div>

        <div v-else class="table-responsive print-table-container">
          
          <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-secondary mb-2 mb-md-0 d-print-none">
              <i class="bi bi-calendar-check me-2"></i>ตารางการปฏิบัติหน้าที่
            </h5>
            
            <div class="d-flex align-items-center d-print-none">
              <div class="btn-group me-3" role="group" aria-label="View Mode Toggle">
                <input type="radio" class="btn-check" name="viewmode" id="mode1" autocomplete="off" value="1" v-model="viewMode">
                <label class="btn btn-outline-primary btn-sm" for="mode1"><i class="bi bi-list-task me-1"></i> แบบที่ 1 (รายการ)</label>

                <input type="radio" class="btn-check" name="viewmode" id="mode2" autocomplete="off" value="2" v-model="viewMode">
                <label class="btn btn-outline-primary btn-sm" for="mode2"><i class="bi bi-table me-1"></i> แบบที่ 2 (ตารางขวาง)</label>
              </div>

              <button @click="printTable" class="btn btn-secondary btn-sm">
                <i class="bi bi-printer me-1"></i> พิมพ์ตาราง
              </button>
            </div>
          </div>

          <div class="d-none d-print-block text-center mb-4">
            <h4 class="fw-bold">ตารางการปฏิบัติหน้าที่เวรนอกเวลาทำการ</h4>
            <h5 v-if="selectedCommandId">{{ getSelectedCommandText() }}</h5>
          </div>

          <table v-if="viewMode === '1'" class="table table-hover table-bordered align-middle print-normal-table">
            <thead class="table-primary text-center align-middle">
              <tr>
                <th width="15%">วันที่อยู่เวร</th>
                <th width="20%">ชื่อ-สกุล</th>
                <th width="30%">ตำแหน่ง/หน้าที่</th>
                <th width="20%">กลุ่มเวร/จุดปฏิบัติงาน</th>
                <th width="15%">หมายเหตุ</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="(group, gIndex) in schedules" :key="group.ven_date">
                <tr v-for="(person, pIndex) in group.staff_list" :key="person.id">                  
                  <td v-if="pIndex === 0" :rowspan="group.staff_list.length" class="text-center align-middle fw-bold bg-light">
                    {{ formatDateThai(group.ven_date) }}
                  </td>
                  <td>{{ person.full_name }}</td>
                  <td>{{ person.dep || '-' }}</td>
                  <td>{{ person.sub_name ? person.sub_name : person.ven_name }}</td>
                  <td class="text-center">
                    
                  </td>
                </tr>
              </template>
            </tbody>
          </table>

          <table v-if="viewMode === '2'" class="table table-bordered align-middle print-matrix-table">
            <thead class="text-center align-middle">
              <tr class="table-primary">
                <th width="10%" class="py-3">วันที่</th>
                <th v-for="col in dutyColumns" :key="col" class="py-3">{{ col }}</th>
                <th width="10%" class="py-3">หมายเหตุ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in matrixSchedules" :key="row.ven_date">
                <td class="text-center align-middle fw-bold bg-light">
                  {{ formatDateThai(row.ven_date) }}
                </td>
                <td v-for="col in dutyColumns" :key="col" class="align-top py-2">
                  <div v-for="person in row.duties[col]" :key="person.id" class="mb-1">
                    - {{ person.full_name }}                    
                  </div>
                </td>
                <td class="align-top"></td>
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
import Swal from 'sweetalert2'

// --- ตัวแปร (State) ---
const thaiMonths = [
  'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
  'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
]
const yearOptions = ref([])

const selectedMonth = ref('')
const selectedYear = ref('')
const selectedCommandId = ref('')

// ✨ ตัวแปรควบคุมรูปแบบตาราง (1 = แบบรายการ, 2 = แบบ Matrix)
const viewMode = ref('1')

const commands = ref([])
const schedules = ref([])

const isLoadingCommands = ref(false)
const isLoadingSchedules = ref(false)

// --- ฟังก์ชันตอนเริ่มต้น (onMounted) ---
onMounted(() => {
  setupInitialDates()
})

const setupInitialDates = () => {
  const currentDate = new Date()
  selectedMonth.value = currentDate.getMonth() + 1
  const currentYearCE = currentDate.getFullYear()
  selectedYear.value = currentYearCE

  for (let i = currentYearCE - 2; i <= currentYearCE + 2; i++) {
    yearOptions.value.push({ ce: i, be: i + 543 })
  }

  fetchCommands()
}

// --- Watchers ---
watch(selectedCommandId, (newCommandId) => {
  if (newCommandId) {
    fetchScheduleDetails()
  } else {
    schedules.value = []
  }
})

// --- Computed Properties (สำหรับตารางแบบที่ 2) ---
const dutyColumns = computed(() => {
  const cols = new Set()
  schedules.value.forEach(group => {
    group.staff_list.forEach(person => {
      const dutyName = person.sub_name ? person.sub_name : person.ven_name
      if (dutyName) cols.add(dutyName)
    })
  })
  return Array.from(cols)
})

const matrixSchedules = computed(() => {
  return schedules.value.map(group => {
    const row = {
      ven_date: group.ven_date,
      duties: {}
    }
    dutyColumns.value.forEach(col => { row.duties[col] = [] })
    group.staff_list.forEach(person => {
      const dutyName = person.sub_name ? person.sub_name : person.ven_name
      if (dutyName && row.duties[dutyName]) {
         row.duties[dutyName].push(person)
      }
    })
    return row
  })
})

// --- API Functions ---
const fetchCommands = async () => {
  selectedCommandId.value = ''
  commands.value = []
  schedules.value = []

  if (!selectedMonth.value || !selectedYear.value) return

  try {
    isLoadingCommands.value = true
    const response = await api.get('?route=report/monthly', {
      params: { month: selectedMonth.value, year: selectedYear.value }
    })
    commands.value = response.data || []
  } catch (error) {
    console.error('Error fetching commands:', error)
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดรายการคำสั่งได้' })
  } finally {
    isLoadingCommands.value = false
  }
}

const fetchScheduleDetails = async () => {
  if (!selectedCommandId.value) return

  try {
    isLoadingSchedules.value = true
    const response = await api.get('?route=report/schedule-details', {
      params: { command_id: selectedCommandId.value }
    })
    schedules.value = response.data || []
  } catch (error) {
    console.error('Error fetching schedules:', error)
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลตารางเวรได้' })
  } finally {
    isLoadingSchedules.value = false
  }
}

// --- Helpers ---
const formatDateThai = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  const shortMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.']
  const d = date.getDate()
  const m = shortMonths[date.getMonth()]
  const y = date.getFullYear() + 543
  return `${d} ${m} ${y}`
}

const getStatusBadge = (status) => {
  switch (status) {
    case 'ปกติ': return 'bg-success'
    case 'ขอเปลี่ยนเวร': return 'bg-warning text-dark'
    case 'ยกเลิก': return 'bg-danger'
    default: return 'bg-secondary'
  }
}

const getSelectedCommandText = () => {
  const cmd = commands.value.find(c => c.id === selectedCommandId.value || c.v_com_id === selectedCommandId.value)
  if (cmd) {
    const num = cmd.ven_com_num ? cmd.ven_com_num : 'ไม่ระบุเลขที่'
    const name = cmd.ven_com_name ? `(${cmd.ven_com_name})` : ''
    return `คำสั่งที่ ${num} ${name}`
  }
  return ''
}

const printTable = () => {
  window.print()
}
</script>

<style scoped>
/* สไตล์เบื้องต้นสำหรับหัวตารางหน้าจอปกติ */
.table-primary th {
  background-color: #e2e3e5 !important;
  color: #000;
}

.badge {
  font-size: 0.75em;
  padding: 0.3em 0.6em;
}

/* --- การตั้งค่าสำหรับหน้าพิมพ์กระดาษ --- */
@media print {
  @page {
    /* size: A4 landscape; บังคับพิมพ์แนวนอน */
    size: a4;
    margin: 1cm;
  }

  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .print-card {
    border: none !important;
    box-shadow: none !important;
  }
  
  .card-body {
    padding: 0 !important;
  }

  .container {
    max-width: 100% !important;
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  /* ตกแต่งเส้นตารางตอนพิมพ์ให้เข้มและชัดขึ้น สำหรับทั้งแบบ 1 และ 2 */
  .print-normal-table, .print-matrix-table {
    width: 100% !important;
    border-collapse: collapse !important;
  }
  
  .print-normal-table th, .print-normal-table td,
  .print-matrix-table th, .print-matrix-table td {
    border: 1px solid #000 !important;
    padding: 8px !important;
    font-size: 14pt !important;
  }

  .print-normal-table th, .print-matrix-table th {
    background-color: #eaeaea !important;
  }

  .print-table-container {
    overflow: visible !important;
  }
}
</style>