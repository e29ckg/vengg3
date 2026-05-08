<template>
  <div class="container-fluid mt-4 pb-5">
    <div class="d-flex align-items-center mb-4">
      <h4 class="fw-bold text-dark mb-0">
        <i class="bi bi-google text-danger me-2"></i>ตั้งค่าการเชื่อมต่อ Google Calendar
      </h4>
    </div>

    <div class="row g-4 justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
          <div class="card-header bg-white border-0 pt-4 px-4">
            <h5 class="fw-bold"><i class="bi bi-gear-wide-connected me-2 text-primary"></i>Configuration</h5>
          </div>
          <div class="card-body px-4 pb-4">
            
            <div class="mb-4">
              <label class="form-label fw-bold text-muted small">Service Account Email (ใช้สำหรับแชร์สิทธิ์ใน Google Calendar)</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope-at text-primary"></i></span>
                <input type="text" class="form-control bg-light fw-bold text-success" :value="config.google_service_account" readonly placeholder="ยังไม่ได้อัปโหลดไฟล์ Credentials">
              </div>
            </div>

            <div class="mb-4">
              <label class="form-label fw-bold text-dark">Google Calendar ID (รหัสปฏิทินที่ต้องการให้ข้อมูลไปปรากฏ)</label>
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-danger"></i></span>
                <input type="text" class="form-control border-start-0" v-model="config.google_calendar_id" placeholder="เช่น xxxx@group.calendar.google.com">
              </div>
              <div class="form-text mt-2 small">
                <i class="bi bi-info-circle me-1"></i> นำรหัสปฏิทินจากหน้าตั้งค่าของ Google Calendar (Integrate Calendar) มาใส่ที่นี่
              </div>
            </div>

            <div class="d-grid gap-2 d-md-flex justify-content-md-end mb-4">
              <button class="btn btn-primary px-4 fw-bold shadow-sm" @click="saveConfig">
                <i class="bi bi-save me-2"></i>บันทึกการตั้งค่าปฏิทิน
              </button>
            </div>

            <hr class="text-muted opacity-25 mb-4">

            <div class="bg-light p-3 rounded-3 border">
              <label class="form-label fw-bold text-dark small"><i class="bi bi-file-earmark-code me-1"></i>อัปโหลดไฟล์ credentials.json ใหม่</label>
              <div class="input-group input-group-sm">
                <input type="file" class="form-control" accept=".json" @change="handleFileChange" ref="fileInput">
                <button class="btn btn-outline-dark fw-bold" @click="uploadCredentials" :disabled="!selectedFile">อัปโหลดไฟล์</button>
              </div>
            </div>

          </div>
        </div>
        
        <div class="card border-0 shadow-sm rounded-4 mt-4 bg-dark text-white">
          <div class="card-body p-4 small opacity-75">
            <h6 class="fw-bold mb-3 text-warning">
              <i class="bi bi-lightbulb-fill me-2"></i>ขั้นตอนการตั้งค่าและเชื่อมต่อ Google Calendar:
            </h6>
            <ol class="mb-0" style="line-height: 1.8;">
              <li><strong>เปิดใช้งาน API:</strong> ไปที่ <a href="https://console.cloud.google.com/" target="_blank" class="text-info fw-bold">Google Cloud Console</a> &gt; สร้าง Project &gt; ค้นหาและกด Enable <strong>Google Calendar API</strong></li>
              <li><strong>สร้างผู้ใช้งานจำลอง:</strong> ไปที่เมนู IAM & Admin &gt; Service Accounts &gt; กด Create Service Account</li>
              <li><strong>ดาวน์โหลดไฟล์ Key:</strong> คลิกที่ Service Account ที่เพิ่งสร้าง &gt; ไปที่แถบ <strong>Keys</strong> &gt; Add Key &gt; Create new key &gt; เลือกฟอร์แมต <strong>JSON</strong> (ไฟล์จะถูกดาวน์โหลดลงเครื่อง)</li>
              <li><strong>อัปโหลดเข้าสู่ระบบ:</strong> นำไฟล์ JSON ที่ดาวน์โหลดมา อัปโหลดในช่องด้านบน เพื่อให้ระบบอ่านค่า <strong>Service Account Email</strong></li>
              <li><strong>แชร์ปฏิทิน:</strong> ก๊อปปี้ Email ที่ได้ ไป <strong>"เพิ่มบุคคล" (Share)</strong> ในหน้าตั้งค่าของ Google Calendar และปรับสิทธิ์เป็น <strong>"Make changes to events"</strong></li>
              <li><strong>เชื่อมต่อ:</strong> ก๊อปปี้ <strong>Calendar ID</strong> จาก Google มาใส่ในช่องด้านบนแล้วกด "บันทึกการตั้งค่าปฏิทิน"</li>
            </ol>
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

const config = ref({
  google_service_account: '',
  google_calendar_id: ''
})
const selectedFile = ref(null)
const fileInput = ref(null)

const fetchConfig = async () => {
  try {
    const res = await api.get('?route=admin/google_settings&action=get_google_config')
    config.value = res.data
  } catch (error) { console.error(error) }
}

const saveConfig = async () => {
  try {
    await api.post('?route=admin/google_settings&action=update_google_config', config.value)
    Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false })
  } catch (error) { Swal.fire('ผิดพลาด', 'บันทึกไม่สำเร็จ', 'error') }
}

const handleFileChange = (e) => { selectedFile.value = e.target.files[0] }

const uploadCredentials = async () => {
  const formData = new FormData();
  formData.append('credential_file', selectedFile.value);
  try {
    const res = await api.post('?route=admin/google_settings&action=upload_credentials', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    if (res.data.success) {
      config.value.google_service_account = res.data.client_email;
      selectedFile.value = null;
      if (fileInput.value) fileInput.value.value = '';
      Swal.fire('สำเร็จ', 'อัปโหลด Credentials เรียบร้อย', 'success');
    }
  } catch (error) { Swal.fire('ผิดพลาด', 'อัปโหลดไม่สำเร็จ', 'error'); }
}

onMounted(fetchConfig)
</script>