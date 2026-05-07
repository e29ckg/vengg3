<template>
  <div class="bg-light min-vh-100 d-flex flex-column pb-4">
    <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column">
      <div class="card shadow-sm border-0 rounded-4 flex-grow-1 d-flex flex-column overflow-hidden">        
        <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
          <h4 class="fw-bold mb-0 text-primary">
            <i class="bi bi-calendar3 me-2"></i>ตารางเวรเดือน {{ formatMonthThai(currentMonth) }}
          </h4>
          
          <div class="mt-1">
            <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill small fw-normal">
              <i class="bi bi-info-circle me-1"></i>
              <span v-if="systemSettings.advance_swap_days > 0">
                ต้องทำรายการล่วงหน้าอย่างน้อย {{ systemSettings.advance_swap_days }} วัน
              </span>
              <span v-else>สามารถทำรายการแลกเปลี่ยนเวรได้ภายในวันเดียวกัน</span>
            </span>
          </div>

          <div class="d-flex gap-2">
            <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border">
              <button class="btn btn-light border-end" @click="changeMonth(-1)"><i class="bi bi-chevron-left"></i></button>
              <select class="form-select border-0 text-center fw-bold bg-white" v-model="selMonth" @change="updateCurrentMonth" style="width: 120px;">
                <option v-for="(m, idx) in thaiMonths" :key="idx" :value="String(idx + 1).padStart(2, '0')">{{ m }}</option>
              </select>
              <select class="form-select border-0 border-start text-center fw-bold bg-white" v-model="selYear" @change="updateCurrentMonth" style="width: 100px;">
                <option v-for="y in yearList" :key="y" :value="y">{{ y }}</option>
              </select>
              <button class="btn btn-light border-start" @click="changeMonth(1)"><i class="bi bi-chevron-right"></i></button>
            </div>
            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm" @click="goToToday">วันนี้</button>
          </div>
        </div>

        <div v-if="isLoading" class="text-center py-5 flex-grow-1 d-flex flex-column justify-content-center">
          <div class="spinner-border text-primary mx-auto mb-2" role="status"></div>
          <p class="text-muted fw-semibold">กำลังดึงข้อมูลปฏิทิน...</p>
        </div>

        <div v-else class="d-flex flex-column flex-grow-1 px-4 pb-4 overflow-hidden">
          
          <div class="row g-0 bg-dark text-white fw-bold text-center py-2 shadow-sm rounded-top-3">
            <div class="col" v-for="day in weekDays" :key="day">{{ day }}</div>
          </div>

          <div class="calendar-grid bg-light flex-grow-1 overflow-auto p-2 border border-top-0 rounded-bottom-3 custom-scrollbar">
            <div class="day-cell blank" v-for="b in blankDays" :key="'b'+b"></div>

            <div class="day-cell border rounded-3 d-flex flex-column bg-white shadow-sm transition-hover" 
                 v-for="day in daysInMonth" :key="day">
              
              <div class="day-header fw-bold border-bottom px-2 py-1 text-end"
                   :class="{ 'bg-primary text-white': isToday(day), 'bg-light text-secondary': !isToday(day) }">
                {{ day }}
              </div>
              
              <div class="day-body p-1 flex-grow-1 overflow-auto custom-scrollbar">
                
                <div v-for="sch in getSchedulesForDay(day)" :key="sch.id" 
     class="schedule-item mb-1 p-1 rounded-1 shadow-sm"
     :class="{ 'flashing-24h': Number(sch.price) !== 0 && is24HourShift(sch) }"
     :style="{ 
       backgroundColor: Number(sch.price) === 0 ? sch.backgroundColor : (is24HourShift(sch) ? '' : (sch.user_id == currentUserId ? '#FFD700' : sch.backgroundColor)), 
       color: Number(sch.price) === 0 ? '#fff' : (is24HourShift(sch) ? 'white' : (sch.user_id == currentUserId ? '#000' : '#fff'))
     }"
     @click="openShiftDetail(sch.id)">
  
  <div class="fw-bold" style="font-size: 0.7rem;">
    <i class="bi bi-clock me-1"></i>{{ sch.ven_time.substring(0,5) }}
    
    <i v-if="Number(sch.price) !== 0 && is24HourShift(sch)" class="bi bi-exclamation-triangle-fill ms-1 text-danger"></i>
    <i v-else-if="Number(sch.price) !== 0 && sch.user_id == currentUserId" class="bi bi-star-fill ms-1 text-warning"></i>
  </div>

  <div class="text-truncate fw-semibold" style="font-size: 0.85rem;">{{ sch.title }}</div>

</div>

              </div>
            </div>
          </div>

        </div>
        <Teleport to="body">
          <div class="modal fade" id="venDetailModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content border-0 shadow-lg rounded-4" v-if="selectedVen">
                
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title text-muted fs-6" style="font-family: monospace;">ID: {{ selectedVen.ven_id || selectedVen.id }}</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
    
                <div class="modal-body text-center pt-2 px-4 pb-4">
                  <img :src="getProfileImage(selectedVen)" class="rounded-circle shadow-sm mb-3 object-fit-cover border border-3 border-white" style="width: 100px; height: 100px;">
                  <h4 class="fw-bold mb-1" :style="{ color: selectedVen.color }">{{ selectedVen.full_name }}</h4>
                  <span class="badge mb-3 px-3 py-2 rounded-pill shadow-sm" :style="{ backgroundColor: selectedVen.color }">
                    {{ selectedVen.duty_role }}
                  </span>
    
                  <div class="bg-light border rounded-3 p-3 text-start mb-4 shadow-sm">
                    <p class="mb-2"><i class="bi bi-calendar-event me-2 text-primary"></i> 
                      <strong>{{ formatDate(selectedVen.ven_date || selectedVen.date) }}</strong> 
                      <span v-if="selectedVen.ven_time">(เวลา {{ selectedVen.ven_time.substring(0,5) }} น.)</span>
                    </p>
                    <p class="mb-2"><i class="bi bi-moon-stars me-2 text-warning"></i> {{ selectedVen.duty_main }}</p>
                    
                    <p v-if="selectedVen.command_num" class="mb-2 text-muted small">
                      <i class="bi bi-file-earmark-text me-2"></i> คำสั่งเลขที่ {{ selectedVen.command_num }} 
                      <span v-if="selectedVen.command_date">ลงวันที่ {{ formatDate(selectedVen.command_date) }}</span>
                    </p>
    
                    <p class="mb-0 text-success fw-bold fs-5"><i class="bi bi-cash-coin me-2"></i> {{ Number(selectedVen.price).toLocaleString() }} บาท</p>
                  </div>
    
                  <div v-if="selectedVen.user_id == currentUserId">
                    
                    <div v-if="selectedVen.com_status != 1" class="alert alert-warning border-0 small text-start mt-2 shadow-sm">
                      <i class="bi bi-exclamation-circle-fill me-1"></i> 
                      <strong>หมายเหตุ:</strong> คำสั่งนี้อยู่ระหว่างรออนุมัติ ท่านจะสามารถโอนเวรได้หลังจากคำสั่งได้รับการอนุมัติแล้วเท่านั้น
                    </div>
    
                    <template v-else>
                      <button class="btn btn-primary rounded-pill fw-bold shadow-sm py-2 w-100 mb-2">
                        <i class="bi bi-file-earmark-check me-1"></i> รายงานการปฏิบัติหน้าที่
                      </button>
                      
                      <div class="mb-3 text-start px-1" v-if="selectedVen.user_id == currentUserId">
                        <div class="d-flex align-items-center text-muted small">
                          <i class="bi bi-shield-check me-2 text-success"></i>
                          <span>
                            กฎการเปลี่ยนเวร: 
                            <b>{{ systemSettings.advance_swap_days === 0 ? 'ทำรายการได้ทันที' : 'ล่วงหน้า ' + systemSettings.advance_swap_days + ' วัน' }}</b>
                          </span>
                        </div>
                      </div>

                      <template v-if="!isPastShift(selectedVen.ven_date || selectedVen.date, selectedVen.ven_time) || systemSettings.allow_retroactive_swap">                        
                        <button v-if="!showRecipientList" 
                                class="btn btn-warning rounded-pill fw-bold text-dark shadow-sm py-2 w-100" 
                                @click="loadRecipients">
                          <i class="bi bi-arrow-right-circle me-1"></i> ยกเวรนี้ให้ผู้อื่น (โอนขาด)
                        </button>
      
                        <div v-if="showRecipientList" class="mt-3 text-start border border-warning rounded-3 p-2 bg-white shadow-sm">
                          <label class="form-label fw-bold small text-primary mb-2 px-1">เลือกผู้ที่ต้องการยกเวรให้:</label>
                          <div class="list-group list-group-flush border rounded-3 overflow-auto custom-scrollbar" style="max-height: 180px;">
                            <button v-for="user in recipients" :key="user.user_id" 
                                    class="list-group-item list-group-item-action small py-2 d-flex align-items-center"
                                    @click="confirmTransfer(user)">
                              <i class="bi bi-person-plus-fill me-2 text-success fs-5"></i>
                              <span class="fw-semibold">{{ user.full_name || (user.prefix_name + user.first_name + ' ' + user.last_name) }}</span>
                            </button>
                            <div v-if="recipients.length === 0" class="p-3 text-center text-muted small">ไม่พบผู้มีสิทธิ์ท่านอื่นในหน้าที่นี้</div>
                          </div>
                          <div class="text-center mt-2">
                            <button class="btn btn-link btn-sm text-decoration-none text-muted" @click="showRecipientList = false">ยกเลิก</button>
                          </div>
                        </div>
                      </template>
                      
                      <div v-else class="alert alert-danger border-0 small text-center mt-2 shadow-sm rounded-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> ระบบไม่อนุญาตให้เปลี่ยนเวรย้อนหลัง
                      </div>
                    </template>
                  </div>

                  <div v-else-if="systemSettings.allow_swap" class="mt-3 text-start border border-warning rounded-3 p-3 bg-white shadow-sm">
                    <h6 class="fw-bold text-warning mb-2"><i class="bi bi-arrow-left-right"></i> ขอสลับเปลี่ยนเวร</h6>
                    
                    <div v-if="selectedVen.com_status != 1" class="alert alert-warning border-0 small text-start mt-2 shadow-sm">
                      <i class="bi bi-exclamation-circle-fill me-1"></i> 
                      รอคำสั่งอนุมัติก่อน จึงจะสามารถสลับเวรได้
                    </div>
                    
                    <template v-else>
                      <template v-if="!isPastShift(selectedVen.ven_date || selectedVen.date, selectedVen.ven_time) || systemSettings.allow_retroactive_swap">

                        <div class="mb-3 text-start px-1" v-if="selectedVen.user_id == currentUserId">
                          <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-shield-check me-2 text-success"></i>
                            <span>
                              กฎการเปลี่ยนเวร: 
                              <b>{{ systemSettings.advance_swap_days === 0 ? 'ทำรายการได้ทันที' : 'ล่วงหน้า ' + systemSettings.advance_swap_days + ' วัน' }}</b>
                            </span>
                          </div>
                        </div>

                        <label class="form-label text-secondary small fw-bold mt-1">เลือกเวรของคุณเพื่อเสนอแลกเปลี่ยน:</label>
                        <select class="form-select mb-3 border-warning" v-model="selectedMyShiftId">
                          <option value="" disabled>-- เลือกเวรของคุณ (คำสั่ง/หน้าที่เดียวกัน) --</option>
                          <option v-for="shift in mySwappableShifts" :key="shift.id || shift.ven_id" :value="shift.id || shift.ven_id">
                            วันที่ {{ formatDate(shift.date || shift.ven_date) }} <span v-if="shift.ven_time">({{ shift.ven_time.substring(0,5) }} น.)</span>
                          </option>
                        </select>
                        
                        <button class="btn btn-warning rounded-pill fw-bold text-dark shadow-sm py-2 w-100" 
                                @click="confirmSwap"
                                :disabled="!selectedMyShiftId">
                          <i class="bi bi-check-circle me-1"></i> ยืนยันขอสลับเวร
                        </button>

                        <div v-if="mySwappableShifts.length === 0" class="text-danger small mt-2 text-center">
                          <i class="bi bi-exclamation-circle"></i> คุณไม่มีเวรในคำสั่งและหน้าที่เดียวกันที่นำมาแลกได้
                        </div>
                      </template>
                      
                      <div v-else class="alert alert-danger border-0 small text-center mt-2 shadow-sm rounded-3">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> ระบบไม่อนุญาตให้สลับเวรย้อนหลัง
                      </div>
                    </template>
                  </div>
                  
                  <div v-else class="alert alert-secondary border-0 small text-center mt-3 mb-4 shadow-sm rounded-3">
                    <i class="bi bi-info-circle me-1"></i> การเปลี่ยนเวรหรือยกเวร สามารถทำได้เฉพาะเวรของตนเองเท่านั้น
                  </div>
    
                  <div class="timeline-container mt-4 pt-4 border-top text-start">
                    <h6 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>ประวัติการเปลี่ยนเวร</h6>
                    
                    <div v-if="selectedVen.history && selectedVen.history.length > 0">
                      <div v-for="(history, index) in selectedVen.history" :key="index" class="card mb-2 border-0 shadow-sm bg-light">
                        <div class="card-body p-3">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-primary">ใบเปลี่ยนเลขที่: {{ history.change_no || history.change_id }}</span>
                            
                            <span v-if="history.status == 1" class="badge bg-success">อนุมัติแล้ว</span>
                            <span v-else class="badge bg-warning text-dark">รออนุมัติ</span>
                          </div>
                          
                          <p class="mb-1 text-muted" style="font-size: 0.9rem;">
                            {{ history.user1_name }} 
                            <i v-if="history.is_swap == 1" class="bi bi-arrow-left-right mx-1 text-warning fw-bold"></i>
                            <i v-else class="bi bi-arrow-right mx-1 text-primary fw-bold"></i> 
                            {{ history.user2_name }}
                          </p>
                          
                          <div class="d-flex justify-content-end gap-2 mt-2">
                            <button v-if="history.status == 0 || history.status == 1" 
                                    @click="downloadWordForm(history)" 
                                    class="btn btn-sm btn-outline-primary bg-white">
                              <i class="bi bi-file-earmark-word"></i> พิมพ์ใบเปลี่ยนเวร
                            </button>
                            <button v-if="history.status == 0 && history.user1_id == currentUserId" 
                                    @click="cancelChange(history.change_id)" 
                                    class="btn btn-sm btn-outline-danger bg-white">
                              <i class="bi bi-x-circle"></i> ยกเลิก
                            </button>                    
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div v-else class="text-center text-muted py-3 bg-light rounded-3 border">
                      <small>ไม่มีประวัติการเปลี่ยนเวร</small>
                    </div>
                  </div>

                </div>
              </div>
            </div>    
          </div>
        </Teleport>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { exportShiftChangeToWord } from '../services/wordService';
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'
import api from '../services/api'

const router = useRouter()
const isLoading = ref(true)

const currentUserId = ref(0)
const currentUsername = ref('')
const userRole = ref(0)

// 🌟 ตัวแปรเก็บกฎการตั้งค่าระบบ (ดึงมาจาก Backend)
const systemSettings = ref({
  allow_swap: true,
  advance_swap_days: 3,
  allow_retroactive_swap: false,
  check_24h_consecutive: true
});

// ฟังก์ชันดึงข้อมูลการตั้งค่าระบบ
const fetchSystemSettings = async () => {
  try {
    const res = await api.get('?route=system_settings');
    if (res.data) {
      systemSettings.value = {
        allow_swap: res.data.allow_swap == 1,
        advance_swap_days: parseInt(res.data.advance_swap_days) || 0,
        allow_retroactive_swap: res.data.allow_retroactive_swap == 1,
        check_24h_consecutive: res.data.check_24h_consecutive == 1
      };
    }
  } catch (error) {
    console.error('Error fetching settings:', error);
  }
};

const fetchUserInfo = async () => {
  try {
    const response = await api.get('?route=auth/me')
    
    if (response.data.success) {
      currentUserId.value = response.data.user_id
      currentUsername.value = response.data.username
      userRole.value = response.data.role
      
      await fetchSystemSettings() // 🌟 ดึงข้อมูล Settings หลังจาก Login เสร็จ
      
      localStorage.setItem('user_id', response.data.user_id)
      localStorage.setItem('role', response.data.role)
    }
  } catch (error) {
    console.error("ยืนยันตัวตนไม่สำเร็จ:", error)
    handleLogout()
  }
}

const isAdminMenuOpen = ref(false)
const toggleAdminMenu = () => { 
  isAdminMenuOpen.value = !isAdminMenuOpen.value 
  if (isAdminMenuOpen.value) isDirectorMenuOpen.value = false
  }
const isDirectorMenuOpen = ref(false)
const toggleDirectorMenu = () => { 
  isDirectorMenuOpen.value = !isDirectorMenuOpen.value 
  if (isDirectorMenuOpen.value) isAdminMenuOpen.value = false
}

const todayDate = new Date()
const thaiMonths = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม']
const weekDays = ['จันทร์', 'อังคาร', 'พุธ', 'พฤหัสบดี', 'ศุกร์', 'เสาร์', 'อาทิตย์']

const selMonth = ref(String(todayDate.getMonth() + 1).padStart(2, '0'))
const selYear = ref(todayDate.getFullYear() + 543)
const currentMonth = ref(`${todayDate.getFullYear()}-${String(todayDate.getMonth() + 1).padStart(2, '0')}`)

const allSchedules = ref([])

const yearList = computed(() => {
  const curY = todayDate.getFullYear() + 543
  return [curY - 1, curY, curY + 1, curY + 2]
})

const daysInMonth = computed(() => {
  const [y, m] = currentMonth.value.split('-')
  return new Date(y, m, 0).getDate()
})

const blankDays = computed(() => {
  const [y, m] = currentMonth.value.split('-')
  const dayOfWeek = new Date(y, m - 1, 1).getDay()
  return (dayOfWeek + 6) % 7
})

const isToday = (day) => {
  const dateStr = `${currentMonth.value}-${String(day).padStart(2, '0')}`
  const todayStr = `${todayDate.getFullYear()}-${String(todayDate.getMonth() + 1).padStart(2, '0')}-${String(todayDate.getDate()).padStart(2, '0')}`
  return dateStr === todayStr
}

const getSchedulesForDay = (day) => {
  if (!Array.isArray(allSchedules.value)) return [] 
  
  const dateStr = `${currentMonth.value}-${String(day).padStart(2, '0')}`
  return allSchedules.value.filter(s => s.date === dateStr)
}

const updateCurrentMonth = () => {
  currentMonth.value = `${selYear.value - 543}-${selMonth.value}`
  fetchVenData()
}

const changeMonth = (step) => {
  let m = parseInt(selMonth.value) + step
  let y = parseInt(selYear.value)
  if (m > 12) { m = 1; y++ }
  else if (m < 1) { m = 12; y-- }
  selMonth.value = String(m).padStart(2, '0')
  selYear.value = y
  updateCurrentMonth()
}

const goToToday = () => {
  selMonth.value = String(todayDate.getMonth() + 1).padStart(2, '0')
  selYear.value = todayDate.getFullYear() + 543
  updateCurrentMonth()
}

const formatMonthThai = (ym) => {
  const [y, m] = ym.split('-')
  return `${thaiMonths[parseInt(m)-1]} ${parseInt(y)+543}`
}

const fetchVenData = async () => {
  isLoading.value = true
  const token = localStorage.getItem('token')
  try {
    const response = await api.get(`?route=ven/list&monthYear=${currentMonth.value}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    
    allSchedules.value = Array.isArray(response.data) ? response.data : []
    
  } catch (error) {
    console.error("Error fetching calendar:", error)
    allSchedules.value = [] 
    
    if (error.response?.status === 401) {
      Swal.fire('เซสชันหมดอายุ', 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 'warning')
      handleLogout()
    }
  } finally {
    isLoading.value = false
  }
}

const selectedVen = ref(null)
let detailModalInstance = null 
const recipients = ref([])
const showRecipientList = ref(false)

const openShiftDetail = async (venId) => {
  try {
    Swal.fire({ 
      title: 'กำลังโหลดข้อมูล...', 
      allowOutsideClick: false, 
      didOpen: () => Swal.showLoading() 
    });
    
    const response = await api.get(`?route=ven/detail&id=${venId}`);

    selectedVen.value = response.data;
    showRecipientList.value = false; 
    
    Swal.close();
    detailModalInstance.show();

  } catch (error) {
    console.error("Error fetching detail:", error);
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงรายละเอียดเวรได้ หรือเซสชันหมดอายุ', 'error');
  }
}

const loadRecipients = async () => {
  try {
    // เช็คสิทธิ์และประวัติก่อนโอน
    if (selectedVen.value.com_status != 1) {
      return Swal.fire({
        title: 'ดำเนินการไม่ได้',
        text: 'คำสั่งจัดเวรนี้ยังไม่ได้รับการอนุมัติอย่างเป็นทางการ จึงยังไม่สามารถทำการโอนหรือเปลี่ยนเวรได้',
        icon: 'error'
      });
    }
    if (selectedVen.value.history && selectedVen.value.history.length > 0) {
      const latestChange = selectedVen.value.history[0];
      if (latestChange.status != 1) {
        return Swal.fire({
          title: 'ไม่สามารถดำเนินการได้',
          text: `ใบเปลี่ยนเวรเลขที่ #${latestChange.change_no} ยังไม่ได้รับการอนุมัติ จึงไม่สามารถนำไปเปลี่ยนต่อได้`,
          icon: 'warning'
        });
      }
    }

    // 🌟 ดักจับกฎ: ตรวจสอบวันที่ล่วงหน้าและย้อนหลัง
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const shiftDate = new Date(selectedVen.value.ven_date);
    shiftDate.setHours(0, 0, 0, 0);

    const diffTime = shiftDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    // กฎที่ 1: ตรวจสอบการเปลี่ยนเวรย้อนหลัง
    if (!systemSettings.value.allow_retroactive_swap && diffDays < 0) {
      return Swal.fire('ไม่สามารถทำรายการได้', 'ระบบไม่อนุญาตให้ส่งคำขอเปลี่ยนเวรย้อนหลังครับ', 'warning');
    }

    // กฎที่ 2: ตรวจสอบจำนวนวันล่วงหน้าขั้นต่ำ
    if (diffDays >= 0 && diffDays < systemSettings.value.advance_swap_days) {
      return Swal.fire('ไม่สามารถทำรายการได้', `ระเบียบของหน่วยงานระบุให้ต้องทำรายการล่วงหน้าอย่างน้อย ${systemSettings.value.advance_swap_days} วันครับ`, 'warning');
    }
   

    const sub_id = selectedVen.value.sub_id || selectedVen.value.ven_name_sub_id; 
    if (!sub_id) return Swal.fire('ข้อมูลไม่ครบถ้วน', 'ไม่พบรหัสหน้าที่', 'warning');

    const res = await api.get(`?route=ven/eligible_users&action=get_by_sub&sub_id=${sub_id}`);
    recipients.value = res.data.filter(u => u.user_id != currentUserId.value);
    showRecipientList.value = true;

  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถดึงรายชื่อผู้มีสิทธิ์ได้', 'error');
  }
};


const check24HourViolation = (targetUserId, shiftDate, shiftTime) => {
  const targetUserShifts = allSchedules.value.filter(s => s.user_id == targetUserId);

  const targetDate = new Date(shiftDate);
  const yesterday = new Date(targetDate);
  yesterday.setDate(yesterday.getDate() - 1);
  const tomorrow = new Date(targetDate);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const dateStr = shiftDate;
  const yesterdayStr = yesterday.toISOString().split('T')[0];
  const tomorrowStr = tomorrow.toISOString().split('T')[0];

  if (shiftTime.includes("08:30")) {
    if (targetUserShifts.some(s => s.date === yesterdayStr && s.ven_time.includes("16:30"))) return true;
    if (targetUserShifts.some(s => s.date === dateStr && s.ven_time.includes("16:30"))) return true;
  } 
  else if (shiftTime.includes("16:30")) {
    if (targetUserShifts.some(s => s.date === dateStr && s.ven_time.includes("08:30"))) return true;
    if (targetUserShifts.some(s => s.date === tomorrowStr && s.ven_time.includes("08:30"))) return true;
  }

  return false;
};

// 🌟 ปรับปรุงการตรวจสอบ 24 ชม. ให้ใช้ systemSettings
const is24HourShift = (sch) => {
  if (!systemSettings.value.check_24h_consecutive || !allSchedules.value) return false;
  
  const userShifts = allSchedules.value.filter(s => s.user_id == sch.user_id);
  const targetDate = new Date(sch.date);
  
  const yesterday = new Date(targetDate);
  yesterday.setDate(yesterday.getDate() - 1);
  const tomorrow = new Date(targetDate);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const yesterdayStr = yesterday.toISOString().split('T')[0];
  const tomorrowStr = tomorrow.toISOString().split('T')[0];

  if (sch.ven_time.includes("08:30")) {
    return userShifts.some(s => (s.date === yesterdayStr && s.ven_time.includes("16:30")) || 
                               (s.date === sch.date && s.ven_time.includes("16:30")));
  } else {
    return userShifts.some(s => (s.date === sch.date && s.ven_time.includes("08:30")) || 
                               (s.date === tomorrowStr && s.ven_time.includes("08:30")));
  }
};

const confirmTransfer = async (targetUser) => {
  let isViolated = false;
  
  if (systemSettings.value.check_24h_consecutive) {
    isViolated = check24HourViolation(targetUser.user_id, selectedVen.value.ven_date, selectedVen.value.ven_time);
  }

  const result = await Swal.fire({
    title: isViolated ? '⚠️ ตรวจพบการเข้าเวร 24 ชม.' : 'ยืนยันการโอนเวร?',
    html: isViolated 
      ? `หากโอนให้ <b>${targetUser.full_name}</b> จะทำให้เข้าเวรติดต่อกัน 24 ชม. <br>ต้องการยืนยันหรือไม่?`
      : `คุณแน่ใจหรือไม่ที่จะยกเวรให้ <b>${targetUser.full_name}</b>?`,
    icon: isViolated ? 'error' : 'warning',
    showCancelButton: true,
    confirmButtonText: 'ยืนยันการโอน',
    cancelButtonText: 'ยกเลิก',
    confirmButtonColor: isViolated ? '#dc3545' : '#3085d6'
  });

  if (result.isConfirmed) {
    try {
      Swal.fire({ 
        title: 'กำลังส่งคำขอ...', 
        allowOutsideClick: false, 
        didOpen: () => Swal.showLoading() 
      });

      await api.post('?route=user/transfer&action=perform', {
        schedule_id: selectedVen.value.ven_id, 
        new_user_id: targetUser.user_id
      });
      
      detailModalInstance.hide();
      fetchVenData(); 
      
      // 🌟 ดักจับผลลัพธ์ (await) จากหน้าต่างแจ้งเตือนโอนสำเร็จ
      const successResult = await Swal.fire({
        title: 'ส่งคำขอสำเร็จ!',
        html: `
          <div class="mb-2">ทำการโอนเวรเรียบร้อยแล้ว</div>
          สถานะปัจจุบัน: <span class="badge bg-warning text-dark fs-6"><i class="bi bi-printer"></i> รอพิมพ์เอกสาร</span>
          <hr>
          <div class="small text-muted text-start">
            <b>คำแนะนำ:</b> โปรดไปที่หน้าประวัติเพื่อพิมพ์ใบเปลี่ยนเวร และนำไปเสนอผู้บังคับบัญชาลงนาม
          </div>
        `,
        icon: 'success',
        showCancelButton: true, // เพิ่มปุ่มปิดเผื่อผู้ใช้ยังไม่อยากไปตอนนี้
        confirmButtonText: '<i class="bi bi-box-arrow-up-right"></i> ไปหน้าประวัติ',
        cancelButtonText: 'ปิดหน้านี้',
        confirmButtonColor: '#198754'
      });

      // 🌟 ถ้ายืนยัน ให้เด้งไปที่หน้า /user/history ทันที
      if (successResult.isConfirmed) {
        router.push('/user/history'); // *หมายเหตุ: ตรวจสอบให้แน่ใจว่า path นี้ตรงกับที่คุณตั้งใน router/index.js
      }

    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถโอนเวรได้', 'error');
    }
  }
};

const cancelChange = async (changeId) => {
  const result = await Swal.fire({
    title: 'ยืนยันการยกเลิก?',
    text: "หากยกเลิก เวรนี้จะถูกส่งคืนให้กับผู้รับผิดชอบเดิมทันที",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'ยืนยันยกเลิก',
    cancelButtonText: 'ปิด'
  });

  if (result.isConfirmed) {
    try {
      const res = await api.post('?route=ven/cancel_change', { change_id: changeId });
      if (res.data.success) {
        Swal.fire('ยกเลิกสำเร็จ', 'ใบเปลี่ยนเวรถูกยกเลิกและคืนเวรเรียบร้อยแล้ว', 'success');
        detailModalInstance.hide();
        fetchVenData();
      }
    } catch (error) {
      Swal.fire('ผิดพลาด', error.response?.data?.error || 'ไม่สามารถยกเลิกได้', 'error');
    }
  }
};


// 🌟 ตัวแปรเก็บ ID เวรของตัวเองที่จะเอาไปเสนอแลก
const selectedMyShiftId = ref(''); 

// ดึงการตั้งค่าระบบว่าเปิดให้สลับเวรหรือไม่
const allowSwap = computed(() => systemSettings.value.allow_swap ? 1 : 0);

// 🌟 ตัวกรองดึง "เวรของฉัน" ที่มีคำสั่งและหน้าที่เดียวกับเวรที่กดเลือกเพื่อนำมาสลับ
const mySwappableShifts = computed(() => {
  // ถ้าไม่ได้เลือกเวร หรือกดโดนเวรตัวเอง ให้ส่งค่าว่างกลับไป (สลับกับตัวเองไม่ได้)
  if (!selectedVen.value || selectedVen.value.user_id === currentUserId.value) return [];
  
  // ดึงวันที่ของเวรที่ถูกคลิกมาเก็บไว้ก่อน (รองรับทั้งฟิลด์ date และ ven_date)
  const targetDate = selectedVen.value.date || selectedVen.value.ven_date;
  
  return allSchedules.value.filter(shift => {
    const myShiftDate = shift.date || shift.ven_date;
    
    return shift.user_id === currentUserId.value && // ต้องเป็นเวรของฉันเอง
           myShiftDate !== targetDate && // 🌟 กรองเวรในวันเดียวกันออก (ไม่ให้สลับวันเดียวกัน)
           shift.ven_com_id === selectedVen.value.ven_com_id && // ต้องอยู่คำสั่งเดียวกัน
           (shift.ven_name_sub_id === selectedVen.value.ven_name_sub_id || shift.sub_id === selectedVen.value.sub_id) && // ต้องเป็นหน้าที่เดียวกัน
           new Date(myShiftDate) >= new Date(); // ต้องเป็นเวรในอนาคต (หรือวันนี้)
  });
});

// ==========================================
// 🌟 ฟังก์ชันขอสลับเวร (แลกเวร)
// ==========================================
const confirmSwap = async () => {
  // 🌟 ดักจับกฎ: ตรวจสอบวันที่ล่วงหน้าและย้อนหลัง
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const shiftDate = new Date(selectedVen.value.ven_date);
    shiftDate.setHours(0, 0, 0, 0);

    const diffTime = shiftDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    // กฎที่ 1: ตรวจสอบการเปลี่ยนเวรย้อนหลัง
    if (!systemSettings.value.allow_retroactive_swap && diffDays < 0) {
      return Swal.fire('ไม่สามารถทำรายการได้', 'ระบบไม่อนุญาตให้ส่งคำขอเปลี่ยนเวรย้อนหลังครับ', 'warning');
    }

    // กฎที่ 2: ตรวจสอบจำนวนวันล่วงหน้าขั้นต่ำ
    if (diffDays >= 0 && diffDays < systemSettings.value.advance_swap_days) {
      return Swal.fire('ไม่สามารถทำรายการได้', `ระเบียบของหน่วยงานระบุให้ต้องทำรายการล่วงหน้าอย่างน้อย ${systemSettings.value.advance_swap_days} วันครับ`, 'warning');
    }
    
  if (!selectedMyShiftId.value) {
    return Swal.fire('แจ้งเตือน', 'กรุณาเลือกเวรของคุณที่ต้องการนำไปสลับ', 'warning');
  }

  // theirShift = เวรของเขา (เวรที่เรากดดูบนปฏิทิน)
  // myShift = เวรของเรา (เวรที่เราเลือกจาก Dropdown จะเอาไปแลก)
  const theirShift = selectedVen.value;
  const myShift = mySwappableShifts.value.find(s => s.id === selectedMyShiftId.value || s.ven_id === selectedMyShiftId.value);

  // ตรวจสอบกฎ 24 ชั่วโมงทั้ง 2 ฝ่าย
  let isMeViolated = false;
  let isThemViolated = false;

  if (systemSettings.value.check_24h_consecutive) {
    isMeViolated = check24HourViolation(currentUserId.value, theirShift.ven_date || theirShift.date, theirShift.ven_time);
    isThemViolated = check24HourViolation(theirShift.user_id, myShift.ven_date || myShift.date, myShift.ven_time);
  }

  // ถ้าเข้าข่าย 24 ชม. ฝ่ายใดฝ่ายหนึ่ง ให้เด้งเตือนสีแดง
  if (isMeViolated || isThemViolated) {
    let msg = "การสลับเวรครั้งนี้จะทำให้มีผู้ปฏิบัติงานติดต่อกัน 24 ชั่วโมง<br><br>";
    if (isMeViolated) msg += "- <b>คุณ</b> จะมีเวร 24 ชม.<br>";
    if (isThemViolated) msg += `- <b>${theirShift.user1_name || 'เจ้าของเวรเดิม'}</b> จะมีเวร 24 ชม.<br>`;
    
    const confirm24h = await Swal.fire({
      title: '⚠️ แจ้งเตือนการปฏิบัติงาน 24 ชั่วโมง!',
      html: msg,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'รับทราบ ยืนยันสลับเวร',
      cancelButtonText: 'ยกเลิก'
    });
    if (!confirm24h.isConfirmed) return;
  } else {
    // ถ้าไม่ติด 24 ชม. ให้ขึ้นถามยืนยันปกติ
    const confirmNormal = await Swal.fire({
      title: 'ยืนยันการขอสลับเวร?',
      text: 'ระบบจะส่งคำขอเพื่อรอการอนุมัติการสลับเวร',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#ffc107',
      confirmButtonText: 'ใช่, ขอสลับเวร'
    });
    if (!confirmNormal.isConfirmed) return;
  }

  try {
    Swal.fire({ title: 'กำลังส่งคำขอ...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    // 🌟 เตรียม payload ส่งให้ Backend พร้อม Flag: is_swap = 1
    await api.post('?route=user/transfer&action=perform', {
      schedule_id: myShift.id || myShift.ven_id, // รหัสเวรของเรา (หลัก)
      new_user_id: theirShift.user_id,           // รหัสเจ้าของเวรที่เราไปขอแลกด้วย
      is_swap: 1,                                // บอก Backend ว่านี่คือการสลับเวร
      s2_id: theirShift.id || theirShift.ven_id  // รหัสเวรของเขาที่จะเอามาแทน
    });
    
    detailModalInstance.hide();
    fetchVenData(); 
    
    const successResult = await Swal.fire({
      title: 'ส่งคำขอสำเร็จ!',
      html: `<div class="mb-2">ทำการส่งคำขอสลับเวรเรียบร้อยแล้ว</div> รอการอนุมัติตามขั้นตอน`,
      icon: 'success',
      showCancelButton: true,
      confirmButtonText: 'ไปหน้าประวัติ',
      cancelButtonText: 'ปิดหน้านี้',
      confirmButtonColor: '#198754'
    });

    if (successResult.isConfirmed) {
      router.push('/user/history'); 
    }

  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถส่งคำขอสลับเวรได้', 'error');
  }
};

const downloadWordForm = async (historyItem) => {
  try {
    Swal.fire({
      title: 'กำลังสร้างเอกสาร...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const director = getActiveSigner('directors');
    const venInfo = {
        ...selectedVen.value,
        agency_name: agencyConfig.value.agency_name, // 🌟 ส่งชื่อหน่วยงาน
        director_name: director.name,               // 🌟 ส่งชื่อผู้ลงนามอนุมัติ
        director_position: director.position,   
        order_no: selectedVen.value.order_no,
        order_date: selectedVen.value.order_date,
        ven_name: selectedVen.value.ven_name,
        duty_main: selectedVen.value.duty_main,
        duty_main_full: selectedVen.value.duty_main_full,
        command_num: selectedVen.value.command_num,
        command_date: selectedVen.value.command_date,
        command_month: selectedVen.value.command_month,
        duty_role: selectedVen.value.duty_role,
    };

    await exportShiftChangeToWord(historyItem, venInfo);

    Swal.fire({
      icon: 'success',
      title: 'ดาวน์โหลดสำเร็จ',
      text: 'เอกสาร Word ถูกดาวน์โหลดเรียบร้อยแล้ว',
      timer: 2000,
      showConfirmButton: false
    });
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถสร้างเอกสาร Word ได้ กรุณาตรวจสอบไฟล์ Template', 'error');
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return date.toLocaleDateString('th-TH', options);
}

const getProfileImage = (ven) => {
  if (ven.profile_image && ven.profile_image !== 'default_avatar.jpg') {
    return `http://localhost/vengg3/backend/public/uploads/profiles/${ven.profile_image}`;
  }
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(ven.full_name)}&background=random&color=fff&size=128`;
}

const handleLogout = () => {
  localStorage.clear()
  router.push('/login')
}

const isPastShift = (dateStr, timeStr) => {
  if (!dateStr) return false;
  const shiftDateTime = new Date(`${dateStr}T${timeStr || '00:00:00'}`);
  const now = new Date();
  return shiftDateTime < now;
};


const agencyConfig = ref({
  agency_name: '',
  directors: [],
  admins: [],
  finances: []
})

const getActiveSigner = (type) => {
  if (!agencyConfig.value[type] || agencyConfig.value[type].length === 0) {
    return { name: '.......................................', position: '..............................' }
  }
  return agencyConfig.value[type].find(s => s.is_active) || agencyConfig.value[type][0]
}

const fetchAgencyConfig = async () => {
  try {
    const res = await api.get('?route=admin/agency_settings')
    if (res.data) agencyConfig.value = res.data
  } catch (error) {
    console.error("Error fetching agency config:", error)
  }
}

onMounted(async() => {
  fetchVenData()
  await fetchAgencyConfig()
  await fetchUserInfo()
  detailModalInstance = new Modal(document.getElementById('venDetailModal'))
})
</script>

<style scoped>
/* 🌟 สไตล์สำหรับ Custom Calendar Grid และ Component ภายในหน้านี้ */
.calendar-grid { 
  display: grid; 
  grid-template-columns: repeat(7, 1fr); 
  grid-auto-rows: minmax(130px, 1fr); 
  gap: 4px; 
}
.day-cell { transition: 0.2s; min-width: 0; }
.day-cell.blank { border: none !important; background: none !important; box-shadow: none !important; }
.transition-hover:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
.schedule-item { transition: 0.15s; }
.schedule-item:hover { filter: brightness(0.9); transform: scale(0.98); }

/* Scrollbar สำหรับกล่องต่างๆ */
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #0d6efd; }
.timeline-container {
    padding-bottom: 1px;
}
.timeline-item:last-child {
    margin-bottom: 0 !important;
}
.timeline-item .card {
    transition: transform 0.2s;
}
.timeline-item .card:hover {
    transform: translateX(5px);
    border-left: 3px solid #0d6efd !important;
}
/* เอฟเฟกต์กระพริบสีแดง */
@keyframes flash-danger {
  0% { opacity: 1; background-color: #dc3545; }
  50% { opacity: 0.7; background-color: #ffc107; }
  100% { opacity: 1; background-color: #dc3545; }
}

.flashing-24h {
  animation: flash-danger 1s infinite;
  border: 2px solid white !important;
  color: white !important;
  box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
}
</style>

<style>
/* 🌟 ป้องกัน layout shift เมื่อเปิด Modal */
html {
  overflow-y: scroll; /* ให้ scrollbar แนวตั้งแสดงเสมอ */
}

body {
  overflow-y: scroll !important;
  padding-right: 0 !important; /* ป้องกัน Bootstrap เพิ่ม padding ขวา */
}

body.modal-open {
  overflow-y: scroll !important;
  padding-right: 0 !important;
}

.modal {
  padding-right: 0 !important;
}

/* ถ้ามี element ไหนที่ใช้ width: 100vw ให้เปลี่ยนเป็น 100% */
.container-fluid,
.min-vh-100 {
  width: 100% !important;
}
</style>