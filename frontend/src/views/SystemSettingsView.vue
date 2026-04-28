<template>
  <div class="bg-light min-vh-100 pb-5">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
              <h5 class="mb-0 fw-bold text-primary">
                <i class="bi bi-sliders me-2"></i>เปิด-ปิด ฟังก์ชันการใช้งาน
              </h5>
            </div>
            
            <div class="card-body p-4">
              <div v-if="isLoading" class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2 text-muted">กำลังโหลดการตั้งค่า...</p>
              </div>

              <div v-else>
                <div class="setting-item d-flex justify-content-between align-items-center p-3 mb-3 border rounded-3 bg-white transition-hover">
                  <div>
                    <h6 class="fw-bold mb-1">อนุญาตให้เปลี่ยนเวรย้อนหลัง</h6>
                    <p class="text-muted small mb-0">หากปิด: จะไม่สามารถโอนหรือเปลี่ยนเวรที่วันที่ผ่านมาแล้วได้</p>
                  </div>
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input custom-switch" 
                      type="checkbox" 
                      v-model="settings.allow_retro_transfer" 
                      @change="handleToggle('allow_retro_transfer', settings.allow_retro_transfer)"
                    >
                  </div>
                </div>

                <div class="setting-item d-flex justify-content-between align-items-center p-3 mb-3 border rounded-3 bg-white transition-hover">
                  <div>
                    <h6 class="fw-bold mb-1">แจ้งเตือนการเข้าเวรติดต่อกัน 24 ชม.</h6>
                    <p class="text-muted small mb-0">หากเปิด: ระบบจะแจ้งเตือนเมื่อพบการอยู่เวรเช้า-ดึกติดกัน</p>
                  </div>
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input custom-switch" 
                      type="checkbox" 
                      v-model="settings.enable_24h_check" 
                      @change="handleToggle('enable_24h_check', settings.enable_24h_check)"
                    >
                  </div>
                </div>

                <div class="alert alert-info border-0 rounded-3 mt-4">
                  <i class="bi bi-info-circle-fill me-2"></i>
                  การเปลี่ยนแปลงการตั้งค่าจะมีผลกับผู้ใช้งานทุกคนในระบบทันที
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';
import Swal from 'sweetalert2';

const router = useRouter();
const isLoading = ref(true);
const userRole = parseInt(localStorage.getItem('role') || 1);

// สถานะการตั้งค่า
const settings = ref({
  allow_retro_transfer: false,
  enable_24h_check: false
});

// ตรวจสอบสิทธิ์ (ถ้าไม่ใช่ Admin ให้เด้งออก)
const checkPermission = () => {
  if (userRole !== 9) {
    Swal.fire('ปฏิเสธการเข้าถึง', 'หน้านี้สำหรับผู้ดูแลระบบเท่านั้น', 'error');
    router.push('/home');
  }
};

// ดึงข้อมูลการตั้งค่าจาก Backend
const fetchSettings = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('?route=settings/app');
    // แปลงค่า '1' เป็น true, '0' เป็น false
    settings.value.allow_retro_transfer = res.data.allow_retro_transfer === '1';
    settings.value.enable_24h_check = res.data.enable_24h_check === '1';
  } catch (error) {
    console.error("Fetch settings error:", error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดข้อมูลการตั้งค่าได้', 'error');
  } finally {
    isLoading.value = false;
  }
};

// อัปเดตการตั้งค่าเมื่อมีการเลื่อนสวิตช์
const handleToggle = async (key, value) => {
  const valString = value ? '1' : '0';
  const label = key === 'allow_retro_transfer' ? 'การเปลี่ยนเวรย้อนหลัง' : 'การแจ้งเตือน 24 ชม.';
  
  try {
    const res = await api.post('?route=settings/update', {
      setting_key: key,
      setting_value: valString
    });

    if (res.data.success) {
      const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
      });
      Toast.fire({
        icon: 'success',
        title: `อัปเดต ${label} เป็น ${value ? 'เปิด' : 'ปิด'} เรียบร้อยแล้ว`
      });
    }
  } catch (error) {
    console.error("Update setting error:", error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกค่าได้ (อาจไม่มีสิทธิ์ Admin)', 'error');
    // ถ้าพลาด ให้เปลี่ยนสวิตช์กลับเป็นค่าเดิม
    settings.value[key] = !value;
  }
};

onMounted(() => {
  checkPermission();
  fetchSettings();
});
</script>

<style scoped>
.custom-switch {
  width: 3.5rem;
  height: 1.75rem;
  cursor: pointer;
}

.setting-item {
  transition: all 0.2s ease;
}

.transition-hover:hover {
  background-color: #f8f9fa !important;
  border-color: #0d6efd !important;
  transform: translateY(-2px);
}

.card {
  border-radius: 1rem;
}

/* ปรับแต่งสีสวิตช์ตอนเปิด */
.form-check-input:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}
</style>