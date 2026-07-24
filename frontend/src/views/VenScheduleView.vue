<template>
  <div class="bg-light min-vh-100 d-flex flex-column pb-3">
    <div class="container-fluid flex-grow-1 d-flex p-3 gap-3">
      
      <div class="card shadow-sm border-0 rounded-4 d-flex flex-column sticky-name-list align-self-start" 
     style="width: 350px; min-width: 350px; top: 80px; height: calc(100vh - 100px); z-index: 10;">
        
        <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
          <h5 class="fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>รายชื่อผู้อยู่เวร</h5>
        </div>

        <div class="card-body px-4 d-flex flex-column gap-3 overflow-hidden">
          
          <div>
            <label class="form-label small fw-bold text-muted">1. เลือกเดือนปฏิทิน </label>
            <div class="input-group input-group-sm shadow-sm rounded-3 overflow-hidden">
              <select class="form-select border-0 bg-white" v-model="selMonth" @change="updateCurrentMonth">
                <option v-for="(m, idx) in thaiMonths" :key="idx" :value="String(idx + 1).padStart(2, '0')">
                  {{ m }}
                </option>
              </select>
              <select class="form-select border-0 border-start bg-white" v-model="selYear" @change="updateCurrentMonth">
                <option v-for="y in yearList" :key="y" :value="y">{{ y }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="form-label small fw-bold text-muted">2. เลือกคำสั่ง </label>
            <select class="form-select form-select-sm" v-model="activeCommand" @change="onCommandChange">
              <option value="">-- เลือกคำสั่ง ({{ formatMonthThai(currentMonth) }}) --</option>
              <option v-for="com in filteredCommands" :key="com.id" :value="com">
                {{ com.com_num }} ({{ com.ven_name_title }})
              </option>
            </select>

            
            <div v-if="activeCommand" class="mt-2 p-2 border rounded-3 bg-white shadow-sm d-flex justify-content-between align-items-center">
              <div>
                <span v-if="activeCommand.status == 0" class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>กำลังดำเนินการ</span>
                <span v-else-if="activeCommand.status == 1" class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>ยืนยันแล้ว (เปิดแลก)</span>
              </div>
              <button v-if="activeCommand.status == 0" class="btn btn-sm btn-success fw-bold" style="font-size: 0.7rem;" @click="toggleCommandStatus(1)">
                ยืนยันการจัดเวร
              </button>
              <button v-else-if="activeCommand.status == 1" class="btn btn-sm btn-outline-secondary fw-bold" style="font-size: 0.7rem;" @click="toggleCommandStatus(0)">
                ปลดล็อคแก้ไข
              </button>             

            </div>

            <div v-if="filteredCommands.length === 0" class="text-danger small mt-1" style="font-size: 0.7rem;">
              <i class="bi bi-exclamation-circle me-1"></i>ยังไม่มีคำสั่งของเดือนนี้
            </div>
          </div>

          <div v-if="activeCommand" class="d-flex flex-column gap-2">            
            <label class="form-label small fw-bold text-muted mb-0">3. เลือกหน้าที่เพื่อจัดคน</label>
            
            <select class="form-select form-select-sm border-primary" v-model="activeSubDuty" @change="loadEligibleUsers">
              <option value="">-- เลือกหน้าที่ย่อย --</option>
              <option v-for="sub in subDuties" :key="sub.id" :value="sub">{{ sub.name }}</option>
            </select>
            
            <div class="d-flex gap-1" v-if="activeSubDuty && activeCommand.status == 0">
              <button v-if="eligibleUsers.length > 0" class="btn btn-xs btn-outline-success flex-grow-1 fw-bold" @click="openAutoAssignModal">
                <i class="bi bi-robot"></i> จัด Auto
              </button>
              
              <button class="btn btn-xs btn-outline-danger flex-grow-1 fw-bold" @click="clearSubDutySchedules" :disabled="!hasSchedulesInActiveSubDuty">
                <i class="bi bi-trash"></i> ล้างหน้าที่นี้
              </button>
            </div>            

            
            <div v-if="activeCommand && activeCommand.status == 1" class="alert alert-success py-2 mt-2 mb-0 text-center small fw-bold">
              <i class="bi bi-lock-fill me-1"></i> คำสั่งนี้ยืนยันแล้ว ไม่สามารถแก้ไขได้
            </div>

          </div>

          <div class="user-list-container border rounded-3 p-2 bg-light flex-grow-1 shadow-inner overflow-auto" v-if="activeSubDuty && activeCommand && activeCommand.status == 0">
            <p class="text-center small text-muted mb-2 sticky-top bg-light pb-2 pt-1 border-bottom" style="z-index: 1; font-size: 0.75rem;">
              <i class="bi bi-arrows-move me-1"></i>ลากชื่อไปวางในปฏิทิน
            </p>
            
            <div v-for="user in eligibleUsers" :key="user.id" 
                 class="user-card shadow-sm mb-2 p-2 rounded-2 bg-white border-start border-4 border-primary d-flex align-items-center"
                 draggable="true" @dragstart="startDragFromSidebar($event, user)">
              <i class="bi bi-grip-vertical text-muted me-1"></i>
              <span class="fw-semibold small">{{ user.prefix_name }}{{ user.first_name }} {{ user.last_name }}</span>
            </div>
            
            <div v-if="eligibleUsers.length === 0" class="text-center text-muted py-4 small">
              ไม่มีรายชื่อผู้มีสิทธิ์
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 rounded-4 flex-grow-1 overflow-hidden d-flex flex-column">
        <div class="row g-0 bg-dark text-white fw-bold text-center py-2 shadow-sm">
          <div class="col" v-for="day in weekDays" :key="day">{{ day }}</div>
        </div>

        <div class="calendar-grid bg-light flex-grow-1 overflow-auto p-2 custom-scrollbar">
          <div class="day-cell blank" v-for="b in blankDays" :key="'b'+b"></div>

          <div class="day-cell border rounded-3 d-flex flex-column" 
               v-for="day in daysInMonth" :key="day"
               @drop="onDrop($event, day)" @dragover.prevent
               :class="{ 'bg-white shadow-sm': isDayEnabled(day), 'bg-secondary-subtle opacity-50': !isDayEnabled(day) }">
            
            <div class="day-header fw-bold border-bottom px-2 py-1 text-end"
                 :class="isDayEnabled(day) ? 'bg-light text-secondary' : 'bg-secondary text-white-50'">
              {{ day }}
              <i v-if="activeCommand && isDayEnabled(day)" class="bi bi-check-circle-fill text-success ms-1 small"></i>
            </div>
            
            <div class="day-body p-1 flex-grow-1 overflow-auto custom-scrollbar">
              <div v-for="schedule in getSchedulesForDay(day)" :key="schedule.id" 
                   class="schedule-item mb-1 p-1 rounded-1 border d-flex justify-content-between align-items-start"
                   :draggable="isScheduleEditable(schedule)" 
                   @dragstart="startDragFromCalendar($event, schedule)"
                   :class="{ 'clash-warning blink': isClashing(schedule) }"
                   :style="{ 
                     backgroundColor: schedule.color, 
                     color: getTextColor(schedule.color),
                     cursor: isScheduleEditable(schedule) ? 'grab' : 'not-allowed',
                     opacity: isScheduleEditable(schedule) ? '1' : '0.85'
                   }">
                
                <div :title="`${schedule.user_name} (${schedule.ven_time.substring(0,5)} น.)`" style="max-width: 85%; line-height: 1.2; font-size: 0.7rem; cursor: pointer;" @click="showScheduleDetails(schedule)">
                  <span class="fw-bold d-block text-truncate">{{ schedule.user_name }}</span>
                  
                  <span class="d-block text-truncate" style="opacity: 0.8; font-size: 0.6rem;">
                    <i class="bi bi-clock me-1"></i>{{ schedule.ven_time.substring(0,5) }} | {{ schedule.com_num }} - {{ schedule.sub_name }}
                  </span>
                </div>
                
                <button v-if="isScheduleEditable(schedule)" class="btn btn-sm p-0 border-0 ms-1 opacity-75" :style="{ color: getTextColor(schedule.color) }" @click="removeSchedule(schedule.id)">
                  <i class="bi bi-x-circle-fill"></i>
                </button>
                <i v-else class="bi bi-lock-fill ms-1 mt-1 opacity-50 small" :style="{ color: getTextColor(schedule.color) }" title="คำสั่งนี้ถูกยืนยันแล้ว"></i>
              </div>
            </div>

            
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="autoAssignModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-success text-white border-0 px-4 pt-4 pb-3">
            <h5 class="modal-title fw-bold"><i class="bi bi-robot me-2"></i>จัดเวรอัตโนมัติ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-4">
             <div class="mb-3">
               <label class="form-label fw-bold small">เลือกบุคคลเริ่มต้นคิวแรก:</label>
               <select class="form-select" v-model="selectedStartUser">
                 <option v-for="user in eligibleUsers" :key="user.id" :value="user.user_id">
                   {{ user.prefix_name }}{{ user.first_name }} {{ user.last_name }}
                 </option>
               </select>
             </div>
             
             <div class="mb-2">
               <label class="form-label fw-bold small">จำนวนคนต่อวัน (หน้าที่นี้):</label>
               <div class="input-group">
                 <input type="number" class="form-control" v-model="peoplePerDay" min="1" :max="eligibleUsers.length || 1">
                 <span class="input-group-text bg-light text-muted">คน / วัน</span>
               </div>
               <div class="form-text small" style="font-size: 0.75rem;">ระบบจะจัดคนลงในวันเดียวกันจนครบ แล้วจึงเปลี่ยนวัน</div>
             </div>
          </div>
          <div class="modal-footer border-0 pb-4 pe-4">
             <button type="button" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" @click="runAutoAssign">เริ่มจัดเวร</button>
          </div>
        </div>
      </div>
    </div>

    
    <div class="print-area d-none text-black bg-white">
      <div class="text-center mb-4">
        <h4 class="fw-bold mb-1" style="font-size: 1.4rem;">ตารางปฏิบัติงานนอกเวลาราชการ (เวร...{{ activeCommand?.ven_name_title || '' }})</h4>
        <h5 class="fw-bold" style="font-size: 1.2rem;">ประจำเดือน {{ formatMonthThai(currentMonth) }}</h5>
        <p class="mb-0">ตามคำสั่งเลขที่ {{ activeCommand?.com_num || '....................' }}</p>
      </div>
      
      <table class="table table-bordered border-dark print-table">
        <thead>
          <tr class="text-center align-middle" style="background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact;">
            <th style="width: 12%; border: 1px solid black;">วันที่</th>
            <th style="border: 1px solid black;" v-for="vns in subDuties" :key="vns.id"> {{vns.name}}</th>
            <th style="width: 20%; border: 1px solid black;">หมายเหตุ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="day in daysInMonth" :key="'print-'+day" >
            <td class="text-center align-middle fw-bold" style="border: 1px solid black; font-size: 1.1rem;">
              {{ day }} {{ getThaiDayShort(day) }}
            </td>
            <td class="p-2" style="border: 1px solid black;" v-for="vns in subDuties" :key="'print-'+day+'-'+vns.id">
              <div v-if="getSchedulesForDay(day).length === 0" class="text-muted small text-center">-</div>
              <div v-else class="d-flex flex-wrap gap-3">
                <div v-for="schedule in getSchedulesForDay(day)" :key="'p-'+schedule.id" class="mb-1">
                  <span class="fw-bold">{{schedule.com_id == activeCommand.id && schedule.sub_name == vns.name ? schedule.user_name : '-' }} {{ schedule }}</span> 
                  <!-- <span class="ms-1">{{ schedule.user_name }}</span> -->
                </div>
              </div>
            </td>
            <td style="border: 1px solid black;"></td>
          </tr>
        </tbody>
      </table>
      
      <div class="row mt-5 pt-3 text-center" style="page-break-inside: avoid;">
        <div class="col-6">
          <br>
          <p class="mb-5">(ลงชื่อ).......................................................ผู้จัดทำ</p>
          <p>(.......................................................)</p>
          <p>ตำแหน่ง .......................................................</p>
        </div>
        <div class="col-6">
          <p class="mb-2">ทราบ/อนุมัติ</p>
          <br>
          <p class="mb-5">(ลงชื่อ).......................................................ผู้อนุมัติ</p>
          <p>(.......................................................)</p>
          <p>ผู้อำนวยการฯ</p>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { useRoute } from 'vue-router'
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'

const route = useRoute()

// --- 🌟 จัดการเรื่องวันที่และภาษาไทย ---
const today = new Date()
const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม']

const selMonth = ref(String(today.getMonth() + 1).padStart(2, '0'))
const selYear = ref(today.getFullYear() + 543)
const currentMonth = ref(`${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`)

const yearList = computed(() => {
  const curY = today.getFullYear() + 543
  return [curY - 1, curY, curY + 1]
})

const updateCurrentMonth = () => {
  currentMonth.value = `${selYear.value - 543}-${selMonth.value}`
  onMonthChange()
}

// --- State อื่นๆ ---
const weekDays = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์']
const commands = ref([])
const activeCommand = ref('')
const subDuties = ref([])
const activeSubDuty = ref('')
const eligibleUsers = ref([])
const allSchedules = ref([])
let autoAssignModalInstance = null
const selectedStartUser = ref('')
const peoplePerDay = ref(1)

// --- Computed ---
const daysInMonth = computed(() => {
  const [y, m] = currentMonth.value.split('-')
  return new Date(y, m, 0).getDate()
})
const blankDays = computed(() => {
  const [y, m] = currentMonth.value.split('-')
  const dayOfWeek = new Date(y, m - 1, 1).getDay()
  return (dayOfWeek + 6) % 7
})
const filteredCommands = computed(() => commands.value.filter(com => com.ven_month === currentMonth.value))
const getSchedulesForDay = (day) => allSchedules.value.filter(s => parseInt(s.day) === day)
const hasSchedulesInActiveCommand = computed(() => activeCommand.value && allSchedules.value.some(s => s.com_id === activeCommand.value.id))
const hasSchedulesInActiveSubDuty = computed(() => {
  if (!activeCommand.value || !activeSubDuty.value) return false;
  return allSchedules.value.some(s => s.com_id === activeCommand.value.id && s.sub_id === activeSubDuty.value.id);
})

const isDayEnabled = (day) => {
  if (!activeCommand.value || !activeCommand.value.ven_com_days) return true 
  return activeCommand.value.ven_com_days.split(',').map(Number).includes(parseInt(day))
}

const formatMonthThai = (ym) => {
  const [y, m] = ym.split('-')
  return `${thaiMonths[parseInt(m)-1]} ${parseInt(y)+543}`
}

const getTextColor = (bg) => {
  const darks = ['blueviolet', 'brown', 'green', 'darkblue', 'crimson', '#0d6efd', '#dc3545']
  return darks.includes(bg?.toLowerCase()) ? '#fff' : '#000'
}

const isScheduleEditable = (sch) => {
  const scheduleCommand = commands.value.find(c => c.id === sch.com_id);
  return scheduleCommand ? scheduleCommand.status == 0 : false;
}

// --- API & Logic ---
const fetchCommands = async () => { commands.value = (await api.get('?route=admin/ven_com/list&action=list')).data || [] }
const fetchMonthSchedules = async () => { allSchedules.value = (await api.get(`?route=admin/ven_schedule/list_month&month=${currentMonth.value}`)).data || [] }

const onMonthChange = () => {
  activeCommand.value = ''; activeSubDuty.value = ''; subDuties.value = []; eligibleUsers.value = []
  fetchMonthSchedules()
}

const onCommandChange = async () => {
  if (!activeCommand.value) return
  const res = await api.get(`?route=admin/ven_schedule/ven_name_list`)
  const main = res.data.data.find(v => v.id === activeCommand.value.ven_name_id)
  subDuties.value = main?.subs?.sort((a, b) => a.srt - b.srt) || []
  activeSubDuty.value = ''; eligibleUsers.value = []
}

const loadEligibleUsers = async () => {
  if (activeSubDuty.value) eligibleUsers.value = (await api.get(`?route=admin/ven_schedule/user_list_by_sub&sub_id=${activeSubDuty.value.id}`)).data || []
}

// --- Drag & Drop ---
const startDragFromSidebar = (e, user) => {
  e.dataTransfer.setData('source', 'sidebar'); e.dataTransfer.setData('userID', user.user_id)
  e.dataTransfer.setData('userName', `${user.prefix||''}${user.name} ${user.sname}`)
}
const startDragFromCalendar = (e, sch) => {
  e.dataTransfer.setData('source', 'calendar'); e.dataTransfer.setData('scheduleID', sch.id)
}

// 🌟 ฟังก์ชันตรวจสอบ เวรเร่งรัด vs เวรกลางคืน// 🌟 ฟังก์ชันตรวจสอบ เวรเร่งรัด vs เวรกลางคืน (ละเว้นเวร 0 บาท)
const checkFastTrackAndNightConflict = (userId, targetDay, targetShiftStr, targetPrice) => {
  
  // 1. ถ้าเวรที่กำลังจะจัดมีราคา 0 บาท ให้ปล่อยผ่านได้เลย ไม่ต้องเช็คเงื่อนไขใดๆ
  if (targetPrice !== undefined && Number(targetPrice) === 0) return false;

  const userShiftsToday = allSchedules.value.filter(s => String(s.user_id) === String(userId) && parseInt(s.day) === parseInt(targetDay));
  
  const targetStr = (targetShiftStr || '').toLowerCase();
  const isTargetFastTrack = targetStr.includes('เร่งรัด') || targetStr.includes('16:30-20:00');
  const isTargetNight = targetStr.includes('กลางคืน') || targetStr.includes('16:30-08:30');

  for (const shift of userShiftsToday) {
    // 2. ถ้าเวรที่มีอยู่แล้วในระบบวันนั้นเป็นเวร 0 บาท ให้ข้ามการตรวจเวรนั้นไปเลย
    if (Number(shift.price) === 0) continue;

    const existingStr = `${shift.shift_type || ''} ${shift.sub_name || ''} ${shift.title || ''} ${shift.ven_time || ''}`.toLowerCase();

    const existingIsFastTrack = existingStr.includes('เร่งรัด') || existingStr.includes('16:30-20:00');
    const existingIsNight = existingStr.includes('กลางคืน') || existingStr.includes('16:30-08:30');

    // ถ้าพบว่าชนกัน (เร่งรัดชนกลางคืน)
    if ((isTargetFastTrack && existingIsNight) || (isTargetNight && existingIsFastTrack)) {
      return true; 
    }
  }
  return false;
};

const toggleCommandStatus = async (newStatus) => {
  const statusText = newStatus === 0 ? 'ยืนยันการจัดเวรและเปิดให้สมาชิกแลกเปลี่ยน?' : 'ปลดล็อคเพื่อกลับมาแก้ไขตารางเวร?';
  const result = await Swal.fire({
    title: newStatus === 0 ? 'ยืนยันการจัดเวร?' : 'ปลดล็อคแก้ไข?',
    text: statusText,
    icon: newStatus === 0 ? 'info' : 'warning',
    showCancelButton: true,
    confirmButtonText: 'ตกลง',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    try {
      await api.post('?route=admin/ven_com/update_status', { id: activeCommand.value.id, status: newStatus });
      activeCommand.value.status = newStatus;
      await fetchCommands();
      const updatedCom = commands.value.find(c => c.id === activeCommand.value.id);
      if(updatedCom) activeCommand.value = updatedCom;
      Swal.fire('สำเร็จ', 'อัปเดตสถานะคำสั่งเรียบร้อยแล้ว', 'success');
    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถอัปเดตสถานะได้', 'error');
    }
  }
}

// 🌟 ปรับปรุง onDrop เพิ่มระบบแจ้งเตือนก่อนบันทึก
const onDrop = async (e, day) => {
  const source = e.dataTransfer.getData('source')
  
  if (source === 'sidebar') {
    if (activeCommand.value && activeCommand.value.status != 0) {
      return Swal.fire('ถูกล็อค!', 'คำสั่งนี้ได้รับการยืนยันแล้ว โปรดปลดล็อคก่อนแก้ไข', 'warning');
    }
    if (!isDayEnabled(day)) {
      return Swal.fire({ title: 'ไม่อนุญาต!', text: 'วันนี้ไม่ได้ระบุไว้ในเงื่อนไขของคำสั่งนี้', icon: 'error', timer: 2000, showConfirmButton: false })
    }

    const uID = e.dataTransfer.getData('userID')
    if (!uID || !activeCommand.value || !activeSubDuty.value) return

    if (allSchedules.value.some(s => parseInt(s.day) === day && s.com_id === activeCommand.value.id && String(s.user_id) === String(uID))) {
      return Swal.fire('รายชื่อซ้ำ!', 'บุคคลนี้มีเวรในคำสั่งนี้ของวันนี้แล้ว', 'warning')
    }

    // 🌟 ดักเช็คเงื่อนไข เวรเร่งรัด vs เวรกลางคืน
    const shiftInfoStr = `${activeCommand.value.ven_name_title || ''} ${activeSubDuty.value.name || ''}`;
    
    // ดึงราคาของเวรที่กำลังจะจัด (อ้างอิงจาก activeSubDuty หรือ activeCommand)
    const newPrice = activeSubDuty.value?.price !== undefined ? activeSubDuty.value.price : activeCommand.value?.price;
    
    if (checkFastTrackAndNightConflict(uID, day, shiftInfoStr)) {
      const confirm = await Swal.fire({
        title: 'จัดเวรชนกัน!',
        text: 'เจ้าหน้าที่มี "เวรเร่งรัด" และ "เวรกลางคืน" ในวันเดียวกัน ต้องการจัดเวรนี้ต่อไปหรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันจัดเวร',
        cancelButtonText: 'ยกเลิก'
      });
      // ถ้าแอดมินกดยกเลิก ให้จบการทำงานตรงนี้เลย (ไม่บันทึก)
      if (!confirm.isConfirmed) return;
    }

    // บันทึกเมื่อไม่มีปัญหา หรือแอดมินกดยืนยันแล้ว
    await api.post('?route=admin/ven_schedule/add', { date: `${currentMonth.value}-${String(day).padStart(2,'0')}`, com_id: activeCommand.value.id, sub_id: activeSubDuty.value.id, user_id: uID })
    fetchMonthSchedules()
  } 
  
  else if (source === 'calendar') {
    const sID = e.dataTransfer.getData('scheduleID')
    const old = allSchedules.value.find(s => String(s.id) === String(sID))

    if (!old || parseInt(old.day) === day) return

    const scheduleCommand = commands.value.find(c => c.id === old.com_id)

    if (scheduleCommand && scheduleCommand.status != 0) {
      return Swal.fire('ถูกล็อค!', `คำสั่งเลขที่ ${scheduleCommand.com_num} ถูกยืนยันแล้ว ไม่สามารถย้ายได้`, 'warning');
    }

    if (scheduleCommand && scheduleCommand.ven_com_days) {
      const allowedDays = scheduleCommand.ven_com_days.split(',').map(Number)
      if (!allowedDays.includes(parseInt(day))) {
         return Swal.fire({ title: 'ย้ายไม่ได้!', text: `อนุญาตให้จัดเวรเฉพาะวันที่ ${scheduleCommand.ven_com_days} เท่านั้น`, icon: 'error', timer: 3000, showConfirmButton: false })
      }
    }

    if (allSchedules.value.some(s => parseInt(s.day) === day && s.com_id === old.com_id && String(s.user_id) === String(old.user_id))) {
      return Swal.fire('ย้ายไม่ได้!', 'มีชื่อบุคคลนี้ในวันนั้นอยู่แล้ว', 'warning')
    }

    // 🌟 ดักเช็คเงื่อนไข เวรเร่งรัด vs เวรกลางคืน (กรณีลากย้ายวัน)
    const oldShiftInfoStr = `${old.shift_type || ''} ${old.sub_name || ''} ${old.title || ''}`;
    if (checkFastTrackAndNightConflict(old.user_id, day, oldShiftInfoStr, old.price)) {
      const confirm = await Swal.fire({
        title: 'ย้ายเวรชนกัน!',
        text: 'เจ้าหน้าที่มี "เวรเร่งรัด" และ "เวรกลางคืน" ในวันเดียวกัน ต้องการย้ายมาวันนี้ต่อไปหรือไม่?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f39c12',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ยืนยันย้ายเวร',
        cancelButtonText: 'ยกเลิก'
      });
      if (!confirm.isConfirmed) return;
    }

    await api.post('?route=admin/ven_schedule/remove', { id: old.id })
    await api.post('?route=admin/ven_schedule/add', { date: `${currentMonth.value}-${String(day).padStart(2,'0')}`, com_id: old.com_id, sub_id: old.sub_id, user_id: old.user_id })
    fetchMonthSchedules()
  }
}

// ฟังก์ชันลบ
const removeSchedule = async (id) => {
  const schedule = allSchedules.value.find(s => s.id === id);
  if (schedule) {
    const scheduleCommand = commands.value.find(c => c.id === schedule.com_id);
    if (scheduleCommand && scheduleCommand.status != 0) {
      return Swal.fire('ถูกล็อค!', `คำสั่งเลขที่ ${scheduleCommand.com_num} ถูกยืนยันแล้ว ไม่สามารถลบได้`, 'warning');
    }
  }

  if ((await Swal.fire({ title: 'ลบชื่อนี้?', icon: 'warning', showCancelButton: true })).isConfirmed) { 
    await api.post('?route=admin/ven_schedule/remove', { id }); 
    fetchMonthSchedules();
  } 
}

const clearSubDutySchedules = async () => {
  if (!activeCommand.value || !activeSubDuty.value) return;

  const result = await Swal.fire({ 
    title: 'ล้างเวรหน้าที่นี้?', 
    text: `ต้องการลบรายชื่อผู้เข้าเวรในหน้าที่ "${activeSubDuty.value.name}" ทั้งหมดในคำสั่งนี้ใช่หรือไม่?`, 
    icon: 'warning', 
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ใช่, ล้างเลย',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    Swal.fire({ title: 'กำลังลบข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    const toDel = allSchedules.value.filter(s => s.com_id === activeCommand.value.id && s.sub_id === activeSubDuty.value.id);
    await Promise.all(toDel.map(s => api.post('?route=admin/ven_schedule/remove', { id: s.id })));
    fetchMonthSchedules();
    Swal.fire('สำเร็จ', `ล้างข้อมูลหน้าที่ "${activeSubDuty.value.name}" เรียบร้อยแล้ว`, 'success');
  }
}

const openAutoAssignModal = () => { if (eligibleUsers.value.length > 0) { selectedStartUser.value = eligibleUsers.value[0].user_id; autoAssignModalInstance.show() } }

const runAutoAssign = async () => {
  const days = activeCommand.value.ven_com_days?.split(',').map(Number).sort((a,b)=>a-b) || []
  let idx = eligibleUsers.value.findIndex(u => u.user_id === selectedStartUser.value)
  if (idx === -1) idx = 0

  const payloads = []
  const assignCount = Math.min(parseInt(peoplePerDay.value) || 1, eligibleUsers.value.length)

  days.forEach(d => {
    for (let i = 0; i < assignCount; i++) {
      const u = eligibleUsers.value[idx]
      const targetDate = `${currentMonth.value}-${String(d).padStart(2,'0')}`
      const alreadyInDb = allSchedules.value.some(s => parseInt(s.day) === d && s.com_id === activeCommand.value.id && String(s.user_id) === String(u.user_id))
      const alreadyInPayload = payloads.some(p => p.date === targetDate && String(p.user_id) === String(u.user_id))

      if (!alreadyInDb && !alreadyInPayload) {
        payloads.push({ date: targetDate, com_id: activeCommand.value.id, sub_id: activeSubDuty.value.id, user_id: u.user_id })
      }
      idx = (idx + 1) % eligibleUsers.value.length
    }
  })
  
  if (payloads.length) {
    Swal.fire({ title: 'กำลังประมวลผล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() })
    for (const payload of payloads) { await api.post('?route=admin/ven_schedule/add', payload) }
    autoAssignModalInstance.hide()
    peoplePerDay.value = 1 
    fetchMonthSchedules()
    Swal.fire('สำเร็จ', `จัดเวรอัตโนมัติเรียบร้อย (${payloads.length} คิว)`, 'success')
  } else {
    autoAssignModalInstance.hide()
    Swal.fire('แจ้งเตือน', 'ไม่มีวันที่ว่าง หรือรายชื่อถูกจัดครบแล้ว', 'info')
  }
}

const isClashing = (sch) => {
  // 1. ถ้าเวรเป้าหมายนี้ price = 0 ให้ข้ามการตรวจสอบไปเลย
  if (Number(sch.price) === 0) { return false; }

  // 2. 🌟 ดึงเฉพาะเวรของ User นี้ "และต้องไม่ใช่เวรที่ price = 0" มาตรวจสอบ
  const userShifts = allSchedules.value.filter(s => s.user_id === sch.user_id && Number(s.price) !== 0);
  
  const sameDay = userShifts.filter(s => parseInt(s.day) === parseInt(sch.day));
  
  // เช็คเวรในวันเดียวกัน (มีทั้งกลางวันและกลางคืน)
  if (sameDay.length > 1 && sameDay.some(s => s.shift_type.includes('กลางวัน')) && sameDay.some(s => s.shift_type.includes('กลางคืน'))) return true;
  
  // เช็คเวรข้ามวัน (กลางคืนวันนี้ ต่อด้วย กลางวันพรุ่งนี้)
  if (sch.shift_type.includes('กลางคืน') && userShifts.find(s => parseInt(s.day) === parseInt(sch.day)+1 && s.shift_type.includes('กลางวัน'))) return true;
  
  // เช็คเวรข้ามวันย้อนหลัง (กลางวันวันนี้ ต่อจาก กลางคืนเมื่อวาน)
  if (sch.shift_type.includes('กลางวัน') && userShifts.find(s => parseInt(s.day) === parseInt(sch.day)-1 && s.shift_type.includes('กลางคืน'))) return true;
  
  return false;
}

const getThaiDayShort = (day) => {
  const [y, m] = currentMonth.value.split('-')
  const date = new Date(y, m - 1, day)
  const days = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.']
  return days[date.getDay()]
}

const printSchedule = () => {
  if (!activeCommand.value) {
    return Swal.fire('แจ้งเตือน', 'โปรดเลือกคำสั่งก่อนพิมพ์รายงาน', 'warning')
  }
  window.print()
}
// 🌟 ฟังก์ชันแสดงรายละเอียดเวรเมื่อกดคลิกที่บล็อก
const showScheduleDetails = (sch) => {
  Swal.fire({
    title: '<i class="bi bi-info-circle-fill text-primary"></i> รายละเอียดการจัดเวร',
    html: `
      <div class="text-start mt-3 p-3 bg-light rounded-3" style="font-size: 0.95rem; line-height: 1.8;">
        <div class="d-flex align-items-center mb-3">
          <i class="bi bi-person-badge fs-4 text-secondary me-3"></i>
          <div style="line-height: 1.2;">
            <small class="text-muted d-block mb-1">ผู้ปฏิบัติหน้าที่</small>
            <strong class="text-dark fs-6">${sch.user_name || sch.title || 'ไม่ระบุ'}</strong>
          </div>
        </div>
        
        <div class="d-flex align-items-center mb-3">
          <i class="bi bi-tag-fill fs-4 text-primary me-3"></i>
          <div style="line-height: 1.2;">
            <small class="text-muted d-block mb-1">คำสั่ง / หน้าที่</small>
            <strong class="text-dark">${sch.com_num || '-'} | ${sch.sub_name || '-'}</strong>
          </div>
        </div>
        
        <div class="d-flex align-items-center mb-3">
          <i class="bi bi-clock-fill fs-4 text-warning me-3"></i>
          <div style="line-height: 1.2;">
            <small class="text-muted d-block mb-1">เวลาปฏิบัติงาน</small>
            <strong class="text-dark">${sch.ven_time || '-'} น.</strong>
          </div>
        </div>
        
        <div class="d-flex align-items-center">
          <i class="bi bi-cash-coin fs-4 text-success me-3"></i>
          <div style="line-height: 1.2;">
            <small class="text-muted d-block mb-1">ค่าตอบแทน</small>
            <strong class="text-dark">${Number(sch.price) > 0 ? sch.price + ' บาท' : 'ไม่มีค่าตอบแทน (0 บาท)'}</strong>
          </div>
        </div>
      </div>
    `,
    showCancelButton: true,
    showConfirmButton: true,
    confirmButtonColor: '#dc3545', // สีแดงสำหรับปุ่มลบ
    cancelButtonColor: '#6c757d',  // สีเทาสำหรับปุ่มปิด
    confirmButtonText: '<i class="bi bi-trash3-fill"></i> ลบเวรนี้',
    cancelButtonText: 'ปิดหน้าต่าง',
    reverseButtons: true // สลับให้ปุ่มปิดอยู่ขวา ปุ่มลบอยู่ซ้าย
  }).then((result) => {
    // ถ้าแอดมินกดปุ่ม "ลบเวรนี้" จะไปเรียกฟังก์ชัน removeSchedule ที่คุณมีอยู่แล้ว
    if (result.isConfirmed) {
      removeSchedule(sch.id); 
    }
  });
};

// 🌟 ฟังก์ชันส่งข้อมูลขึ้น Google Calendar
const syncToGoogle = async () => {
  // ตรวจสอบว่ามีการเลือกเดือนหรือยัง (ปรับตัวแปร currentMonth.value ให้ตรงกับของคุณ)
  const monthToSync = currentMonth.value; 
  
  if (!monthToSync) {
    Swal.fire('แจ้งเตือน', 'กรุณาเลือกเดือนที่ต้องการซิงค์', 'warning');
    return;
  }

  // ถามยืนยันก่อนเริ่มซิงค์
  const result = await Swal.fire({
    title: 'ยืนยันการซิงค์ข้อมูล?',
    text: "ระบบจะนำข้อมูลการจัดเวรล่าสุดของเดือนนี้ ส่งขึ้นไปอัปเดตบน Google Calendar แยกตามประเภทเวรที่คุณได้ตั้งค่าไว้",
    icon: 'info',
    showCancelButton: true,
    confirmButtonColor: '#db4437', // สีแดงสไตล์ Google
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-cloud-upload"></i> ตกลง, เริ่มซิงค์เลย',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    // แสดงหน้าต่างโหลด
    Swal.fire({
      title: 'กำลังซิงค์ข้อมูล...',
      html: 'โปรดรอสักครู่ ระบบกำลังสื่อสารกับเซิร์ฟเวอร์ของ Google<br><small class="text-muted">อาจใช้เวลา 10-30 วินาที ขึ้นอยู่กับจำนวนข้อมูล</small>',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    try {
      // เรียก API ไปยัง Backend PHP ของเรา
      const response = await api.post('?route=admin/ven_schedule/sync_google', {
        month: monthToSync 
      });

      // ถ้า Backend ทำงานสำเร็จ
      if (response.data.success) {
        Swal.fire(
          'สำเร็จ!',
          response.data.message || 'อัปเดตตารางเวรบน Google Calendar เรียบร้อยแล้ว',
          'success'
        );
      } else {
        // กรณีเชื่อมต่อได้แต่ไม่มีข้อมูลเวร
        Swal.fire('เสร็จสิ้น', response.data.message || 'ไม่มีข้อมูลเวรใหม่ให้ซิงค์', 'info');
      }

    } catch (error) {
      console.error("Sync Google Error:", error);
      // กรณีเกิด Error จากฝั่ง Backend หรือ Google API
      Swal.fire(
        'เกิดข้อผิดพลาด!',
        error.response?.data?.error || 'ไม่สามารถเชื่อมต่อ Google API ได้ โปรดตรวจสอบการตั้งค่า Service Account',
        'error'
      );
    }
  }
};

// 🌟 แก้ไข onMounted ให้ตั้งค่า Parameter ครบถ้วน
onMounted(async () => { 
  await fetchCommands(); 
  
  autoAssignModalInstance = new Modal(document.getElementById('autoAssignModal'))
  
  if (route.query.com_id || route.query.month) {    
    if (route.query.month) {
      const [y, m] = route.query.month.split('-');
      selMonth.value = m;
      selYear.value = parseInt(y) + 543; 
      currentMonth.value = `${y}-${m}`; 
    }
    
    // 🌟 ดึงคอมมานด์มาแสดงอัตโนมัติ
    if (route.query.com_id) {
      // ค้นหา command จาก id ที่ส่งมา และตั้งเป็น activeCommand
      const foundCom = commands.value.find(c => String(c.id) === String(route.query.com_id));
      if (foundCom) {
        activeCommand.value = foundCom;
        // โหลดหน้าที่ย่อยของคำสั่งนี้มาใส่ Dropdown ถัดไปทันที
        await onCommandChange();
      }
    }
  }
  await fetchMonthSchedules();     
})
</script>

<style scoped>
.btn-xs { padding: 0.25rem 0.5rem; font-size: 0.7rem; }
.calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); grid-auto-rows: minmax(130px, auto); gap: 4px; }
.day-cell { transition: 0.2s; min-width: 0; font-size: 0.8rem; }
.day-cell.blank { border: none !important; background: none !important; }
.day-cell.bg-white { border: 1px solid #dee2e6; }
.bg-secondary-subtle { background-color: #f1f3f5 !important; cursor: not-allowed; }
.user-card, .schedule-item { cursor: grab; transition: 0.1s; }
.user-card:active, .schedule-item:active { cursor: grabbing; transform: scale(0.95); opacity: 0.8; }
.schedule-item { border-width: 1px !important; }

.user-card {
  cursor: grab; /* เปลี่ยนเมาส์เป็นรูปมือจับ */
  transition: all 0.2s ease-in-out; /* ทำให้การเปลี่ยนเอฟเฟกต์ดูนุ่มนวล */
}

/* เอฟเฟกต์ตอนเอาเมาส์ไปชี้ (Hover) */
.user-card:hover {
  background-color: #f8f9fa !important; /* เปลี่ยนพื้นหลังเป็นสีเทาอ่อนๆ */
  transform: translateY(-2px); /* ทำให้กล่องลอยขึ้นด้านบน 2px */
  box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15) !important; /* เพิ่มเงาให้เข้มขึ้น */
}

/* เอฟเฟกต์ตอนกำลังคลิกค้างเพื่อลาก (Active / Dragging) */
.user-card:active {
  cursor: grabbing; /* เปลี่ยนรูปเมาส์เป็นมือกำ (กำลังจับ) */
  transform: translateY(0); /* ดึงกล่องกลับลงมา */
  box-shadow: none !important; /* ลดเงาลงเวลาถูกกด */
}

.sticky-name-list {
  /* ทำให้บล็อกนี้เกาะติดหน้าจอเวลาเลื่อนลง */
  position: -webkit-sticky;
  position: sticky;
  top: 20px; /* ระยะห่างจากขอบจอด้านบน ปรับตัวเลขได้ตามความเหมาะสม */
  
  /* จำกัดความสูงไม่ให้เกินหน้าจอ เพื่อให้ Scroll ได้ */
  max-height: calc(100vh - 100px); 
  
  /* ถ้าตารางรายชื่อยาวเกินความสูงที่กำหนด ให้เกิด Scrollbar ด้านในบล็อกแทน */
  overflow-y: auto; 
  
  /* เพิ่มเงาเล็กน้อยให้ดูมีมิติแยกลอยออกมา (ใส่หรือไม่ใส่ก็ได้ครับ) */
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

/* 🌟 Custom Scrollbar */
.user-list-container, .custom-scrollbar { overflow-y: auto; }
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #f8f9fa; }
::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #0d6efd; }

.clash-warning { border: 2px solid #ffc107 !important; }
.blink { animation: blinker 2s linear infinite; }
@keyframes blinker { 50% { opacity: 0.5; background-color: #dc3545 !important; color: white !important; } }

/* ==========================================
   🌟 CSS สำหรับการพิมพ์ (Print Mode)
   ========================================== */
@media print {
  /* ซ่อนทุกอย่างยกเว้น print-area */
  body * { visibility: hidden; }
  .print-area, .print-area * { visibility: visible; }
  .print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    display: block !important;
  }
  
  /* เส้นตารางเข้มขึ้น */
  .print-table { border: 2px solid black !important; }
  .print-table th, .print-table td { 
    border: 1px solid black !important; 
    color: black !important;
  }

  @page {
    size: A4;
    margin: 1.5cm;
  }
}
</style>