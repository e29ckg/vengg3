<template>
  <div class="container-fluid mt-4" style="max-width: 800px;">
    <h4 class="fw-bold text-primary mb-4"><i class="bi bi-person-lines-fill"></i> จัดการโปรไฟล์ส่วนตัว</h4>

    <div class="card shadow-sm border-0 mb-4">
      <div class="card-header bg-light fw-bold">ข้อมูลทั่วไป</div>
      <div class="card-body">

        <div class="text-center mb-4 pb-4 border-bottom">
          <div class="position-relative d-inline-block">
            <!-- <img :src="getAvatarUrl(profile.avatar, profile.first_name)" class="rounded-circle border border-3 border-primary shadow-sm" style="width: 120px; height: 120px; object-fit: cover;">            
            <input type="file" ref="fileInput" class="d-none" accept="image/png, image/jpeg, image/webp" @change="uploadAvatar">
            <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 start-100 translate-middle-x rounded-circle" style="width: 35px; height: 35px;" @click="$refs.fileInput.click()">
              <i class="bi bi-camera"></i>
            </button> -->
            <div class="rounded-circle border border-3 border-primary shadow-sm d-flex align-items-center justify-content-center fw-bold text-uppercase text-primary bg-primary bg-opacity-10" 
                style="width: 120px; height: 120px; font-size: 3.5rem;">
              {{ profile.first_name ? profile.first_name.charAt(0).toUpperCase() : '' }}
            </div>
          </div>
          <!-- <div class="small text-muted mt-2">คลิกที่ไอคอนกล้องเพื่อเปลี่ยนรูปภาพ</div> -->                        
        </div>

        

        <form @submit.prevent="updateProfile">
          <div class="row g-3">
            <div class="col-md-2">
              <label class="form-label text-muted small">คำนำหน้า</label>
              <select class="form-select" v-model="profile.prefix_name">
                <option value="" disabled selected>-- คำนำหน้าชื่อ --</option>
                <option 
                  v-for="(f, index) in prefixes" 
                  :key="'f-'+index" 
                  :value="f"
                >
                  {{ f }}
                </option>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label text-muted small">ชื่อ</label>
              <input type="text" class="form-control" v-model="profile.first_name" required>
            </div>
            <div class="col-md-5">
              <label class="form-label text-muted small">นามสกุล</label>
              <input type="text" class="form-control" v-model="profile.last_name" required>
            </div>

            <div class="col-md-6">
              <label class="form-label text-muted small">ตำแหน่ง</label>
              <select class="form-select" v-model="profile.position">
                <option value="" disabled selected>-- กรุณาเลือกตำแหน่ง --</option>
                <option 
                  v-for="(pos, index) in positions" 
                  :key="'pos-'+index" 
                  :value="pos"
                >
                  {{ pos }}
                </option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">กลุ่มงาน</label>
              <select class="form-select" v-model="profile.department">
                <option value="" disabled selected>-- กรุณาเลือกกลุ่มงาน --</option>
                <option 
                  v-for="(g, index) in departments" 
                  :key="'g-'+index" 
                  :value=" g"
                >
                  {{ g }}
                </option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label text-muted small">เบอร์โทรศัพท์</label>
              <input type="text" class="form-control" v-model="profile.phone">
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">เลขที่บัญชี</label>
              <input type="text" class="form-control" v-model="profile.bank_account">
            </div>
            <div class="col-md-4">
              <label class="form-label text-muted small">ธนาคาร / สาขา</label>
              <input type="text" class="form-control" v-model="profile.bank_comment" placeholder="เช่น กรุงไทย สาขาศาลากลาง">
            </div>

            <div class="col-12 mt-4 text-end">
              <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-save"></i> บันทึกข้อมูล
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm border-0 border-top border-danger border-3">
      <div class="card-header bg-light fw-bold text-danger">เปลี่ยนรหัสผ่าน</div>
      <div class="card-body">
        <form @submit.prevent="changePassword">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label text-muted small">รหัสผ่านปัจจุบัน</label>
              <input type="password" class="form-control" v-model="pwd.old_password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">รหัสผ่านใหม่</label>
              <input type="password" class="form-control" v-model="pwd.new_password" required minlength="6">
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small">ยืนยันรหัสผ่านใหม่</label>
              <input type="password" class="form-control" v-model="pwd.confirm_password" required minlength="6">
            </div>
            <div class="col-12 mt-3 text-end">
              <button type="submit" class="btn btn-danger px-4">
                <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'

const profile = ref({
  prefix_name: '',
  first_name: '',
  last_name: '',
  position: '',       // เพิ่มใหม่
  departments: '',     // เพิ่มใหม่
  phone: '',
  bank_account: '',   // เพิ่มใหม่
  bank_comment: ''       
})

const pwd = ref({
  old_password: '',
  new_password: '',
  confirm_password: ''
})

// ดึงข้อมูลเดิมมาแสดง
const fetchProfile = async () => {
  try {
    const res = await api.get('?route=user/profile')
    if (res.data) profile.value = res.data
  } catch (error) {
    console.error(error)
  }
}

const positions = ref([]);
const departments = ref([]);
const prefixes = ref([]);

// 🌟 2. สร้างฟังก์ชันดึงข้อมูลตัวเลือก
const fetchOptions = async () => {
  try {
    const res = await api.get('?route=admin/user/options')
    if (res.data) {
      // สมมติว่า Backend ส่งข้อมูลมาเป็น { positions: [...], departments: [...] }
      positions.value = res.data.positions || [];
      departments.value = res.data.departments || [];
      prefixes.value = res.data.prefixes || [];
    }
  } catch (error) {
    console.error("ไม่สามารถดึงข้อมูลตัวเลือกได้:", error);
  }
}

// อัปเดตข้อมูลชื่อ
const updateProfile = async () => {
  try {
    await api.post('?route=user/profile/update', profile.value)
    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', toast: true, position: 'top-end', timer: 2000, showConfirmButton: false })
    
    localStorage.setItem('username',`${profile.value.prefix_name}${profile.value.first_name} ${profile.value.last_name}`);
    
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  }
}

// เปลี่ยนรหัสผ่าน
const changePassword = async () => {
  if (pwd.value.new_password !== pwd.value.confirm_password) {
    Swal.fire('แจ้งเตือน', 'รหัสผ่านใหม่และการยืนยันไม่ตรงกัน', 'warning')
    return
  }

  try {
    const res = await api.post('?route=user/profile/password', {
      old_password: pwd.value.old_password,
      new_password: pwd.value.new_password
    })
    
    Swal.fire('สำเร็จ', 'รหัสผ่านถูกเปลี่ยนเรียบร้อยแล้ว', 'success')
    pwd.value = { old_password: '', new_password: '', confirm_password: '' } // เคลียร์ฟอร์ม
  } catch (error) {
    const msg = error.response?.data?.error || 'เกิดข้อผิดพลาด'
    Swal.fire('ไม่สำเร็จ', msg, 'error')
  }
}

// เพิ่มตัวแปร
const fileInput = ref(null);


// ฟังก์ชันอัปโหลดรูป
const uploadAvatar = async (event) => {
  const file = event.target.files[0];
  if (!file) return;

  // จำกัดขนาดไฟล์ไม่เกิน 2MB
  if (file.size > 2 * 1024 * 1024) {
    Swal.fire('แจ้งเตือน', 'ขนาดรูปภาพต้องไม่เกิน 2MB', 'warning');
    return;
  }

  const formData = new FormData();
  formData.append('avatar', file);

  try {
    // ต้องใส่ Header เป็น multipart/form-data เพื่อส่งไฟล์
    const res = await api.post('?route=user/profile/upload_avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    // อัปเดตรูปหน้าจอทันที
    profile.value.avatar = res.data.avatar;
    
    if (res.data.avatar) {
      localStorage.setItem('avatar', res.data.avatar);
      
      // Dispatch Event บังคับให้ Navbar เปลี่ยนรูป (ถ้าต้องการให้เรียลไทม์)
      window.dispatchEvent(new Event('user-updated'));
    }

    Swal.fire({ icon: 'success', title: 'อัปเดตรูปโปรไฟล์แล้ว', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false });
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถอัปโหลดรูปภาพได้', 'error');
  }
}

const getAvatarUrl = (filename, name = 'User') => {
  if (!filename || filename == null) {
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
    userAvatar.value = userData.avatar || '';
  }
}

onMounted(() => {
    fetchOptions();
    fetchProfile();
})
</script>