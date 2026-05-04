<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm d-print-none"  v-if="isLoggedIn">
    <div class="container-fluid">
      <router-link class="navbar-brand fw-bold" to="/home">
        <i class="bi bi-calendar2-check"></i> ระบบจัดเวร
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
              <li><router-link class="dropdown-item" active-class="active fw-bold" to="/admin/users"><i class="bi bi-people"></i> จัดการผู้ใช้งาน</router-link></li>
              <li>
                <router-link to="/admin/settings/system" class="dropdown-item" active-class="active fw-bold">
                  <i class="bi bi-gear-wide-connected me-2 text-dark"></i> ตั้งค่าระบบ
                </router-link>
              </li>             
              <li>  <router-link to="/admin/settings/telegram" class="dropdown-item"><i class="bi bi-telegram me-2"></i> ตั้งค่า Telegram</router-link></li>
              <li>
                <router-link to="/admin/settings/agency" class="dropdown-item" active-class="active fw-bold">
                  <i class="bi bi-building me-2 text-primary"></i> ตั้งค่าข้อมูลหน่วยงาน
                </router-link>
              </li>
            </ul>
          </li>
        </ul>

        <div class="d-flex align-items-center text-white mt-2 mt-lg-0">
          <div class="me-3">
            <i class="bi bi-person-circle"></i> {{ userName }}
          </div>
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

const router = useRouter()
const route = useRoute()

// ข้อมูลผู้ใช้งาน
const isLoggedIn = ref(false)
const userName = ref('')
const userRole = ref(null)

// ตัวแปรควบคุมการเปิด/ปิดเมนู
const isAdminMenuOpen = ref(false)
const isDirectorMenuOpen = ref(false)
const isFinanceMenuOpen = ref(false) // เพิ่มตัวแปรสำหรับเมนูการเงิน
const isMobileMenuOpen = ref(false)

// ฟังก์ชันเช็คว่าล็อกอินอยู่หรือไม่
const checkAuth = () => {
  const token = localStorage.getItem('token')
  if (token) {
    isLoggedIn.value = true
    userName.value = localStorage.getItem('username') || 'ผู้ใช้งาน'
    userRole.value = parseInt(localStorage.getItem('role')) || 0
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

// เริ่มดักจับการคลิกเมื่อโหลดคอมโพเนนต์
onMounted(() => {
  document.addEventListener('click', closeDropdowns)
})

// ยกเลิกดักจับเมื่อคอมโพเนนต์ถูกทำลาย ป้องกันค้างในหน่วยความจำ
onBeforeUnmount(() => {
  document.removeEventListener('click', closeDropdowns)
})

// ออกจากระบบ
const logout = () => {
  localStorage.clear()
  router.push('/login')
}
</script>