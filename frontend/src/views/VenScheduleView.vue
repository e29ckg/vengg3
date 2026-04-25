<template>
  <div class="bg-light min-vh-100 d-flex flex-column pb-3">
    <nav class="navbar navbar-dark bg-primary shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4" href="#"><i class="bi bi-calendar3 me-2"></i>จัดตารางเวร (Drag & Drop)</a>
        <router-link to="/home" class="btn btn-outline-light btn-sm rounded-pill px-3">กลับหน้าหลัก</router-link>
      </div>
    </nav>

    <div class="container-fluid flex-grow-1 d-flex p-3 gap-3">
      
      <div class="card shadow-sm border-0 rounded-4 d-flex flex-column position-sticky" style="width: 350px; min-width: 350px; top: 20px; height: calc(100vh - 110px);">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>รายชื่อผู้อยู่เวร</h5>
        </div>
        <div class="card-body px-4 d-flex flex-column gap-3">
          
          <div>
            <label class="form-label small fw-bold text-muted">1. เลือกเดือนปฏิทิน</label>
            <input type="month" class="form-control" v-model="currentMonth" @change="onMonthChange">
          </div>

          <div>
            <label class="form-label small fw-bold text-muted">2. เลือกคำสั่ง</label>
            <select class="form-select" v-model="activeCommand" @change="onCommandChange">
              <option value="">-- เลือกคำสั่ง ({{ formatMonthThai(currentMonth) }}) --</option>
              <option v-for="com in filteredCommands" :key="com.id" :value="com">
                {{ com.com_num }} ({{ com.ven_name_title }})
              </option>
            </select>
            <div v-if="filteredCommands.length === 0 && currentMonth" class="text-danger small mt-1">
              <i class="bi bi-exclamation-circle me-1"></i>ยังไม่มีคำสั่งของเดือนนี้ในระบบ
            </div>
          </div>

          <div v-if="activeCommand">
            <label class="form-label small fw-bold text-muted">3. เลือกหน้าที่เพื่อจัดคน</label>
            <select class="form-select border-primary" v-model="activeSubDuty" @change="loadEligibleUsers">
              <option value="">-- เลือกหน้าที่ย่อย --</option>
              <option v-for="sub in subDuties" :key="sub.id" :value="sub">
                {{ sub.name }}
              </option>
            </select>
          </div>

          <div class="user-list-container border rounded-3 p-2 bg-light mt-2 flex-grow-1 shadow-inner" v-if="activeSubDuty">
            <p class="text-center small text-muted mb-2 sticky-top bg-light pb-2 pt-1 border-bottom" style="z-index: 1;">
              <i class="bi bi-arrows-move me-1"></i>ลากชื่อไปวางในปฏิทิน
            </p>
            
            <div v-for="user in eligibleUsers" :key="user.id" 
                 class="user-card shadow-sm mb-2 p-2 rounded-2 bg-white border-start border-4 border-primary d-flex align-items-center"
                 draggable="true" 
                 @dragstart="startDrag($event, user)">
              <i class="bi bi-grip-vertical text-muted me-1"></i>
              <span class="fw-semibold">{{ user.prefix }}{{ user.name }} {{ user.sname }}</span>
            </div>
            
            <div v-if="eligibleUsers.length === 0" class="text-center text-muted py-4 small">
              <i class="bi bi-person-x fs-4 d-block mb-2"></i>ไม่มีรายชื่อผู้มีสิทธิ์ในหน้าที่นี้
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 rounded-4 flex-grow-1 overflow-hidden d-flex flex-column">
        <div class="row g-0 bg-dark text-white fw-bold text-center py-2 shadow-sm">
          <div class="col" v-for="day in weekDays" :key="day">{{ day }}</div>
        </div>

        <div class="calendar-grid bg-light flex-grow-1 overflow-auto p-2">
          <div class="day-cell blank" v-for="b in blankDays" :key="'b'+b"></div>

          <div class="day-cell border rounded-3 bg-white shadow-sm d-flex flex-column" 
               v-for="day in daysInMonth" :key="day"
               @drop="onDrop($event, day)" 
               @dragover.prevent>
            
            <div class="day-header fw-bold text-secondary border-bottom px-2 py-1 bg-light text-end">
              {{ day }}
            </div>
            
            <div class="day-body p-1 flex-grow-1 overflow-auto custom-scrollbar">
              <div v-for="schedule in getSchedulesForDay(day)" :key="schedule.id" 
                   class="schedule-item mb-1 p-1 rounded-2 border d-flex justify-content-between align-items-start"
                   :class="{ 'clash-warning blink': isClashing(schedule) }"
                   :style="{ backgroundColor: schedule.color, color: getTextColor(schedule.color) }"
                   :title="isClashing(schedule) ? '⚠️ คำเตือน: เข้าเวรชนกันหรือพักผ่อนไม่พอ!' : schedule.sub_name">
                
                <div class="text-truncate" style="max-width: 85%; white-space: normal; line-height: 1.1;">
                  <i v-if="isClashing(schedule)" class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                  <span class="fw-bold d-block mb-1">{{ schedule.user_name }}</span>
                  <span style="font-size: 0.65rem; opacity: 0.9;">{{ schedule.com_num }} - {{ schedule.sub_name }}</span>
                </div>
                
                <button class="btn btn-sm p-0 border-0 ms-1 opacity-75 hover-opacity-100" 
                        :style="{ color: getTextColor(schedule.color) }"
                        @click="removeSchedule(schedule.id)">
                  <i class="bi bi-x-circle-fill"></i>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'

// --- State Variables ---
// ตั้งค่าเริ่มต้นเป็นเดือนปัจจุบัน YYYY-MM
const today = new Date()
const currentMonth = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`)
const weekDays = ['อาทิตย์', 'จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์']

const commands = ref([])
const activeCommand = ref('')
const subDuties = ref([])
const activeSubDuty = ref('')
const eligibleUsers = ref([])
const allSchedules = ref([]) // ข้อมูลตารางเวรทั้งหมดของเดือน

// --- Computed & Helpers ---
const daysInMonth = computed(() => {
  const [y, m] = currentMonth.value.split('-')
  return new Date(y, m, 0).getDate()
})
const blankDays = computed(() => {
  const [y, m] = currentMonth.value.split('-')
  return new Date(y, m - 1, 1).getDay()
})

// กรองคำสั่งให้ตรงกับเดือนที่เลือก
const filteredCommands = computed(() => {
  return commands.value.filter(com => com.ven_month === currentMonth.value)
})

const getSchedulesForDay = (day) => {
  return allSchedules.value.filter(s => parseInt(s.day) === day)
}

const formatMonthThai = (ymString) => {
  if (!ymString) return ''
  const [year, month] = ymString.split('-')
  const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม']
  return `${thaiMonths[parseInt(month) - 1]} ${parseInt(year) + 543}`
}

const getTextColor = (bgColor) => {
  if (!bgColor) return '#000'
  const darks = ['blueviolet', 'brown', 'green', 'magenta', 'darkblue', 'crimson', '#0d6efd', '#198754', '#dc3545']
  return darks.includes(bgColor.toLowerCase()) ? '#fff' : '#000'
}

// --- API Methods ---
const fetchCommands = async () => {
  const res = await api.get('?route=admin/ven_com&action=list')
  commands.value = res.data || []
}

const fetchMonthSchedules = async () => {
  const res = await api.get(`?route=admin/ven_schedule&action=list_month&month=${currentMonth.value}`)
  allSchedules.value = res.data || []
}

// --- Logic Flow ---
const onMonthChange = () => {
  // รีเซ็ตค่าการเลือกเดิม
  activeCommand.value = ''
  activeSubDuty.value = ''
  subDuties.value = []
  eligibleUsers.value = []
  // ดึงตารางเวรเดือนใหม่มาแสดง
  fetchMonthSchedules()
}

const onCommandChange = async () => {
  if (!activeCommand.value) return
  activeSubDuty.value = ''
  eligibleUsers.value = []
  
  // ดึงรายละเอียดหน้าที่ย่อยของคำสั่งนี้
  const res = await api.get(`?route=admin/setting&action=ven_full`)
  const mainVen = res.data.find(v => v.id === activeCommand.value.ven_name_id)
  subDuties.value = mainVen ? mainVen.subs : []
}

const loadEligibleUsers = async () => {
  if (!activeSubDuty.value) return
  const res = await api.get(`?route=admin/ven_user&action=get_by_sub&sub_id=${activeSubDuty.value.id}`)
  eligibleUsers.value = res.data || []
}

// --- Drag & Drop ---
const startDrag = (evt, user) => {
  evt.dataTransfer.dropEffect = 'copy'
  evt.dataTransfer.effectAllowed = 'copy'
  
  // แนบรหัสผู้ใช้ และชื่อ เพื่อเตรียมใช้งานตอน Drop
  evt.dataTransfer.setData('userID', user.user_id)
  const fullName = `${user.prefix || ''}${user.name} ${user.sname}`
  evt.dataTransfer.setData('userName', fullName)
}

const onDrop = async (evt, day) => {
  const userID = evt.dataTransfer.getData('userID')
  const userName = evt.dataTransfer.getData('userName')

  if (!userID || !activeCommand.value || !activeSubDuty.value) {
    Swal.fire('คำแนะนำ', 'กรุณาเลือกคำสั่งและหน้าที่ย่อยก่อนลากวาง', 'info')
    return
  }
  
  // ==========================================
  // 🌟 เพิ่มระบบตรวจสอบชื่อซ้ำในคำสั่งเดียวกัน (วันเดียวกัน)
  // ==========================================
  const isDuplicate = allSchedules.value.some(s => 
    parseInt(s.day) === parseInt(day) && 
    s.com_id === activeCommand.value.id && 
    s.user_id === userID
  )

  if (isDuplicate) {
    Swal.fire({
      title: 'รายชื่อซ้ำ!',
      text: `มีชื่อ ${userName} ในคำสั่งนี้ของวันนี้อยู่แล้วครับ`,
      icon: 'warning',
      confirmButtonText: 'ตกลง'
    })
    return // หยุดการทำงาน ไม่ให้ลากลงไปได้
  }

  // สร้างออบเจกต์จำลองเพื่อนำลง UI ก่อนทันที (ไม่ต้องรอ API โหลดเสร็จ เพื่อให้ลื่นไหล)
  const tempSchedule = {
    id: 'temp_' + Date.now(),
    day: day,
    date: `${currentMonth.value}-${String(day).padStart(2, '0')}`,
    user_id: userID,
    user_name: userName,
    com_id: activeCommand.value.id,
    com_num: activeCommand.value.com_num,
    sub_id: activeSubDuty.value.id,
    sub_name: activeSubDuty.value.name,
    shift_type: activeCommand.value.dn, 
    color: activeSubDuty.value.color
  }

  allSchedules.value.push(tempSchedule) // โชว์ใน UI เลย

  try {
    // บันทึกลงฐานข้อมูล
    await api.post('?route=admin/ven_schedule&action=add', {
      date: tempSchedule.date,
      com_id: tempSchedule.com_id,
      sub_id: tempSchedule.sub_id,
      user_id: tempSchedule.user_id
    })
    // โหลดใหม่เพื่ออัปเดต ID จริง
    fetchMonthSchedules()
  } catch (error) {
    // ถ้าเซฟไม่ผ่าน ให้ลบตัวจำลองออก
    allSchedules.value = allSchedules.value.filter(s => s.id !== tempSchedule.id)
    Swal.fire('ผิดพลาด', 'บันทึกข้อมูลไม่สำเร็จ', 'error')
  }
}

const removeSchedule = async (id) => {
  const result = await Swal.fire({ title: 'ลบรายชื่อนี้?', icon: 'warning', showCancelButton: true })
  if (result.isConfirmed) {
    try {
      await api.post('?route=admin/ven_schedule&action=remove', { id })
      fetchMonthSchedules()
    } catch (e) {
      Swal.fire('ผิดพลาด', 'ลบข้อมูลไม่สำเร็จ', 'error')
    }
  }
}

// --- Clash Detection (เช็คเวรชนกัน) ---
const isClashing = (schedule) => {
  if (!schedule.shift_type) return false
  const userShifts = allSchedules.value.filter(s => s.user_id === schedule.user_id)
  
  // 1. วันเดียวกัน ลงทั้งกลางวันและกลางคืน (หรือ nightCourt)
  const sameDay = userShifts.filter(s => parseInt(s.day) === parseInt(schedule.day))
  if (sameDay.length > 1) {
    const hasDay = sameDay.some(s => s.shift_type.includes('กลางวัน'))
    const hasNight = sameDay.some(s => s.shift_type.includes('กลางคืน') || s.shift_type.includes('nightCourt'))
    if (hasDay && hasNight) return true
  }

  // 2. ถ้าเป็นเวรกลางคืน ตรวจว่าวันพรุ่งนี้มีเวรเช้าหรือไม่
  const isNight = schedule.shift_type.includes('กลางคืน') || schedule.shift_type.includes('nightCourt')
  if (isNight) {
    const tomorrowDayShift = userShifts.find(s => parseInt(s.day) === parseInt(schedule.day) + 1 && s.shift_type.includes('กลางวัน'))
    if (tomorrowDayShift) return true
  }
  
  // 3. ถ้าเป็นเวรกลางวัน ตรวจว่าเมื่อคืนทำดึกมาหรือไม่
  const isDay = schedule.shift_type.includes('กลางวัน')
  if (isDay) {
    const yesterdayNightShift = userShifts.find(s => parseInt(s.day) === parseInt(schedule.day) - 1 && (s.shift_type.includes('กลางคืน') || s.shift_type.includes('nightCourt')))
    if (yesterdayNightShift) return true
  }

  return false
}

onMounted(() => {
  fetchCommands()
  fetchMonthSchedules()
})
</script>

<style scoped>
/* CSS ปฏิทิน */
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-auto-rows: minmax(140px, auto);
  gap: 6px;
}
.day-cell { transition: 0.2s; min-width: 0; }
.day-cell.blank { border: none !important; background: none !important; }
.day-cell:hover { border-color: #0d6efd !important; background-color: #f0f7ff !important; }

/* CSS รายชื่อและลากวาง */
.user-card { cursor: grab; transition: transform 0.1s; }
.user-card:active { cursor: grabbing; transform: scale(0.95); opacity: 0.8; }
.schedule-item { font-size: 0.75rem; transition: 0.2s; cursor: default; }
.schedule-item:hover { filter: brightness(90%); }

/* 🌟 จัดการ Scrollbar กล่องรายชื่อซ้ายมือ */
.user-list-container {
  overflow-y: auto;
  max-height: calc(100vh - 400px); 
  position: relative;
}
.user-list-container::-webkit-scrollbar,
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.user-list-container::-webkit-scrollbar-track,
.custom-scrollbar::-webkit-scrollbar-track {
  background: #f8f9fa; 
  border-radius: 8px;
}
.user-list-container::-webkit-scrollbar-thumb,
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #c1c1c1; 
  border-radius: 8px;
}
.user-list-container::-webkit-scrollbar-thumb:hover {
  background: #0d6efd; 
}

/* 🌟 เอฟเฟกต์กระพริบเตือนเวรชนกัน */
.clash-warning {
  border: 2px solid #ffc107 !important;
}
.blink {
  animation: blinker 2s linear infinite;
}
@keyframes blinker {
  50% { opacity: 0.5; background-color: #dc3545 !important; color: white !important; }
}
</style>