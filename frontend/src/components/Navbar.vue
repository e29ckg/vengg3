<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm d-print-none"  v-if="isLoggedIn">
    <div class="container-fluid">
      
      <router-link class="navbar-brand d-flex align-items-center" to="/home">
        <span class="fw-bold"><i class="bi bi-calendar2-check"></i> {{ systemName || 'ระบบเวรนอกเวลาทำการ' }}</span>
      </router-link>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" @click="toggleMobileMenu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" :class="{ 'show': isMobileMenuOpen }" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <router-link class="nav-link" active-class="active fw-bold" to="/home">
              <i class="bi bi-house"></i> หน้าแรก
            </router-link>
          </li>
          <li>
            <router-link class="nav-link" active-class="active fw-bold" to="/user/history">
              <i class="bi bi-calendar3"></i> ประวัติการเปลี่ยนเวร
            </router-link>
          </li>

          <li class="nav-item dropdown" v-if="userRole === 9 || userRole === 2">
            <a class="nav-link dropdown-toggle" 
               :class="{ 'active fw-bold': route.path.startsWith('/director') }" 
               href="#" role="button" data-bs-toggle="dropdown" @click.prevent="toggleDirectorMenu">
              <i class="bi bi-briefcase"></i> งานอำนวยการ
            </a>
            <ul class="dropdown-menu shadow-sm" :class="{ 'show': isDirectorMenuOpen }">
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/director/ven-settings"><i class="bi bi-gear"></i> จัดการชื่อเวร</router-link></li>
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/director/commands"><i class="bi bi-file-earmark-text"></i> จัดการคำสั่งเวร</router-link></li>
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/director/schedule"><i class="bi bi-calendar-check"></i> จัดเวร</router-link></li>
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/director/schedule-list"><i class="bi bi-card-list"></i> รายการการจัดเวร</router-link></li>
              <li><hr class="dropdown-divider"></li>
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/director/approvals"><i class="bi bi-check-circle"></i> อนุมัติใบเปลี่ยนเวร</router-link></li>
            </ul>
          </li>

          <li class="nav-item dropdown" v-if="userRole === 9 || userRole === 3">
            <a class="nav-link dropdown-toggle" 
               :class="{ 'active fw-bold': route.path.startsWith('/finance') }" 
               href="#" role="button" data-bs-toggle="dropdown" @click.prevent="toggleFinanceMenu">
              <i class="bi bi-cash-coin"></i> การเงิน
            </a>
            <ul class="dropdown-menu shadow-sm" :class="{ 'show': isFinanceMenuOpen }">
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/finance/report"><i class="bi bi-file-earmark-spreadsheet"></i> ออกรายงาน</router-link></li>
            </ul>
          </li>

          <li class="nav-item dropdown" v-if="userRole === 9">
            <a class="nav-link dropdown-toggle" 
               :class="{ 'active fw-bold': route.path.startsWith('/admin') }" 
               href="#" role="button" data-bs-toggle="dropdown" @click.prevent="toggleAdminMenu">
              <i class="bi bi-shield-lock"></i> ผู้ดูแลระบบ
            </a>
            <ul class="dropdown-menu shadow-sm" :class="{ 'show': isAdminMenuOpen }">
              <li>
                <router-link class="dropdown-item" active-class="active fw-bold" to="/admin/users">
                  <i class="bi bi-people"></i> จัดการผู้ใช้งาน
                </router-link>
              </li>
              <li>
                <router-link class="dropdown-item" to="/admin/options" active-class="active fw-bold">
                  <i class="bi bi-card-list"></i> จัดการคำนำหน้า ตำแหน่ง กลุ่มงาน
                </router-link>
              </li>
              <li><hr class="dropdown-divider"></li>            
              <li>  <router-link to="/admin/settings/telegram" class="dropdown-item"><i class="bi bi-telegram me-2"></i> ตั้งค่า Telegram</router-link></li>
              <li>
                <router-link to="/admin/settings/system" class="dropdown-item" active-class="active fw-bold">
                  <i class="bi bi-gear-wide-connected me-2 text-dark"></i> ตั้งค่าระบบ
                </router-link>
              </li>  
              <li>
                <router-link to="/admin/settings/google-calendar" class="dropdown-item py-2" active-class="active fw-bold bg-primary text-white">
                  <i class="bi bi-google me-2 text-danger"></i>ตั้งค่า Google Calendar
                </router-link>
              </li>       
              <li>
                <router-link to="/admin/settings/agency" class="dropdown-item" active-class="active fw-bold">
                  <i class="bi bi-building me-2 text-primary"></i> ตั้งค่าข้อมูลหน่วยงาน
                </router-link>
              </li>
            </ul>
          </li>
        </ul>

        <div class="d-flex align-items-center text-white mt-2 mt-lg-0">
          <router-link to="/profile" class="text-white text-decoration-none me-3 profile-link d-flex align-items-center" title="จัดการโปรไฟล์">
            
            <div class="rounded-circle border border-white me-2 d-flex align-items-center justify-content-center fw-bold text-uppercase" 
                style="width: 30px; height: 30px; background-color: rgba(255, 255, 255, 0.2);">
                {{ userName ? userName.charAt(0).toUpperCase() : '' }}
            </div>
            
            <span>{{ userName }}</span>
          </router-link>
          
          <button class="btn btn-light btn-sm rounded-pill fw-bold text-danger px-3 shadow-sm" @click="logout">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../services/api'
import Swal from 'sweetalert2'

const router = useRouter()
const route = useRoute()

// ข้อมูลผู้ใช้งาน
const isLoggedIn = ref(false)
const userName = ref('')
const userRole = ref(null)
const userAvatar = ref('')

// ตัวแปรควบคุมการเปิด/ปิดเมนู
const isAdminMenuOpen = ref(false)
const isDirectorMenuOpen = ref(false)
const isFinanceMenuOpen = ref(false) // เพิ่มตัวแปรสำหรับเมนูการเงิน
const isMobileMenuOpen = ref(false)
const systemName = ref('ระบบเวรนอกเวลาทำการ') // ชื่อระบบที่จะแสดงใน Navbar

// ฟังก์ชันเช็คว่าล็อกอินอยู่หรือไม่
const checkAuth = () => {
  const token = localStorage.getItem('token')
  if (token) {
    isLoggedIn.value = true
    userName.value = localStorage.getItem('username') || 'ผู้ใช้งาน'
    userRole.value = parseInt(localStorage.getItem('role')) || 0
    userAvatar.value = localStorage.getItem('avatar') || null
  } else {
    isLoggedIn.value = false
  }
}

// ฟังก์ชันสลับสถานะเมนูต่างๆ
const toggleAdminMenu = () => { 
  isAdminMenuOpen.value = !isAdminMenuOpen.value 
  if (isAdminMenuOpen.value) {
    isDirectorMenuOpen.value = false 
    isFinanceMenuOpen.value = false
  }
}

const toggleDirectorMenu = () => { 
  isDirectorMenuOpen.value = !isDirectorMenuOpen.value 
  if (isDirectorMenuOpen.value) {
    isAdminMenuOpen.value = false 
    isFinanceMenuOpen.value = false
  }
}

const toggleFinanceMenu = () => { 
  isFinanceMenuOpen.value = !isFinanceMenuOpen.value 
  if (isFinanceMenuOpen.value) {
    isAdminMenuOpen.value = false 
    isDirectorMenuOpen.value = false
  }
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// ตรวจสอบการเปลี่ยนหน้า (พอกดเปลี่ยนหน้าปุ๊บ สั่งปิดเมนูทั้งหมดทันที)
watch(() => route.path, () => {
  checkAuth()
  isAdminMenuOpen.value = false
  isDirectorMenuOpen.value = false
  isFinanceMenuOpen.value = false
  isMobileMenuOpen.value = false
}, { immediate: true })

// ดักจับการคลิกบนหน้าจอ (Click Outside) ปิด Dropdown
const closeDropdowns = (event) => {
  if (!event.target.closest('.dropdown')) {
    isAdminMenuOpen.value = false
    isDirectorMenuOpen.value = false
    isFinanceMenuOpen.value = false
  }
}

const fetchSystemSettings = async () => {
  try {
    const res = await api.get('?route=system_settings') // ปรับ Route ให้ตรงกับ API ของคุณ
    console.log("Fetched system settings:", res) // Debug log
    if (res.data && res.data.system_name) {
      systemName.value = res.data.system_name
      // 🌟 ปรับชื่อหัวเว็บ (Browser Title)
      document.title = res.data.system_name
    }
  } catch (error) {
    console.error("Fetch settings error:", error)
  }
}


// 🌟 เพิ่มพารามิเตอร์ name เข้ามา (ตั้งค่าเริ่มต้นเป็น 'User' เผื่อดึงชื่อไม่ทัน)
const getAvatarUrl = (filename, name = 'User') => {
  if (!filename || filename == null || filename == "null") {
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=random&color=fff&size=128&font-size=0.5`;
  }

  let baseUrl = api.defaults.baseURL || 'http://localhost/vengg3/backend/public/';
  baseUrl = baseUrl.split('?')[0].replace('index.php', '');
  if (!baseUrl.endsWith('/')) baseUrl += '/';
  
  return `${baseUrl}uploads/avatars/${filename}`;
}

const loadUserData = () => {
  const userStr = localStorage.getItem('user');
  if (userStr) {
    const userData = JSON.parse(userStr);
    // รองรับทั้งฟิลด์ name และ first_name
    userName.value = userData.name || userData.first_name || 'ผู้ใช้งาน'; 
    const separateAvatar = localStorage.getItem('avatar');
    if (separateAvatar) {
      userAvatar.value = separateAvatar;
    }
      userAvatar.value = userData.avatar || '';
  }
}

// เริ่มดักจับการคลิกเมื่อโหลดคอมโพเนนต์
onMounted(() => {
  loadUserData()
  document.addEventListener('click', closeDropdowns)
  fetchSystemSettings()
  window.addEventListener('user-updated', loadUserData)
  window.addEventListener('storage', loadUserData)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', closeDropdowns)
  window.removeEventListener('user-updated', loadUserData)
  window.removeEventListener('storage', loadUserData)
})

// ออกจากระบบ
const logout = async () => { 
  const result = await Swal.fire({
    title: 'ออกจากระบบ?',
    text: "คุณต้องการออกจากระบบใช่หรือไม่?",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'ใช่, ออกจากระบบ',
    cancelButtonText: 'ยกเลิก'
  });

  if (result.isConfirmed) {
    // ถ้ากดยืนยัน ค่อยเคลียร์ค่าแล้วเด้งไปหน้า login
    localStorage.clear()
    router.push('/login')
  }
}
</script>