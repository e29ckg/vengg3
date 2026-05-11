<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-md-9 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0"><i class="bi bi-gear-wide-connected me-2"></i>ตั้งค่าระบบ (System Settings)</h5>
          </div>
          <div class="card-body p-4">
            <form @submit.prevent="saveSettings">
              <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-display me-2"></i>ข้อมูลทั่วไป</h6>
              <div class="mb-4">
                <label class="form-label text-muted small fw-bold">ชื่อระบบ (System Name)</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="settings.system_name" 
                  placeholder="เช่น ระบบบริหารจัดการเวรนอกเวลาทำการ"
                  @change="updateSystemName"
                  required
                >
                <div class="form-text">ชื่อนี้จะไปปรากฏที่แถบเมนู (Navbar) และหัวเว็บ</div>
              </div>
              <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" id="compactView" 
                      v-model="settings.compact_schedule_view" @change="updateSingleSetting('compact_schedule_view', settings.compact_schedule_view)" >
                <label class="form-check-label fw-bold" for="compactView">
                  แสดงปฏิทินตารางเวรแบบกะทัดรัด (บรรทัดเดียว)
                </label>
                <div class="form-text">หากเปิดใช้งาน ปฏิทินจะแสดงไอคอนพระอาทิตย์/พระจันทร์แทนตัวเลขเวลา เพื่อประหยัดพื้นที่</div>
              </div>

              <hr class="my-4">
              <h6 class="fw-bold mb-3 text-success"><i class="bi bi-arrow-left-right me-2"></i>กฎการเปลี่ยน/แลกเวร</h6>
                            
              <div class="card bg-light border-0 mb-3">
                <div class="card-body p-3">
                  <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" v-model="settings.allow_swap" @change="updateSingleSetting('allow_swap', settings.allow_swap)" id="allowSwap">
                    <label class="form-check-label" for="allowSwapToggle" style="cursor: pointer;">
                      <span class="fw-bold d-block text-dark">อนุญาตให้ใช้งานฟังก์ชัน "สลับเปลี่ยนเวร"</span>
                      <small class="text-muted">
                        หาก <b>ปิด</b> ผู้ใช้งานจะสามารถ <u>ยกเวรให้ผู้อื่น (โอนขาด)</u> ได้เพียงอย่างเดียว ไม่สามารถนำเวรมาแลกกันได้
                      </small>
                    </label>
                  </div>

                  
                  <div>
                    <label class="form-label text-muted small fw-bold">จำนวนวันล่วงหน้าขั้นต่ำในการขอแลกเวร (วัน)</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      v-model="settings.advance_swap_days" 
                      @change="updateSingleSetting2('advance_swap_days', settings.advance_swap_days)"
                      min="0" 
                      placeholder="เช่น 3"
                    >
                    <div class="small text-muted mt-1">ใส่ 0 หากสามารถแลกเวรในวันเดียวกันได้</div>
                  </div>
                </div>
              </div>

              <div class="card bg-light border-0 mb-3">
                <div class="card-body p-3">
                    <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" v-model="settings.allow_retroactive_swap" @change="updateSingleSetting('allow_retroactive_swap', settings.allow_retroactive_swap)" id="retroactiveSwap">
                    <label class="form-check-label fw-bold" for="retroactiveSwap">อนุญาตให้ส่งคำขอเปลี่ยนเวรย้อนหลังได้</label>
                    <div class="small text-muted mt-1">หากปิด วันที่ที่ผ่านมาแล้วจะไม่สามารถเลือกทำรายการแลกเวรได้</div>
                    </div>

                    <hr>

                    <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" v-model="settings.check_24h_consecutive" @change="updateSingleSetting('check_24h_consecutive', settings.check_24h_consecutive)" id="check24h">
                    <label class="form-check-label fw-bold" for="check24h">เปิดใช้งานระบบแจ้งเตือนการเข้าเวรติดต่อกัน 24 ชม.</label>
                    <div class="small text-muted mt-1">ระบบจะแจ้งเตือน (Warning) หากเจ้าหน้าที่ถูกจัดเวรในวันติดกัน (เช่น เวรเช้าวันนี้ และเวรดึกวันถัดไป)</div>
                    </div>
                </div>
                </div>

              <hr class="my-4">
              <h6 class="fw-bold mb-3 text-danger"><i class="bi bi-shield-lock me-2"></i>ความปลอดภัยและสถานะระบบ</h6>
              <div class="card border-danger border-opacity-25 bg-danger bg-opacity-10 mb-4">
                <div class="card-body p-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input bg-danger" type="checkbox" v-model="settings.maintenance_mode" @change="updateSingleSetting('maintenance_mode', settings.maintenance_mode)" id="maintenanceMode">
                    <label class="form-check-label text-danger fw-bold" for="maintenanceMode">เปิดโหมดปรับปรุงระบบ (Maintenance Mode)</label>
                    <div class="small text-danger mt-1">หากเปิด ผู้ใช้ทั่วไปจะไม่สามารถเข้าใช้งานระบบได้จนกว่าแอดมินจะปิด</div>
                  </div>
                </div>
              </div>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-dark py-2" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-save me-2" v-else></i> บันทึกการตั้งค่าระบบ
                </button>
              </div>

            </form>

            
          </div>

          <div class="card-header py-3">
            <div class="d-flex gap-2">
              <button class="btn btn-info fw-bold text-white shadow-sm" @click="downloadSqlBackup">
                <i class="bi bi-database-down"></i> สำรองเฉพาะฐานข้อมูล (.sql)
              </button>
  
              <!-- <button class="btn btn-warning fw-bold shadow-sm" @click="downloadImageBackup">
                <i class="bi bi-file-zip"></i> สำรองรูปภาพ (.zip)
              </button> -->
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import Swal from 'sweetalert2'

const loading = ref(false)
const settings = ref({
  system_name: '',
  allow_swap: true,
  advance_swap_days: 0,
  maintenance_mode: false,
  allow_retroactive_swap: false, 
  check_24h_consecutive: true,
  compact_schedule_view :false
})

const fetchSettings = async () => {
  try {
    const res = await api.get('?route=admin/system_settings')
    if (res.data) {
      settings.value = {
        system_name: res.data.system_name || '',
        allow_swap: res.data.allow_swap == 1 || res.data.allow_swap === true,
        advance_swap_days: parseInt(res.data.advance_swap_days) || 0,        
        maintenance_mode: res.data.maintenance_mode == 1 || res.data.maintenance_mode === true,
        allow_retroactive_swap: res.data.allow_retroactive_swap == 1 || res.data.allow_retroactive_swap === true,
        check_24h_consecutive: res.data.check_24h_consecutive == 1 || res.data.check_24h_consecutive === true,
        compact_schedule_view: res.data.compact_schedule_view == 1 || res.data.compact_schedule_view === true
      }
    }
  } catch (err) {
    console.error("Error fetching system settings:", err)
  }
}

const saveSettings = async () => {
  loading.value = true
  try {
    const payload = {
      ...settings.value,
      allow_swap: settings.value.allow_swap ? 1 : 0,
      maintenance_mode: settings.value.maintenance_mode ? 1 : 0,
      allow_retroactive_swap: settings.value.allow_retroactive_swap ? 1 : 0, 
      check_24h_consecutive: settings.value.check_24h_consecutive ? 1 : 0,   
      compact_schedule_view: settings.value.compact_schedule_view ? 1 : 0    
    }
    await api.post('?route=admin/system_settings', payload)
    Swal.fire('สำเร็จ', 'อัปเดตการตั้งค่าระบบเรียบร้อยแล้ว', 'success')
  } catch (err) { 
    // 🌟 ใส่แจ้งเตือนเมื่อเกิด Error
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  } finally { 
    loading.value = false 
  }
}
// ในส่วน <script setup>
const updateSingleSetting = async (key, value) => {
  try {
    // แสดง Loading ขนาดเล็กที่มุมจอ (Optional)
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true
    });

    // ส่งข้อมูลไปบันทึกที่ Backend
    await api.post('?route=admin/settings/update_toggle', {
      setting_key: key,
      setting_value: value ? 1 : 0
    });

    Toast.fire({
      icon: 'success',
      title: 'บันทึกการตั้งค่าแล้ว'
    });
  } catch (error) {
    console.error(error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
    // หากบันทึกไม่สำเร็จ ให้คืนค่าสวิตช์กลับเป็นค่าเดิม (Rollback UI)
    settings.value[key] = !value;
  }
};
const updateSingleSetting2 = async (key, value) => {
  try {
    // แสดง Loading ขนาดเล็กที่มุมจอ (Optional)
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true
    });

    // ส่งข้อมูลไปบันทึกที่ Backend
    await api.post('?route=admin/settings/update_toggle', {
      setting_key: key,
      setting_value: value 
    });

    Toast.fire({
      icon: 'success',
      title: 'บันทึกการตั้งค่าแล้ว'
    });
  } catch (error) {
    console.error(error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
    // หากบันทึกไม่สำเร็จ ให้คืนค่าสวิตช์กลับเป็นค่าเดิม (Rollback UI)
    settings.value[key] = !value;
  }
};

// ในส่วน <script setup> ของหน้าตั้งค่า
const updateSystemName = async () => {
  try {
    const name = settings.value.system_name
    
    // 1. บันทึกลงฐานข้อมูล
    await api.post('?route=admin/settings/update_toggle', {
      setting_key: 'system_name',
      setting_value: name
    })

    // 2. เปลี่ยนชื่อหัวเว็บทันที
    document.title = name

    // 3. แจ้งเตือนสำเร็จ
    Swal.fire({
      icon: 'success',
      title: 'เปลี่ยนชื่อระบบแล้ว',
      toast: true,
      position: 'top-end',
      timer: 2000,
      showConfirmButton: false
    })

    // 💡 ทริค: หากต้องการให้ Navbar เปลี่ยนทันทีโดยไม่ต้อง Refresh 
    // แนะนำให้ใช้หน้าต่างแจ้งเตือนบอกผู้ใช้ว่า "กรุณารีเฟรชหน้าจอเพื่อเห็นความเปลี่ยนแปลง" 
    // หรือใช้ Pinia/Global State ในการคุมชื่อระบบครับ
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกชื่อระบบได้', 'error')
  }
}

const downloadImageBackup = async () => {
  try {
    // แสดงหน้าโหลดรอ
    Swal.fire({
      title: 'กำลังบีบอัดไฟล์รูปภาพ...',
      text: 'กรุณารอสักครู่ ระบบกำลังรวบรวมไฟล์รูปโปรไฟล์ทั้งหมด',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading()
      }
    });

    // 🌟 ส่ง Request ไปขอไฟล์ (เปลี่ยน URL เป็น route สำหรับรูปภาพ)
    const response = await api.get('?route=admin/backup/images', { 
      responseType: 'blob' 
    });

    // สร้างลิงก์ดาวน์โหลด
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    // 🌟 ตั้งชื่อไฟล์ให้รู้ว่าเป็นรูปภาพ
    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    link.setAttribute('download', `images_backup_${dateStr}.zip`);
    
    document.body.appendChild(link);
    link.click();
    link.remove();

    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: 'ดาวน์โหลดไฟล์รูปภาพเรียบร้อยแล้ว', timer: 2000, showConfirmButton: false });
  } catch (error) {
    console.error("Backup Error:", error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถสำรองไฟล์รูปภาพได้', 'error');
  }
}

const downloadSqlBackup = async () => {
  try {
    Swal.fire({
      title: 'กำลังดึงข้อมูลฐานข้อมูล...',
      text: 'กรุณารอสักครู่',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading()
      }
    });

    // เรียก API ไปที่ route ใหม่
    const response = await api.get('?route=admin/backup/sql', { 
      responseType: 'blob' 
    });

    // สร้างลิงก์ดาวน์โหลด
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    
    // ตั้งชื่อไฟล์เริ่มต้นเป็น .sql
    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    link.setAttribute('download', `database_${dateStr}.sql`);
    
    document.body.appendChild(link);
    link.click();
    link.remove();

    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: 'ดาวน์โหลดฐานข้อมูลเรียบร้อยแล้ว', timer: 2000, showConfirmButton: false });
  } catch (error) {
    console.error("SQL Backup Error:", error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถสำรองฐานข้อมูลได้', 'error');
  }
}

onMounted(fetchSettings)
</script>