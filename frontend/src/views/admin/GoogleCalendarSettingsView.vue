<template>
  <div class="container-fluid mt-4 pb-5">
    <div class="d-flex align-items-center mb-4">
      <h4 class="fw-bold text-dark mb-0">
        <i class="bi bi-google text-danger me-2"></i>ตั้งค่า Google Calendar Integration
      </h4>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold"><i class="bi bi-key-fill me-2 text-warning"></i>API Credentials</h5>
          </div>
          <div class="card-body px-4">
            
            <div class="mb-4">
              <label class="form-label fw-bold text-muted small">Service Account Email ปัจจุบัน</label>
              <div class="form-control bg-light text-success fw-bold d-flex align-items-center" v-if="googleEmail">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <span class="text-truncate">{{ googleEmail }}</span>
              </div>
              <div class="form-control bg-light text-danger d-flex align-items-center" v-else>
                <i class="bi bi-exclamation-circle-fill me-2 fs-5"></i>ยังไม่ได้อัปโหลดไฟล์
              </div>
            </div>

            <hr class="text-muted opacity-25 mb-4">

            <div class="mb-3">
              <label class="form-label fw-bold text-dark">อัปโหลดไฟล์ credentials.json</label>
              <input type="file" class="form-control" accept=".json" @change="handleFileChange" ref="fileInput">
              <div class="form-text text-muted small mt-2">
                <i class="bi bi-info-circle me-1"></i>อัปโหลดไฟล์ Key (JSON) ที่ดาวน์โหลดจาก Google Cloud Console ระบบจะอ่านอีเมลและบันทึกไฟล์ให้ทันที
              </div>
            </div>

            <button class="btn btn-primary w-100 fw-bold mt-2 py-2" @click="uploadCredentials" :disabled="!selectedFile">
              <i class="bi bi-cloud-upload-fill me-2"></i>อัปโหลดไฟล์และอัปเดตสิทธิ์
            </button>

          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 h-100">
          <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2 text-primary"></i>จับคู่ประเภทเวรกับปฏิทิน</h5>
            <button class="btn btn-sm btn-outline-secondary" @click="fetchVenNames" title="รีเฟรชข้อมูล"> 
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
          <div class="card-body p-0">
            
            <div v-if="isLoading" class="text-center py-5 text-muted">
              <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
              <div>กำลังโหลดข้อมูล...</div>
            </div>

            <div class="table-responsive" v-else>{{ venNames }}
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted">
                  <tr>
                    <th class="ps-4" style="width: 35%;">ชื่อเวร</th>
                    <th style="width: 50%;">Google Calendar ID (รหัสปฏิทิน)</th>
                    <th class="text-center" style="width: 15%;">สถานะ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="ven in venNames" :key="ven.id">
                    <td class="ps-4">
                      <div class="fw-bold text-dark">{{ ven.name }}</div>
                      <small class="text-muted">อัตรา {{ ven.price }} บ.</small>
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm border bg-light" 
                             v-model="ven.google_calendar_id" 
                             placeholder="เว้นว่างไว้หากไม่ต้องการซิงค์"
                             @change="updateVenCalendarId(ven)">
                    </td>
                    <td class="text-center">
                      <i v-if="ven.google_calendar_id" class="bi bi-check-circle-fill text-success fs-5" title="เปิดใช้งานแล้ว"></i>
                      <i v-else class="bi bi-dash-circle text-secondary opacity-50 fs-5" title="ปิดใช้งาน"></i>
                    </td>
                  </tr>
                  <tr v-if="venNames.length === 0">
                    <td colspan="3" class="text-center py-4 text-muted">ไม่พบข้อมูลชื่อเวรในระบบ</td>
                  </tr>
                </tbody>
              </table>

            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card border-0 shadow-sm rounded-4 mt-4 bg-dark text-white">
      <div class="card-body p-4">
        <h6 class="fw-bold mb-3 text-warning"><i class="bi bi-lightbulb-fill me-2"></i>ขั้นตอนการตั้งค่าใช้งานครั้งแรก:</h6>
        <ol class="small mb-0 opacity-75" style="line-height: 1.8;">
          <li>สร้างโปรเจกต์ใน <a href="https://console.cloud.google.com/" target="_blank" class="text-info fw-bold">Google Cloud Console</a> และเปิดใช้ <strong>Google Calendar API</strong></li>
          <li>สร้าง <strong>Service Account</strong> และดาวน์โหลดคีย์แบบ <strong>JSON</strong> นำมาอัปโหลดในช่องด้านซ้าย</li>
          <li>ก๊อปปี้ <strong>Service Account Email ปัจจุบัน</strong> ที่ปรากฏด้านซ้าย ไปเพิ่มเป็นผู้ใช้งาน (Share) ในแต่ละปฏิทินของท่าน และกำหนดสิทธิ์ให้เป็น <strong>"Make changes to events"</strong></li>
          <li>นำรหัส <strong>Calendar ID</strong> (อยู่ในตั้งค่าปฏิทินของ Google) มาใส่ให้ตรงกับชื่อเวรในตารางด้านขวา ระบบจะบันทึกอัตโนมัติ</li>
        </ol>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import Swal from 'sweetalert2'

// --- State ---
const googleEmail = ref('')
const venNames = ref([])
const selectedFile = ref(null)
const fileInput = ref(null)
const isLoading = ref(true)

// --- API Calls: ดึงข้อมูลการตั้งค่า ---
const fetchSystemSettings = async () => {
  try {
    const res = await api.get('?route=admin/google_settings&action=get_google_config')
    googleEmail.value = res.data.google_service_account || ''
  } catch (error) { 
    console.error("Error fetching google settings:", error) 
  }
}

const fetchVenNames = async () => {
  isLoading.value = true
  try {
    const res = await api.get('?route=admin/setting&action=list_venname')
    venNames.value = res.data.data || []
  } catch (error) { 
    console.error("Error fetching ven names:", error) 
  } finally {
    isLoading.value = false
  }
}

// --- การจัดการอัปโหลดไฟล์ ---
const handleFileChange = (event) => {
  selectedFile.value = event.target.files[0]
}

const uploadCredentials = async () => {
  if (!selectedFile.value) return;

  const formData = new FormData();
  formData.append('credential_file', selectedFile.value);

  try {
    Swal.fire({
      title: 'กำลังอัปโหลด...',
      text: 'ระบบกำลังอ่านข้อมูลจากไฟล์',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const res = await api.post('?route=admin/google_settings&action=upload_credentials', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });

    if (res.data.success) {
      googleEmail.value = res.data.client_email; // อัปเดตอีเมลที่อ่านได้
      selectedFile.value = null; // ล้างค่าตัวแปรไฟล์
      if (fileInput.value) fileInput.value.value = ''; // ล้างช่อง input file
      
      Swal.fire('สำเร็จ', 'อัปโหลดไฟล์และอัปเดต Service Account เรียบร้อยแล้ว', 'success');
    }
  } catch (error) {
    console.error(error);
    Swal.fire('ผิดพลาด', error.response?.data?.error || 'ไม่สามารถอัปโหลดไฟล์ได้', 'error');
  }
}

// --- การอัปเดต Calendar ID ทันทีที่พิมพ์เสร็จ ---
const updateVenCalendarId = async (ven) => {
  try {
    await api.post('?route=admin/setting&action=update_calendar_id', {
      id: ven.id,
      google_calendar_id: ven.google_calendar_id
    })
    
    // แจ้งเตือนเล็กๆ มุมขวาบน (Toast) เพื่อไม่ให้รบกวนการทำงาน
    const Toast = Swal.mixin({ 
      toast: true, 
      position: 'top-end', 
      showConfirmButton: false, 
      timer: 2000 
    })
    Toast.fire({ 
      icon: 'success', 
      title: `บันทึกปฏิทินของเวร ${ven.name} แล้ว` 
    })
  } catch (error) { 
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึก Calendar ID ได้', 'error') 
    // รีเฟรชข้อมูลกลับคืนถ้าบันทึกไม่ผ่าน
    fetchVenNames(); 
  }
}

// --- เมื่อโหลดหน้าเสร็จ ---
onMounted(() => {
  fetchSystemSettings()
  fetchVenNames()
})
</script>

<style scoped>
.form-control:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}
</style>