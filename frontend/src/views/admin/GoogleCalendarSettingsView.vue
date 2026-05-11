<template>
  <div class="container mt-4 mb-5">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8 col-xl-7">
        
        <div class="text-center mb-4">
          <h2 class="fw-bold text-dark"><i class="bi bi-google text-danger me-2"></i>ตั้งค่า Google Calendar</h2>
          <p class="text-muted">จัดการการเชื่อมต่อตารางเวรกับปฏิทิน Google อัตโนมัติ</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4 p-md-5">

            <!-- <div class="bg-primary-subtle p-3 rounded-4 border-primary border border-opacity-25 mb-5 d-flex align-items-center justify-content-between">
              <div>
                <h6 class="fw-bold text-primary mb-1">สถานะการเชื่อมต่อปฏิทิน</h6>
                <small class="text-muted">เปิด/ปิด การซิงค์ข้อมูลขึ้น Google Calendar</small>
              </div>
              <div class="form-check form-switch fs-3 mb-0">
                <input class="form-check-input cursor-pointer shadow-sm" type="checkbox" role="switch" 
                       v-model="config.is_active" :true-value="1" :false-value="0" title="เปิด-ปิดการใช้งาน">
              </div>
            </div> -->

            <div :class="{'opacity-50 pe-none': config.is_active === 0}" style="transition: all 0.3s ease;">
              
              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-1-circle-fill text-danger me-2"></i>อัปโหลดไฟล์ Credentials</h6>
              <div class="mb-4 ms-4">
                <div class="input-group input-group-sm mb-2 shadow-sm rounded-3 overflow-hidden">
                  <input type="file" class="form-control py-2" accept=".json" @change="handleFileChange" ref="fileInput">
                  <button class="btn btn-dark fw-bold px-4" @click="uploadCredentials" :disabled="!selectedFile">
                    <i class="bi bi-cloud-upload me-2"></i>อัปโหลดไฟล์
                  </button>
                </div>
                <div class="form-text small">
                  อัปโหลดไฟล์ <code class="text-danger fw-bold">credentials.json</code> ที่ได้รับจาก Google Cloud Platform
                </div>
              </div>

              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-2-circle-fill text-danger me-2"></i>ข้อมูล Service Account</h6>
              <div class="mb-4 ms-4">
                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                  <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope-at text-secondary"></i></span>
                  <input type="text" class="form-control bg-light fw-bold text-success border-start-0 px-0" 
                         :value="config.google_service_account" readonly 
                         placeholder="ระบบจะดึงอีเมลอัตโนมัติเมื่ออัปโหลดไฟล์เสร็จ">
                  <button class="btn btn-outline-secondary bg-light border-start-0" 
                          @click="copyToClipboard(config.google_service_account)" 
                          :disabled="!config.google_service_account" title="คัดลอกอีเมล">
                    <i class="bi bi-clipboard"></i>
                  </button>
                </div>
                <div class="form-text mt-2 small">
                  <i class="bi bi-info-circle me-1"></i> นำ Email นี้ไปแชร์ (Share) ในเมนูตั้งค่าปฏิทินของ Google
                </div>
              </div>

              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-3-circle-fill text-danger me-2"></i>ระบุ Calendar ID</h6>
              <div class="mb-4 ms-4">
                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                  <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar3 text-primary"></i></span>
                  <input type="text" class="form-control border-start-0 px-0" 
                         v-model="config.google_calendar_id" 
                         placeholder="เช่น xxxx@group.calendar.google.com">
                </div>
              </div>

              <div class="d-grid mt-5">
                <button class="btn btn-primary btn-lg rounded-pill fw-bold shadow" @click="saveConfig">
                  <i class="bi bi-save2 me-2"></i>บันทึกการตั้งค่าระบบ
                </button>
              </div>

            </div> </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-dark text-white overflow-hidden">
          <div class="card-header bg-black bg-opacity-25 border-0 py-3 px-4">
            <h6 class="fw-bold mb-0 text-warning">
              <i class="bi bi-lightbulb-fill me-2"></i>ขั้นตอนการตั้งค่าฝั่ง Google
            </h6>
          </div>
          <div class="card-body p-4 small opacity-75">
            <ol class="mb-0 ps-3" style="line-height: 1.9;">
              <li><strong>เปิดใช้งาน API:</strong> ไปที่ <a href="https://console.cloud.google.com/" target="_blank" class="text-info fw-bold text-decoration-none">Google Cloud Console</a> &gt; สร้าง Project &gt; กด Enable <strong>Google Calendar API</strong></li>
              <li><strong>สร้างผู้ใช้งานจำลอง:</strong> ไปที่เมนู IAM & Admin &gt; Service Accounts &gt; กด Create Service Account</li>
              <li><strong>ดาวน์โหลดไฟล์ Key:</strong> คลิกที่ Service Account ที่เพิ่งสร้าง &gt; ไปที่แถบ <strong>Keys</strong> &gt; Add Key &gt; Create new key &gt; เลือกฟอร์แมต <strong>JSON</strong></li>
              <li><strong>อัปโหลดเข้าสู่ระบบ:</strong> นำไฟล์ JSON อัปโหลดในช่อง (Step 1) ด้านบน เพื่อให้ระบบอ่านค่า <strong>Service Account Email</strong></li>
              <li><strong>แชร์ปฏิทิน:</strong> ก๊อปปี้ Email ในข้อ 2 ไป <strong>"เพิ่มบุคคล" (Share)</strong> ในปฏิทิน Google และปรับสิทธิ์เป็น <strong>"Make changes to events"</strong></li>
              <li><strong>เชื่อมต่อ:</strong> ก๊อปปี้ <strong>Calendar ID</strong> จาก Google มาใส่ใน (Step 3) แล้วกดบันทึก</li>
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

// ฟังก์ชันช่วยคัดลอก Email ง่ายๆ
const copyToClipboard = (text) => {
  if (!text) return;
  navigator.clipboard.writeText(text);
  Swal.fire({
    icon: 'success',
    title: 'คัดลอกแล้ว!',
    text: 'นำไปวางในช่องแชร์ของ Google Calendar ได้เลย',
    timer: 1500,
    showConfirmButton: false
  });
};

onMounted(fetchConfig)
</script>