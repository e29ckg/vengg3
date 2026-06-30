<template>
  <div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
      <h3 class="fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>รายงานการอยู่เวรรายบุคคล</h3>
    </div>

    <div class="card shadow-sm border-0 rounded-4 mb-4 d-print-none">
      <div class="card-body bg-light rounded-4">
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label fw-bold">เดือน</label>
            <select class="form-select" v-model="selectedMonth" @change="fetchPersonalSchedules">
              <option value="" disabled>-- เลือกเดือน --</option>
              <option v-for="(month, index) in thaiMonths" :key="index" :value="index + 1">{{ month }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">ปี (พ.ศ.)</label>
            <select class="form-select" v-model="selectedYear" @change="fetchPersonalSchedules">
              <option value="" disabled>-- เลือกปี พ.ศ. --</option>
              <option v-for="year in yearOptions" :key="year.be" :value="year.ce">{{ year.be }}</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">เลือกบุคลากร</label>
            <select class="form-select" v-model="selectedUserId" :disabled="isLoadingUsers" @change="fetchPersonalSchedules">
              <option value="" disabled>{{ isLoadingUsers ? 'กำลังโหลดรายชื่อ...' : '-- เลือกบุคคล --' }}</option>
              <option v-for="user in users" :key="user.id || user.user_id" :value="user.id || user.user_id">
                {{ user.full_name }} ({{ user.department }})
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 print-card" v-if="selectedUserId">
      <div class="card-body">
        
        <div v-if="isLoadingSchedules" class="text-center py-5 d-print-none">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-3 text-muted">กำลังดึงข้อมูลเวร...</p>
        </div>

        <div v-else class="table-responsive print-table-container">
          
          <div class="d-flex flex-wrap justify-content-end align-items-center mb-3 d-print-none">
            <div class="d-flex gap-2">
              <button @click="exportToExcel" class="btn btn-success btn-sm" :disabled="Object.keys(groupedSchedules).length === 0">
                <i class="bi bi-file-earmark-excel me-1"></i> ส่งออก Excel
              </button>
              <button @click="printTable" class="btn btn-secondary btn-sm" :disabled="Object.keys(groupedSchedules).length === 0">
                <i class="bi bi-printer me-1"></i> พิมพ์รายงาน
              </button>
            </div>
          </div>

          <div class="text-center mb-4">
            <h4 class="fw-bold mb-1">รายงานการปฏิบัติหน้าที่เวรนอกเวลาทำการ</h4>
            <h5 class="mb-1 text-primary">ชื่อ: {{ getSelectedUserName() }}</h5>
            <p class="mb-0">ประจำเดือน {{ thaiMonths[selectedMonth-1] }} พ.ศ. {{ parseInt(selectedYear) + 543 }}</p>
          </div>

          <div v-if="Object.keys(groupedSchedules).length === 0" class="alert alert-warning text-center">
            ไม่พบข้อมูลการอยู่เวรของบุคคลนี้ในเดือนที่เลือก
          </div>

          <div v-for="(group, cmdName) in groupedSchedules" :key="cmdName" class="mb-4">
            <h5 class="fw-bold bg-light p-2 border-start border-4 border-primary rounded-end">
              {{ cmdName }}
            </h5>
            <table class="table table-bordered align-middle">
              <thead class="table-primary text-center">
                <tr>
                  <th width="10%">ลำดับ</th>
                  <th width="20%">วันที่</th>
                  <th width="35%">กลุ่มเวร / หน้าที่</th>
                  <th width="35%">หมายเหตุ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(schedule, index) in group" :key="schedule.id || index">
                  <td class="text-center">{{ index + 1 }}</td>
                  <td class="text-center fw-bold">{{ formatDateThai(schedule.ven_date) }}</td>
                  <td>{{ schedule.sub_name ? schedule.sub_name : schedule.ven_name }}</td>
                  <td class="text-center">{{ schedule.remark || '-' }}</td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import * as XLSX from 'xlsx'

const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม']
const yearOptions = ref([])
const selectedMonth = ref('')
const selectedYear = ref('')
const selectedUserId = ref('')

const users = ref([])
const rawSchedules = ref([])

const isLoadingUsers = ref(false)
const isLoadingSchedules = ref(false)

onMounted(() => {
  const currentDate = new Date()
  selectedMonth.value = currentDate.getMonth() + 1
  selectedYear.value = currentDate.getFullYear()
  for (let i = selectedYear.value - 1; i <= selectedYear.value + 1; i++) {
    yearOptions.value.push({ ce: i, be: i + 543 })
  }
  fetchUsers()
})

// ✨ ดึงรายชื่อบุคคลทั้งหมดมาใส่ใน Dropdown
const fetchUsers = async () => {
  try {
    isLoadingUsers.value = true
    // สมมติว่า route ดึงข้อมูล user ของคุณคือ users/list หรือดึงจากตาราง profile
    const res = await api.get('?route=users/list') 
    users.value = res.data || []
  } catch (e) { 
    console.error("Error fetching users:", e) 
  } finally { 
    isLoadingUsers.value = false 
  }
}

// ✨ ดึงข้อมูลเวรตามรายบุคคล
const fetchPersonalSchedules = async () => {
  if (!selectedUserId.value) return;

  try {
    isLoadingSchedules.value = true
    // ส่งข้อมูลเดือน ปี และ User ID ไปให้ฝั่ง Backend
    const res = await api.get('?route=report/personal-schedule', { 
      params: { 
        month: selectedMonth.value, 
        year: selectedYear.value,
        user_id: selectedUserId.value
      } 
    })
    rawSchedules.value = res.data || []
  } catch (e) { 
    rawSchedules.value = []
    console.error("Error fetching personal schedules:", e) 
  } finally { 
    isLoadingSchedules.value = false 
  }
}

// ✨ Computed: จัดกลุ่มข้อมูลที่ได้มาแยกตาม "คำสั่ง"
const groupedSchedules = computed(() => {
  const groups = {}
  rawSchedules.value.forEach(schedule => {
    // สร้างชื่อคำสั่งเพื่อใช้เป็น Key สำหรับจัดกลุ่ม (ถ้าไม่มีข้อมูลให้จัดเข้ากลุ่ม ไม่ระบุคำสั่ง)
    const commandText = schedule.ven_com_num || schedule.ven_com_name 
      ? `คำสั่งที่ ${schedule.ven_com_num || ''} ${schedule.ven_com_name || ''}`.trim()
      : 'รายการเวรไม่ระบุคำสั่ง'
      
    if (!groups[commandText]) {
      groups[commandText] = []
    }
    groups[commandText].push(schedule)
  })
  
  // เรียงลำดับวันที่ภายในแต่ละกลุ่มคำสั่ง
  for (const cmdKey in groups) {
    groups[cmdKey].sort((a, b) => new Date(a.ven_date) - new Date(b.ven_date))
  }
  
  return groups
})

const getSelectedUserName = () => {
  const user = users.value.find(u => (u.id || u.user_id) === selectedUserId.value)
  return user ? user.full_name : ''
}

const formatDateThai = (d) => {
  if (!d) return '-'
  const date = new Date(d)
  return `${date.getDate()} ${thaiMonths[date.getMonth()]} ${date.getFullYear() + 543}`
}

// ✨ ฟังก์ชันส่งออก Excel โดยจัดรูปแบบตามคำสั่ง
const exportToExcel = () => {
  const workbook = XLSX.utils.book_new()
  const sheetName = "รายงานบุคคล"
  
  const reportHeader = [
    ["รายงานการปฏิบัติหน้าที่เวรนอกเวลาทำการ"],
    [`ชื่อ: ${getSelectedUserName()}`],
    [`ประจำเดือน ${thaiMonths[selectedMonth.value-1]} พ.ศ. ${parseInt(selectedYear.value) + 543}`],
    [] 
  ]

  let tableData = []

  // วนลูปเขียนข้อมูลลง Excel แยกตามกลุ่มคำสั่ง
  Object.keys(groupedSchedules.value).forEach(cmdName => {
    tableData.push([cmdName]) // หัวข้อคำสั่ง
    tableData.push(["ลำดับ", "วันที่", "กลุ่มเวร / หน้าที่", "หมายเหตุ"]) // หัวตาราง
    
    groupedSchedules.value[cmdName].forEach((schedule, index) => {
      tableData.push([
        index + 1,
        formatDateThai(schedule.ven_date),
        schedule.sub_name || schedule.ven_name || '',
        schedule.remark || ''
      ])
    })
    tableData.push([]) // เว้นบรรทัดระหว่างคำสั่ง
  })

  const finalAoA = [...reportHeader, ...tableData]
  const worksheet = XLSX.utils.aoa_to_sheet(finalAoA)

  worksheet['!cols'] = [{ wch: 10 }, { wch: 20 }, { wch: 35 }, { wch: 20 }]

  XLSX.utils.book_append_sheet(workbook, worksheet, sheetName)
  XLSX.writeFile(workbook, `Report_${getSelectedUserName()}_${selectedMonth.value}_${selectedYear.value}.xlsx`)
}

const printTable = () => { window.print() }
</script>

<style scoped>
@media print {
  .print-card {
    box-shadow: none !important;
    border: none !important;
  }
}
</style>