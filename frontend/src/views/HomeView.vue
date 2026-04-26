<template>
  <div class="bg-light min-vh-100 d-flex flex-column pb-4">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4" href="#"><i class="bi bi-calendar-check me-2"></i>Vengg3</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 fs-5">
            <li class="nav-item"><router-link class="nav-link active" to="/home">หน้าแรก</router-link></li>
            <li class="nav-item"><a class="nav-link" href="#">ประวัติการเปลี่ยนเวร</a></li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">รายงาน</a>
              <ul class="dropdown-menu shadow border-0">
                <li><a class="dropdown-item" href="#">สรุปการอยู่เวร</a></li>
                <li><a class="dropdown-item" href="#">พิมพ์คำสั่ง</a></li>
              </ul>
            </li>
            
            <li class="nav-item dropdown" v-if="userRole === 9 || userRole === 2">
              <a class="nav-link dropdown-toggle fw-bold text-info" 
                 @click.prevent="toggleDirectorMenu" 
                 style="cursor: pointer;">
                <i class="bi bi-briefcase-fill me-1"></i>งานอำนวยการ
              </a>
              <ul class="dropdown-menu shadow border-0 mt-2 rounded-3" :class="{ 'show': isDirectorMenuOpen }">
                <li>
                  <router-link class="dropdown-item py-2" to="/director/commands">
                    <i class="bi bi-file-earmark-plus me-2 text-primary"></i>เพิ่มคำสั่ง (จัดเวร)
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item py-2" to="/director/ven-settings">
                    <i class="bi bi-card-checklist me-2 text-success"></i>จัดการชื่อเวร/กลุ่มหน้าที่
                  </router-link>
                </li>
              </ul>
            </li>
            
            <li class="nav-item dropdown" v-if="userRole === 9">
                <a class="nav-link dropdown-toggle text-warning fw-bold" 
                   @click.prevent="toggleAdminMenu" 
                   style="cursor: pointer;">
                    <i class="bi bi-shield-lock-fill me-1"></i>ผู้ดูแลระบบ
                </a>
                <ul class="dropdown-menu shadow border-0 mt-2 rounded-3" :class="{ 'show': isAdminMenuOpen }">
                    <li>
                    <router-link class="dropdown-item py-2" to="/admin/users">
                        <i class="bi bi-people-fill me-2 text-primary"></i>จัดการสมาชิก (Users)
                    </router-link>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                    <router-link class="dropdown-item py-2" to="/admin/settings">
                        <i class="bi bi-gear-fill me-2 text-secondary"></i>ตั้งค่าระบบ (Master Data)
                    </router-link>
                    </li>
                </ul>
            </li>
          </ul>
          
          <div class="d-flex align-items-center">
            <span class="text-light me-3 fw-semibold"><i class="bi bi-person-circle me-1"></i> {{ currentUsername }}</span>
            <button class="btn btn-light btn-sm rounded-pill fw-bold text-danger px-3 shadow-sm" @click="handleLogout">ออกจากระบบ</button>
          </div>
        </div>
      </div>
    </nav>

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
                     class="schedule-item mb-1 p-1 rounded-1 shadow-sm d-flex flex-column"
                     style="cursor: pointer; border-left: 4px solid rgba(0,0,0,0.2);"
                     :style="{ backgroundColor: sch.backgroundColor, color: '#fff' }"
                     @click="openShiftDetail(sch.id)">
                  
                  <div class="fw-bold d-flex justify-content-between align-items-center" style="font-size: 0.7rem;">
                    <span><i class="bi bi-clock me-1"></i>{{ sch.ven_time ? sch.ven_time.substring(0,5) : '' }}</span>
                  </div>
                  <div class="text-truncate fw-semibold" style="font-size: 0.75rem;">{{ sch.title }}</div>
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
            <img :src="getProfileImage(selectedVen)" 
                 alt="Profile" 
                 class="rounded-circle shadow-sm mb-3 object-fit-cover border border-3 border-white" 
                 style="width: 100px; height: 100px; background-color: #f8f9fa;">
            
            <h4 class="fw-bold mb-1" :style="{ color: selectedVen.color }">{{ selectedVen.full_name }}</h4>
            <span class="badge mb-3 px-3 py-2 rounded-pill shadow-sm" :style="{ backgroundColor: selectedVen.color }">
              {{ selectedVen.duty_role }}
            </span>

            <div class="bg-light border rounded-3 p-3 text-start mb-4 shadow-sm">
              <p class="mb-2"><i class="bi bi-calendar-event me-2 text-primary"></i> 
                <strong>{{ formatDate(selectedVen.ven_date) }}</strong> <span v-if="selectedVen.ven_time">(เวลา {{ selectedVen.ven_time.substring(0,5) }} น.)</span>
              </p>
              <p class="mb-2"><i class="bi bi-moon-stars me-2 text-warning"></i> {{ selectedVen.duty_main }}</p>
              <p class="mb-2"><i class="bi bi-file-earmark-text me-2 text-secondary"></i> คำสั่งที่ {{ selectedVen.command_num }}</p>
              <p class="mb-0 text-success fw-bold fs-5"><i class="bi bi-cash-coin me-2"></i> {{ Number(selectedVen.price).toLocaleString() }} บาท</p>
            </div>

            <div v-if="selectedVen.user_id == currentUserId">
              <button class="btn btn-primary rounded-pill fw-bold shadow-sm py-2 w-100 mb-2">
                <i class="bi bi-file-earmark-check me-1"></i> รายงานการปฏิบัติหน้าที่
              </button>
              
              <button v-if="!showRecipientList" class="btn btn-warning rounded-pill fw-bold text-dark shadow-sm py-2 w-100" @click="loadRecipients">
                <i class="bi bi-arrow-left-right me-1"></i> ยกเวรนี้ให้ผู้อื่น
              </button>

              <div v-if="showRecipientList" class="mt-3 text-start border border-warning rounded-3 p-2 bg-white shadow-sm">
                <label class="form-label fw-bold small text-primary mb-2 px-1">เลือกผู้ที่ต้องการยกเวรให้:</label>
                <div class="list-group list-group-flush border rounded-3 overflow-auto custom-scrollbar" style="max-height: 180px;">
                  <button v-for="user in recipients" :key="user.user_id" 
                          class="list-group-item list-group-item-action small py-2 d-flex align-items-center"
                          @click="confirmTransfer(user)">
                    <i class="bi bi-person-plus-fill me-2 text-success fs-5"></i>
                    <span class="fw-semibold">{{ user.prefix }}{{ user.name }} {{ user.sname }}</span>
                  </button>
                  <div v-if="recipients.length === 0" class="p-3 text-center text-muted small">ไม่พบผู้มีสิทธิ์ท่านอื่นในหน้าที่นี้</div>
                </div>
                <div class="text-center mt-2">
                  <button class="btn btn-link btn-sm text-decoration-none text-muted" @click="showRecipientList = false">ยกเลิก</button>
                </div>
              </div>
            </div>
            
            <div v-else class="alert alert-secondary border-0 small text-center mt-3 mb-0">
              <i class="bi bi-info-circle me-1"></i> การเปลี่ยนเวรหรือยกเวร สามารถทำได้เฉพาะเวรของตนเองเท่านั้น
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'
import api from '../services/api'

const router = useRouter()
const currentUsername = ref(localStorage.getItem('username') || 'ผู้ใช้งาน')
const userRole = parseInt(localStorage.getItem('role') || 1)
const currentUserId = ref(parseInt(localStorage.getItem('user_id')) || 0)
const isLoading = ref(true)

// เมนู
const isAdminMenuOpen = ref(false)
const toggleAdminMenu = () => { isAdminMenuOpen.value = !isAdminMenuOpen.value }
const isDirectorMenuOpen = ref(false)
const toggleDirectorMenu = () => { isDirectorMenuOpen.value = !isDirectorMenuOpen.value }

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
  // 🌟 ป้องกัน Error: ถ้า allSchedules ไม่ใช่ Array ให้คืนค่า [] กลับไปเลย
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
    
    // 🌟 บังคับตรวจสอบ: ถ้าข้อมูลเป็น Array ให้ใส่ค่าไป ถ้าไม่ใช่ให้เป็น Array ว่าง []
    allSchedules.value = Array.isArray(response.data) ? response.data : []
    
  } catch (error) {
    console.error("Error fetching calendar:", error)
    allSchedules.value = [] // 🌟 ล้างค่าให้เป็น Array ว่างเสมอเมื่อเกิด Error
    
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
  const token = localStorage.getItem('token')
  try {
    Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    
    // ดึงข้อมูลรายบุคคลจาก API
    const response = await api.get(`?route=ven/detail&id=${venId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });

    selectedVen.value = response.data;
    showRecipientList.value = false; 
    
    Swal.close();
    detailModalInstance.show();
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงรายละเอียดเวรได้', 'error');
  }
}

const loadRecipients = async () => {
  try {
    const sub_id = selectedVen.value.sub_id || selectedVen.value.ven_name_sub_id; 
    if (!sub_id) return Swal.fire('ข้อมูลไม่ครบถ้วน', 'ไม่พบข้อมูลรหัสหน้าที่(sub_id) ของเวรนี้', 'warning');

    const res = await api.get(`?route=admin/ven_user&action=get_by_sub&sub_id=${sub_id}`);
    recipients.value = res.data.filter(u => u.user_id != currentUserId.value);
    showRecipientList.value = true;
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถดึงรายชื่อผู้มีสิทธิ์ได้', 'error');
  }
};

const confirmTransfer = async (targetUser) => {
  const result = await Swal.fire({
    title: 'ยืนยันการโอนเวร?',
    html: `คุณแน่ใจหรือไม่ที่จะยกเวรให้ <b>${targetUser.name} ${targetUser.sname}</b> ?<br><small class="text-danger">เมื่อยืนยันแล้ว ชื่อในตารางจะถูกเปลี่ยนทันที</small>`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'ยืนยันการโอน',
    cancelButtonText: 'ยกเลิก'
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

onMounted(() => {
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
</style>