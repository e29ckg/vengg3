<template>
  <div class="bg-light min-vh-100">
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

    <div class="container-fluid py-4 px-4">
      <div class="card shadow-sm border-0 rounded-4 p-4">
        <div v-if="isLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted fw-semibold">กำลังดึงข้อมูลปฏิทิน...</p>
        </div>
        <div v-else class="calendar-container">
          <FullCalendar :options="calendarOptions" />
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
                <strong>{{ formatDate(selectedVen.ven_date) }}</strong> (เวลา {{ selectedVen.ven_time.substring(0,5) }} น.)
              </p>
              <p class="mb-2"><i class="bi bi-moon-stars me-2 text-warning"></i> {{ selectedVen.duty_main }}</p>
              <p class="mb-2"><i class="bi bi-file-earmark-text me-2 text-secondary"></i> คำสั่งที่ {{ selectedVen.command_num }}</p>
              <p class="mb-0 text-success fw-bold fs-5"><i class="bi bi-cash-coin me-2"></i> {{ Number(selectedVen.price).toLocaleString() }} บาท</p>
            </div>

            <div class="d-grid gap-2">
              <button class="btn btn-primary rounded-pill fw-bold shadow-sm py-2">
                <i class="bi bi-file-earmark-check me-1"></i> รายงานการปฏิบัติหน้าที่
              </button>
              <button class="btn btn-warning rounded-pill fw-bold text-dark shadow-sm py-2">
                <i class="bi bi-arrow-left-right me-1"></i> ขอสลับเวร / ยกเวรให้ผู้อื่น
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap' // นำเข้าไลบรารี Modal ของ Bootstrap

import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import thLocale from '@fullcalendar/core/locales/th'
import api from '../services/api'

const router = useRouter()
const currentUsername = ref(localStorage.getItem('username') || 'ผู้ใช้งาน')
const userRole = parseInt(localStorage.getItem('role') || 1)
const isLoading = ref(true)

// ตัวแปรสำหรับเก็บข้อมูลที่จะแสดงใน Modal
const selectedVen = ref(null)
let detailModalInstance = null // เก็บตัวแปรควบคุม Modal

const isAdminMenuOpen = ref(false)
const toggleAdminMenu = () => { isAdminMenuOpen.value = !isAdminMenuOpen.value }

// 🌟 เพิ่มตัวแปรคุมเมนู "งานอำนวยการ"
const isDirectorMenuOpen = ref(false)
const toggleDirectorMenu = () => { isDirectorMenuOpen.value = !isDirectorMenuOpen.value }

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  locales: [thLocale],
  locale: 'th',
  height: 'auto',
  headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth' },
  events: [],
  
  eventContent: (arg) => {
    const timeStr = arg.event.extendedProps.ven_time ? arg.event.extendedProps.ven_time.substring(0, 5) : '';
    return {
      html: `
        <div class="p-1 rounded shadow-sm text-white w-100 overflow-hidden" 
             style="background-color: ${arg.event.backgroundColor}; font-size: 0.85em; cursor: pointer; border-left: 4px solid rgba(0,0,0,0.2);">
          <div class="fw-bold text-truncate"><i class="bi bi-clock me-1"></i>${timeStr}</div>
          <div class="text-truncate">${arg.event.title}</div>
        </div>
      `
    }
  },
  
  // 🌟 เมื่อคลิกที่ป้ายเวร ให้วิ่งไปดึงข้อมูลแล้วเปิด Modal
  eventClick: async (info) => {
    const venId = info.event.id;
    const token = localStorage.getItem('token')

    try {
      // แสดงตัวโหลดแบบ Pop-up ชั่วคราว
      Swal.fire({ title: 'กำลังโหลดข้อมูล...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      // ดึงข้อมูลรายบุคคลจาก API
      const response = await api.get(`?route=ven/detail&id=${venId}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      // นำข้อมูลที่ได้ใส่ลงในตัวแปร selectedVen
      selectedVen.value = response.data;
      
      // ปิดตัวโหลด Swal และเปิด Bootstrap Modal
      Swal.close();
      detailModalInstance.show();

    } catch (error) {
      console.error(error);
      Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงรายละเอียดเวรได้', 'error');
    }
  }
})

// ฟังก์ชันจัดรูปแบบวันที่ให้อ่านง่าย (เช่น 7 พฤษภาคม 2569)
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const options = { year: 'numeric', month: 'long', day: 'numeric' };
  return date.toLocaleDateString('th-TH', options);
}

// ฟังก์ชันสร้างรูป Avatar จำลองถ้าไม่มีรูปจริง
const getProfileImage = (ven) => {
  if (ven.profile_image && ven.profile_image !== 'default_avatar.jpg') {
    return `http://localhost/vengg3/backend/public/uploads/profiles/${ven.profile_image}`; // ปรับ Path ตามจริง
  }
  // ถ้าไม่มีรูป ให้สร้างรูปภาพที่มีตัวอักษรชื่อแทน
  return `https://ui-avatars.com/api/?name=${encodeURIComponent(ven.full_name)}&background=random&color=fff&size=128`;
}

const fetchVenData = async () => {
  isLoading.value = true
  const token = localStorage.getItem('token')
  try {
    const response = await api.get('?route=ven/list', {
      headers: { 'Authorization': `Bearer ${token}` }
    })
    calendarOptions.value.events = response.data
  } catch (error) {
    if (error.response?.status === 401) {
      Swal.fire('เซสชันหมดอายุ', 'กรุณาเข้าสู่ระบบใหม่อีกครั้ง', 'warning')
      handleLogout()
    }
  } finally {
    isLoading.value = false
  }
}

const handleLogout = () => {
  localStorage.clear()
  router.push('/login')
}

onMounted(() => {
  fetchVenData()
  // ผูกตัวแปร Modal กับ HTML element เมื่อหน้าเว็บโหลดเสร็จ
  detailModalInstance = new Modal(document.getElementById('venDetailModal'))
})
</script>

<style>
/* คงสไตล์ของ FullCalendar ไว้เหมือนเดิม */
.fc .fc-button-primary { background-color: #0d6efd !important; border-color: #0d6efd !important; border-radius: 0.5rem !important; text-transform: capitalize; }
.fc .fc-button-primary:hover { background-color: #0b5ed7 !important; }
.fc-event { border: none !important; background-color: transparent !important; margin-bottom: 2px !important; }
.fc-col-header-cell-cushion { color: #495057; font-weight: bold; text-decoration: none !important; padding: 10px 0 !important; }
.fc-daygrid-day-number { color: #6c757d; font-weight: 500; text-decoration: none !important; }
</style>