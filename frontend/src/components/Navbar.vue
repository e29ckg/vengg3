<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm d-print-none" v-if="isLoggedIn">
    <div class="container-fluid">
      
      <router-link class="navbar-brand d-flex align-items-center" to="/home">
        <span class="fw-bold"><i class="bi bi-calendar2-check"></i> {{ systemName || 'ระบบเวรนอกเวลาทำการ' }}</span>
      </router-link>

      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" @click="toggleMobileMenu">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" :class="{ 'show': isMobileMenuOpen }" id="navbarNav">
        
        <ul class="navbar-nav ms-auto align-items-lg-center">
          
          <li class="nav-item">
            <router-link class="nav-link py-2" active-class="active fw-bold" to="/home">
              <i class="bi bi-house me-1"></i> หน้าแรก
            </router-link>
          </li>
          <li class="nav-item">
            <router-link class="nav-link py-2" active-class="active fw-bold" to="/user/history">
              <i class="bi bi-calendar3 me-1"></i> ประวัติการเปลี่ยนเวร
            </router-link>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle py-2" :class="{ 'active fw-bold': route.path.startsWith('/director') }" href="#" @click.prevent="toggleDirectorMenu">
              <i class="bi bi-briefcase me-1"></i> งานอำนวยการ
            </a>
            <ul class="dropdown-menu shadow" :class="{ 'show': isDirectorMenuOpen }">
              <li><router-link class="dropdown-item py-2" to="/director/ven-settings"><i class="bi bi-gear me-2"></i> เตรียมข้อมูลเวร</router-link></li>
              <li><router-link class="dropdown-item py-2" to="/director/commands"><i class="bi bi-file-earmark-text me-2"></i> จัดการคำสั่งเวร</router-link></li>
              <li><router-link class="dropdown-item py-2" to="/director/schedule"><i class="bi bi-calendar-check me-2"></i> จัดเวร</router-link></li>
              <li><router-link class="dropdown-item py-2" to="/director/schedule-list"><i class="bi bi-card-list me-2"></i> รายงานการจัดเวร</router-link></li>
              <li><hr class="dropdown-divider"></li>
              <li><router-link class="dropdown-item py-2" to="/director/approvals"><i class="bi bi-check-circle me-2"></i> อนุมัติใบเปลี่ยนเวร</router-link></li>
            </ul>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle py-2" :class="{ 'active fw-bold': route.path.startsWith('/finance') }" href="#" @click.prevent="toggleFinanceMenu">
              <i class="bi bi-cash-coin me-1"></i> การเงิน
            </a>
            <ul class="dropdown-menu shadow" :class="{ 'show': isFinanceMenuOpen }">
              <li><router-link class="dropdown-item py-2" to="/finance/report"><i class="bi bi-file-earmark-spreadsheet me-2"></i> ออกรายงาน</router-link></li>
            </ul>
          </li>

          <li class="nav-item dropdown" v-if="userRole === 9">
            <a class="nav-link dropdown-toggle py-2" :class="{ 'active fw-bold': route.path.startsWith('/admin') }" href="#" @click.prevent="toggleAdminMenu">
              <i class="bi bi-shield-lock me-1"></i> ผู้ดูแลระบบ
            </a>
            <ul class="dropdown-menu shadow" :class="{ 'show': isAdminMenuOpen }">
              <li><router-link class="dropdown-item py-2" to="/admin/users"><i class="bi bi-people me-2"></i> จัดการผู้ใช้งาน</router-link></li>
              <li><router-link class="dropdown-item py-2" to="/admin/options"><i class="bi bi-card-list me-2"></i> จัดการค่าเริ่มต้น</router-link></li>
              <li><router-link class="dropdown-item py-2" to="/admin/settings/agency"><i class="bi bi-building me-2"></i> ข้อมูลหน่วยงาน</router-link></li>
              <li><hr class="dropdown-divider"></li>
              <li><router-link class="dropdown-item py-2" to="/admin/settings/google-calendar"><i class="bi bi-google me-2"></i> Google Calendar</router-link></li>
              <li><router-link class="dropdown-item py-2" to="/admin/settings/telegram"><i class="bi bi-telegram me-2"></i> Telegram Bot</router-link></li>
            </ul>
          </li>
        </ul>

        <hr class="d-lg-none bg-white opacity-25">

        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-3 ms-lg-4 mt-lg-0 mt-3 pb-3 pb-lg-0">
          <router-link to="/profile" class="text-white text-decoration-none d-flex align-items-center">
            <div class="rounded-circle border border-white d-flex align-items-center justify-content-center fw-bold" 
                 style="width: 35px; height: 35px; background-color: rgba(255, 255, 255, 0.2);">
                 {{ userName ? userName.charAt(0).toUpperCase() : 'U' }}
            </div>
            <span class="ms-2">{{ userName }}</span>
          </router-link>
          
          <button class="btn btn-outline-light rounded-pill fw-bold" @click="logout">
            <i class="bi bi-box-arrow-right"></i> ออก
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