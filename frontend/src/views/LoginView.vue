<template>
  <div class="container d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow-lg border-0 rounded-4 p-4" style="width: 100%; max-width: 400px;">
      <div class="text-center mb-4">
        <h3 class="fw-bold text-primary">Vengg3</h3>
        <p class="text-muted">ระบบจัดการเวรนอกเวลาทำการ</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="mb-3">
          <label for="username" class="form-label fw-semibold">ชื่อผู้ใช้งาน</label>
          <input type="text" class="form-control rounded-pill px-4" id="username" v-model="username" required placeholder="กรอกชื่อผู้ใช้งาน">
        </div>
        
        <div class="mb-4">
          <label for="password" class="form-label fw-semibold">รหัสผ่าน</label>
          <input type="password" class="form-control rounded-pill px-4" id="password" v-model="password" required placeholder="กรอกรหัสผ่าน">
        </div>

        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2" :disabled="isLoading">
          <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          {{ isLoading ? 'กำลังตรวจสอบ...' : 'เข้าสู่ระบบ' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
// 1. นำเข้า onMounted เพิ่มเติม
import { ref, onMounted } from 'vue' 
import axios from 'axios'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import api from '../services/api'

// ตัวแปรเก็บค่าจากฟอร์ม
const username = ref('')
const password = ref('')
const isLoading = ref(false)
const router = useRouter()

// 🌟 2. เพิ่มส่วน onMounted ตรวจสอบ Token ตอนโหลดหน้าเว็บ
onMounted(() => {
  const token = localStorage.getItem('token')
  if (token) {
    // ถ้ามี Token อยู่แล้ว ให้เด้งไปหน้า /home ทันที
    router.push('/home')
  }
})

// ฟังก์ชันทำงานเมื่อกดปุ่ม Submit
const handleLogin = async () => {
  isLoading.value = true
  
  try {
    // 1. ส่งข้อมูลไปหา Backend API
    const response = await api.post('?route=auth/login', {
      username: username.value,
      password: password.value
    })

    // 2. ถ้า Backend ตอบกลับมาว่า Login สำเร็จ
    if (response.status === 200) {
      const userData = response.data.user
      const userToken = response.data.token;
      
      // บันทึก Token และสิทธิ์ไว้ในเบราว์เซอร์
      localStorage.setItem('role', userData.role)
      localStorage.setItem('username', userData.username)
      localStorage.setItem('token', userToken)

      // แสดง Popup สำเร็จ
      Swal.fire({
        icon: 'success',
        title: 'สำเร็จ!',
        text: 'ยินดีต้อนรับเข้าสู่ระบบ',
        timer: 1500,
        showConfirmButton: false
      })

      // เปลี่ยนหน้าไปที่ /home
      router.push('/home')
    }
  } catch (error) {
    if (error.response?.data?.error === 'MAINTENANCE') {
      Swal.fire({
        icon: 'warning',
        title: 'ปิดปรับปรุงระบบ',
        html: `<b>ขออภัยในความไม่สะดวก</b><br><br>${error.response.data.message}`,
        confirmButtonText: 'ตกลง',
        confirmButtonColor: '#f39c12',
        allowOutsideClick: false // บังคับให้ต้องกดปุ่มตกลง
      });
      return; // หยุดการทำงานทันที
    }
    // 3. ถ้าผิดพลาด (เช่น รหัสผิด, ไม่มี user)
    Swal.fire({
      icon: 'error',
      title: 'เข้าสู่ระบบไม่สำเร็จ',
      text: error.response?.data?.error || 'เกิดข้อผิดพลาด ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
    })
  } finally {
    isLoading.value = false // ปิดตัวโหลดหมุนๆ
  }
}
</script>

<style scoped>
/* คุณสามารถเพิ่ม CSS ย่อยๆ ของหน้านี้ได้ที่นี่ */
.bg-light {
  background-color: #f8f9fa !important;
}
</style>