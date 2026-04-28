<template>
  <div class="bg-light min-vh-100 d-flex flex-column pb-4">
    <div class="container-fluid py-4 px-4 flex-grow-1 d-flex flex-column">
      <div class="card shadow-sm border-0 rounded-4 flex-grow-1 d-flex flex-column overflow-hidden">        
        <div class="card-header bg-white border-bottom-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
          <h4 class="fw-bold mb-0 text-primary">
            <i class="bi bi-calendar3 me-2"></i>ตารางเวรเดือน {{ formatMonthThai(currentMonth) }}
          </h4>
          
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
                    :class="{ 'flashing-24h': is24HourShift(sch) }"
                    :style="{ 
                      backgroundColor: is24HourShift(sch) ? '' : (sch.user_id == currentUserId ? '#FFD700' : sch.backgroundColor), 
                      color: is24HourShift(sch) ? 'white' : (sch.user_id == currentUserId ? '#000' : '#fff')
                    }"
                    @click="openShiftDetail(sch.id)">
                  
                  <div class="fw-bold" style="font-size: 0.7rem;">
                    <i class="bi bi-clock me-1"></i>{{ sch.ven_time.substring(0,5) }}
                    <i v-if="is24HourShift(sch)" class="bi bi-exclamation-triangle-fill ms-1"></i>
                    <i v-else-if="sch.user_id == currentUserId" class="bi bi-star-fill ms-1"></i>
                  </div>
                  <div class="text-truncate" style="font-size: 0.75rem;">{{ sch.title }}</div>
                </div>

              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="modal fade" id="venDetailModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4" v-if="selectedVen">
          
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title text-muted fs-6" style="font-family: monospace;">ID: {{ selectedVen.ven_id }}</h5>
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
                <strong>{{ formatDate(selectedVen.ven_date) }}</strong> 
                <span v-if="selectedVen.ven_time">(เวลา {{ selectedVen.ven_time }} น.)</span>
              </p>
              <p class="mb-2"><i class="bi bi-moon-stars me-2 text-warning"></i> {{ selectedVen.duty_main }}</p>
              
              <p v-if="selectedVen.order_no" class="mb-2 text-muted small">
                <i class="bi bi-file-earmark-text me-2"></i> คำสั่งเลขที่ {{ selectedVen.order_no }} 
                <span v-if="selectedVen.order_date">ลงวันที่ {{ formatDate(selectedVen.order_date) }}</span>
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
                
                <template v-if="!isPastShift(selectedVen.ven_date, selectedVen.ven_time) || appSettings?.allow_retro_transfer">
                  
                  <button v-if="!showRecipientList" 
                          class="btn btn-warning rounded-pill fw-bold text-dark shadow-sm py-2 w-100" 
                          @click="loadRecipients">
                    <i class="bi bi-arrow-left-right me-1"></i> ยกเวรนี้ให้ผู้อื่น
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
            
            <div v-else class="alert alert-secondary border-0 small text-center mt-3 mb-4">
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
                      {{ history.user1_name }} <i class="bi bi-arrow-right mx-1"></i> {{ history.user2_name }}
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

          </div></div>
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

const currentUserId = ref(0) // เริ่มต้นด้วย 0 หรือ null
const currentUsername = ref('')
const userRole = ref(0)

const fetchUserInfo = async () => {
  try {
    // ยิง API ไปที่ auth/me โดย Header Authorization จะถูกจัดการโดย api service อยู่แล้ว
    const response = await api.get('?route=auth/me')
    
    if (response.data.success) {
      currentUserId.value = response.data.user_id
      currentUsername.value = response.data.username
      userRole.value = response.data.role
      
      // อัปเดต localStorage เพื่อความลื่นไหลในหน้าอื่นๆ (ไม่บังคับ)
      localStorage.setItem('user_id', response.data.user_id)
      localStorage.setItem('role', response.data.role)
    }
  } catch (error) {
    console.error("ยืนยันตัวตนไม่สำเร็จ:", error)
    handleLogout() // ถ้า Token ปลอมหรือหมดอายุ ให้เด้งออกทันที
  }
}

// เมนู
const isAdminMenuOpen = ref(false)
const toggleAdminMenu = () => { 
  isAdminMenuOpen.value = !isAdminMenuOpen.value 
  if (isAdminMenuOpen.value) isDirectorMenuOpen.value = false // ปิดเมนูอำนวยการถ้าเปิดเมนูแอดมิน
  }
const isDirectorMenuOpen = ref(false)
const toggleDirectorMenu = () => { 
  isDirectorMenuOpen.value = !isDirectorMenuOpen.value 
  if (isDirectorMenuOpen.value) isAdminMenuOpen.value = false // ปิดเมนูแอดมินถ้าเปิดเมนูอำนวยการ
}

// --- 🌟 จัดการเรื่องปฏิทิน Custom ---
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
  return (dayOfWeek + 6) % 7 // ปรับให้เริ่มวันจันทร์
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

// --- 🌟 ดึงข้อมูลเวรจาก Backend ---
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

// --- 🌟 การคลิกดูรายละเอียด และ โอนเวร ---
const selectedVen = ref(null)
let detailModalInstance = null 
const recipients = ref([])
const showRecipientList = ref(false)

const openShiftDetail = async (venId) => {
  try {
    // 1. แสดง Loading แจ้งเตือนผู้ใช้
    Swal.fire({ 
      title: 'กำลังโหลดข้อมูล...', 
      allowOutsideClick: false, 
      didOpen: () => Swal.showLoading() 
    });
    
    // 2. ดึงรายละเอียดเวร (ไม่ต้องใส่ headers เพราะ api.js จัดการให้แล้ว)
    const response = await api.get(`?route=ven/detail&id=${venId}`);

    // 3. บันทึกข้อมูลลง selectedVen และรีเซ็ตสถานะการแสดงรายชื่อเพื่อน
    selectedVen.value = response.data;
    showRecipientList.value = false; 
    
    // 4. ปิด Loading และเปิด Modal
    Swal.close();
    detailModalInstance.show();

    // ตรวจสอบใน Console เพื่อความมั่นใจ (ลบออกได้เมื่อใช้งานจริง)
    console.log("ID เวรนี้:", selectedVen.value.user_id);
    console.log("ID ผู้ใช้ปัจจุบัน:", currentUserId.value);

  } catch (error) {
    console.error("Error fetching detail:", error);
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงรายละเอียดเวรได้ หรือเซสชันหมดอายุ', 'error');
  }
}

// ฟังก์ชันดึงรายชื่อผู้มีสิทธิ์รับโอนเวร
// ในหน้า HomeView.vue ส่วน <script setup>
const loadRecipients = async () => {
  try {
    // 🌟 1. ตรวจสอบว่าคำสั่งเวรต้นทางอนุมัติหรือยัง
    if (selectedVen.value.com_status != 1) {
      return Swal.fire({
        title: 'ดำเนินการไม่ได้',
        text: 'คำสั่งจัดเวรนี้ยังไม่ได้รับการอนุมัติอย่างเป็นทางการ จึงยังไม่สามารถทำการโอนหรือเปลี่ยนเวรได้',
        icon: 'error'
      });
    }
    // 🌟 ตรวจสอบสถานะใบเปลี่ยนล่าสุดก่อน
    if (selectedVen.value.history && selectedVen.value.history.length > 0) {
      const latestChange = selectedVen.value.history[0]; // เนื่องจากเรียง DESC ตัวแรกคือล่าสุด
      
      // สมมติว่าสถานะ 1 คืออนุมัติแล้ว, ถ้าไม่ใช่ 1 ให้แจ้งเตือน
      if (latestChange.status != 1) {
        return Swal.fire({
          title: 'ไม่สามารถดำเนินการได้',
          text: `ใบเปลี่ยนเวรเลขที่ #${latestChange.change_id} ยังไม่ได้รับการอนุมัติ จึงไม่สามารถนำไปเปลี่ยนต่อได้`,
          icon: 'warning'
        });
      }
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

// const confirmTransfer = async (targetUser) => {
//   // 🌟 เพิ่มการตรวจสอบ 24 ชั่วโมงตรงนี้
//   const isViolated = check24HourViolation(
//     targetUser.user_id, 
//     selectedVen.value.ven_date, 
//     selectedVen.value.ven_time
//   );

//   if (isViolated) {
//     return Swal.fire({
//       title: 'ไม่สามารถโอนเวรได้',
//       text: `คุณ ${targetUser.name} มีภาระงานในเวลาใกล้เคียงกัน ซึ่งจะทำให้เป็นการปฏิบัติหน้าที่ติดต่อกันเกิน 24 ชั่วโมง`,
//       icon: 'error'
//     });
//   }

//   const result = await Swal.fire({
//     title: 'ยืนยันการโอนเวร?',
//     html: `คุณแน่ใจหรือไม่ที่จะยกเวรให้ <b>${targetUser.full_name}</b> ?<br><small class="text-danger">เมื่อยืนยันแล้ว ชื่อในตารางจะถูกเปลี่ยนทันที</small>`,
//     icon: 'warning',
//     showCancelButton: true,
//     confirmButtonText: 'ยืนยันการโอน',
//     cancelButtonText: 'ยกเลิก'
//   });

//   if (result.isConfirmed) {
//     try {
//       await api.post('?route=user/transfer&action=perform', {
//         schedule_id: selectedVen.value.ven_id, 
//         new_user_id: targetUser.user_id
//       });
      
//       detailModalInstance.hide();
//       fetchVenData(); 
//       Swal.fire('สำเร็จ', 'ทำการโอนเวรเรียบร้อยแล้ว', 'success');
//     } catch (error) {
//       Swal.fire('ผิดพลาด', 'ไม่สามารถโอนเวรได้', 'error');
//     }
//   }
// };

// --- Helpers ---
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

// 🌟 เพิ่มฟังก์ชันตรวจสอบว่าเลยเวลาเวรไปหรือยัง
const isPastShift = (dateStr, timeStr) => {
  if (!dateStr) return false;
  // ประกอบวันที่และเวลาเข้าด้วยกัน (ถ้าไม่มีเวลาให้ยึดเที่ยงคืน)
  const shiftDateTime = new Date(`${dateStr}T${timeStr || '00:00:00'}`);
  const now = new Date();
  
  // คืนค่า true ถ้าเวลาเวรน้อยกว่าเวลาปัจจุบัน (ผ่านมาแล้ว)
  return shiftDateTime < now;
};

// ฟังก์ชันตรวจสอบการเข้าเวรติดต่อกัน 24 ชั่วโมง
const check24HourViolation = (targetUserId, shiftDate, shiftTime) => {
  // กรองหาเวรทั้งหมดที่เพื่อนคนนี้มีอยู่ในมือปัจจุบัน
  const targetUserShifts = allSchedules.value.filter(s => s.user_id == targetUserId);

  const targetDate = new Date(shiftDate);
  const yesterday = new Date(targetDate);
  yesterday.setDate(yesterday.getDate() - 1);
  const tomorrow = new Date(targetDate);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const dateStr = shiftDate;
  const yesterdayStr = yesterday.toISOString().split('T')[0];
  const tomorrowStr = tomorrow.toISOString().split('T')[0];

  // กรณีเวรที่กำลังจะโอนคือ เวรกลางวัน (08:30)
  if (shiftTime.includes("08:30")) {
    // 1. เช็คว่าเมื่อวานเพื่อนมีเวรกลางคืนไหม (ดึกเมื่อวาน + เช้าวันนี้ = 24 ชม.)
    if (targetUserShifts.some(s => s.date === yesterdayStr && s.ven_time.includes("16:30"))) return true;
    // 2. เช็คว่าวันนี้เพื่อนมีเวรกลางคืนอยู่แล้วไหม (เช้าวันนี้ + ดึกวันนี้ = 24 ชม.)
    if (targetUserShifts.some(s => s.date === dateStr && s.ven_time.includes("16:30"))) return true;
  } 
  
  // กรณีเวรที่กำลังจะโอนคือ เวรกลางคืน (16:30)
  else if (shiftTime.includes("16:30")) {
    // 1. เช็คว่าวันนี้เพื่อนมีเวรกลางวันอยู่แล้วไหม (เช้าวันนี้ + ดึกวันนี้ = 24 ชม.)
    if (targetUserShifts.some(s => s.date === dateStr && s.ven_time.includes("08:30"))) return true;
    // 2. เช็คว่าพรุ่งนี้เพื่อนมีเวรกลางวันไหม (ดึกวันนี้ + เช้าพรุ่งนี้ = 24 ชม.)
    if (targetUserShifts.some(s => s.date === tomorrowStr && s.ven_time.includes("08:30"))) return true;
  }

  return false;
};

// 🌟 ฟังก์ชันเช็คว่าเวรนี้เป็นส่วนหนึ่งของการควง 24 ชั่วโมงหรือไม่ (สำหรับแสดงผลในปฏิทิน)
// เพิ่มตัวแปรเก็บการตั้งค่าระบบ
const appSettings = ref({
  allow_retro_transfer: false,
  enable_24h_check: true
});

// ฟังก์ชันดึงค่าการตั้งค่าจาก API
const fetchAppSettings = async () => {
  try {
    const res = await api.get('?route=settings/app');
    appSettings.value.allow_retro_transfer = res.data.allow_retro_transfer === '1';
    appSettings.value.enable_24h_check = res.data.enable_24h_check === '1';
  } catch (error) {
    console.error("โหลดการตั้งค่าไม่สำเร็จ");
  }
};

// ตรวจสอบว่าเวรนี้เป็นส่วนหนึ่งของการควง 24 ชม. หรือไม่ (ใช้แสดงผลในปฏิทิน)
const is24HourShift = (sch) => {
  if (!appSettings.value.enable_24h_check || !allSchedules.value) return false;
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

// 🌟 ปรับปรุงการยืนยันการโอนเวร
const confirmTransfer = async (targetUser) => {
  // ตรวจสอบเงื่อนไข 24 ชั่วโมง
  let isViolated = false;
  if (appSettings.value.enable_24h_check) {
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
    confirmButtonColor: isViolated ? '#dc3545' : '#3085d6'
  });

  if (result.isConfirmed) {
    try {
      await api.post('?route=user/transfer&action=perform', {
        schedule_id: selectedVen.value.ven_id, 
        new_user_id: targetUser.user_id
      });
      detailModalInstance.hide();
      fetchVenData(); 
      Swal.fire('สำเร็จ', 'ทำการโอนเวรเรียบร้อยแล้ว', 'success');
    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถโอนเวรได้', 'error');
    }
  }
};

// 🌟 ฟังก์ชันสำหรับยกเลิกใบเปลี่ยนเวร
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
        fetchVenData(); // รีเฟรชปฏิทิน
      }
    } catch (error) {
      Swal.fire('ผิดพลาด', error.response?.data?.error || 'ไม่สามารถยกเลิกได้', 'error');
    }
  }
};

const downloadWordForm = async (historyItem) => {
  try {
    Swal.fire({
      title: 'กำลังสร้างเอกสาร...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const venInfo = {
        ...selectedVen.value,
        order_no: selectedVen.value.order_no, // มั่นใจว่ามีค่านี้
        order_date: selectedVen.value.order_date,
        ven_name: selectedVen.value.ven_name, // ตัวแปรที่เก็บ "เวรปฏิบัติหน้าที่พยาบาล..."
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

onMounted(async() => {
  await fetchUserInfo()
  await fetchAppSettings();
  fetchVenData()
  detailModalInstance = new Modal(document.getElementById('venDetailModal'))
})
</script>

<style scoped>
/* 🌟 สไตล์สำหรับ Custom Calendar Grid */
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
  50% { opacity: 0.7; background-color: #ffc107; } /* สลับกับสีส้มเพื่อให้สะดุดตา */
  100% { opacity: 1; background-color: #dc3545; }
}

.flashing-24h {
  animation: flash-danger 1s infinite;
  border: 2px solid white !important;
  color: white !important;
  box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
}
</style>