<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="bi bi-telegram me-2"></i>ตั้งค่าการแจ้งเตือน Telegram</h5>
          </div>
          <div class="card-body p-4">
            <form @submit.prevent="saveSettings">
              
              <div class="mb-4">
                <label class="form-label fw-bold">Telegram Bot Token</label>
                <div class="input-group shadow-sm">
                  <input 
                    :type="showToken ? 'text' : 'password'" 
                    class="form-control border-end-0" 
                    v-model="settings.bot_token" 
                    placeholder="เช่น 123456:ABC-DEF..."
                    required
                  >
                  <button class="btn btn-outline-secondary bg-white border-start-0 border" type="button" @click="showToken = !showToken" tabindex="-1">
                    <i class="bi" :class="showToken ? 'bi-eye-slash text-danger' : 'bi-eye text-primary'"></i>
                  </button>
                </div>
                <div class="form-text text-muted">ได้จาก @BotFather ใน Telegram</div>
              </div>

              <div class="mb-4">
                <label class="form-label fw-bold">Group Chat ID</label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="settings.chat_id" 
                  placeholder="เช่น -100123456789"
                  required
                >
                <div class="form-text text-muted">ID ของกลุ่มที่จะให้บอทส่งข้อความไปหา</div>
              </div>

              <hr class="my-4">

              <h6 class="fw-bold mb-3">ประเภทการแจ้งเตือน (Auto Save)</h6>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" v-model="settings.notify_confirmed" @change="autoSaveSettings">
                <label class="form-check-label">แจ้งเมื่อ "ยืนยันตารางเวร" ประจำเดือน</label>
              </div>
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" v-model="settings.notify_change_request" @change="autoSaveSettings">
                <label class="form-check-label">แจ้งเมื่อมีการ "ส่งคำขอแลกเวร"</label>
              </div>
              <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" v-model="settings.notify_approval" @change="autoSaveSettings">
                <label class="form-check-label">แจ้งเมื่อคำขอแลกเวร "ได้รับการอนุมัติ"</label>
              </div>

              <hr class="my-4">

              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>กำหนดเวลาแจ้งเตือนเวรประจำวัน</h6>
                <button type="button" class="btn btn-sm btn-success shadow-sm" @click="addTimeSlot">
                    <i class="bi bi-plus-lg"></i> เพิ่มเวลา
                </button>
              </div>
        
              <div v-for="(item, index) in notifyTimes" :key="index" class="card mb-2 bg-light border-0">
                <div class="card-body p-2">
                    <div class="row align-items-center g-2">
                        <div class="col-md-3">
                            <input type="time" class="form-control form-control-sm" v-model="item.send_time" lang="th-TH" required>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" v-model="item.notify_day">
                                <option :value="0">แจ้งเวรของ "วันนี้"</option>
                                <option :value="1">แจ้งเวรของ "วันพรุ่งนี้"</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" v-model="item.status">
                            </div>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-sm btn-outline-danger" @click="removeTimeSlot(index)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
              </div>

              <hr class="my-4">

              <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mt-4">
                
                <div class="d-flex flex-column flex-sm-row gap-2">
                  <button type="button" class="btn btn-warning fw-bold shadow-sm" @click="sendManualNotify(0)" :disabled="loading">
                    <i class="bi bi-bell-fill me-1"></i> แจ้งเวรวันนี้
                  </button>
                  <button type="button" class="btn btn-info text-dark fw-bold shadow-sm" @click="sendManualNotify(1)" :disabled="loading">
                    <i class="bi bi-brightness-alt-high-fill me-1"></i> แจ้งเวรพรุ่งนี้
                  </button>
                </div>

                <div class="d-flex flex-column flex-sm-row gap-2">
                  <button type="button" class="btn btn-outline-secondary" @click="testMessage" :disabled="loading">
                    <i class="bi bi-send me-1"></i> ทดสอบส่งข้อความ
                  </button>
                  <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                    <i v-else class="bi bi-save me-1"></i> บันทึกการตั้งค่า
                  </button>
                </div>

              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api' // ตรวจสอบ path ให้ตรงกับโปรเจกต์ของคุณ
import Swal from 'sweetalert2'

const loading = ref(false)
const settings = ref({
  bot_token: '',
  chat_id: '',
  notify_confirmed: true,
  notify_change_request: true,
  notify_approval: true
})

const notifyTimes = ref([])
const showToken = ref(false)

// 🌟 ดึงข้อมูลการตั้งค่าเดิมจาก DB
const fetchSettings = async () => {
  try {
    const res = await api.get('?route=admin/telegram_settings')
    if (res.data) {
      settings.value = {
        bot_token: res.data.bot_token || '',
        chat_id: res.data.chat_id || '',
        notify_confirmed: res.data.notify_confirmed ?? true,
        notify_change_request: res.data.notify_change_request ?? true,
        notify_approval: res.data.notify_approval ?? true,
      }
      
      if (res.data.notify_times) {
        notifyTimes.value = res.data.notify_times.map(t => ({
          send_time: t.send_time.substring(0, 5),
          notify_day: parseInt(t.notify_day) || 0,
          status: t.status === 1 || t.status === true || t.status === "1"
        }))
      }
    }
  } catch (err) {
    console.error("Error fetching settings:", err)
  }
}

// 🌟 บันทึกการตั้งค่า (กดปุ่ม)
const saveSettings = async () => {
  loading.value = true
  try {
    await api.post('?route=admin/telegram_settings/update', {
      ...settings.value,
      notify_times: notifyTimes.value
    });
    Swal.fire('สำเร็จ', 'บันทึกการตั้งค่าเรียบร้อยแล้ว', 'success')
  } catch (err) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  } finally {
    loading.value = false
  }
}

// 🌟 ฟังก์ชันบันทึกอัตโนมัติ (ไม่แสดง Popup กวนใจ ใช้ Toast แทน)
const autoSaveSettings = async () => {
  try {
    await api.post('?route=admin/telegram_settings/update', {
      ...settings.value,
      notify_times: notifyTimes.value
    });
    
    // แสดงแจ้งเตือนเล็กๆ มุมขวาบน (Toast)
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true
    });
    Toast.fire({ icon: 'success', title: 'อัปเดตการตั้งค่าแล้ว' });
    
  } catch (err) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  }
}

// 🌟 ทดสอบส่งข้อความหา Telegram
const testMessage = async () => {
  loading.value = true
  try {
    await api.post('?route=admin/telegram_settings/test', settings.value)
    Swal.fire('สำเร็จ', 'ส่งข้อความทดสอบไปที่ Telegram แล้ว กรุณาตรวจสอบในกลุ่ม', 'success')
  } catch (err) {
    Swal.fire('ผิดพลาด', 'ส่งไม่สำเร็จ โปรดตรวจสอบ Token และ Chat ID', 'error')
  } finally {
    loading.value = false
  }
}

const addTimeSlot = () => {
  notifyTimes.value.push({ send_time: '18:00', notify_day: 0, status: true });
};

const removeTimeSlot = (index) => {
  notifyTimes.value.splice(index, 1);
};

// 🌟 ฟังก์ชันส่งแจ้งเตือนแบบ Manual (รองรับทั้งวันนี้ 0 และพรุ่งนี้ 1)
const sendManualNotify = async (dayOffset = 0) => {
  const dayText = dayOffset === 0 ? 'วันนี้' : 'วันพรุ่งนี้'
  
  const result = await Swal.fire({
    title: `ส่งแจ้งเตือนเวร${dayText}?`,
    text: `ระบบจะดึงรายชื่อเวรของ '${dayText}' และส่งเข้ากลุ่ม Telegram ทันที`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ใช่, ส่งเลย!',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    loading.value = true
    try {
      const res = await api.post('?route=admin/telegram_settings/manual_notify', { day_offset: dayOffset })
      Swal.fire('สำเร็จ', res.data.message, 'success')
    } catch (err) {
      const errorMsg = err.response?.data?.error || 'เกิดข้อผิดพลาดในการเชื่อมต่อ'
      Swal.fire('แจ้งเตือน', errorMsg, 'info')
    } finally {
      loading.value = false
    }
  }
}

onMounted(fetchSettings)
</script>

<style scoped>
/* ปรับแต่งความสวยงามเพิ่มเติมได้ที่นี่ */
</style>