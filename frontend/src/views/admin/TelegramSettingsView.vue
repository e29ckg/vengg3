<template>
  <div class="container mt-4 mb-5">
    <div class="row justify-content-center">
      <div class="col-md-10 col-lg-8 col-xl-7">
        
        <div class="text-center mb-4">
          <h2 class="fw-bold text-dark"><i class="bi bi-telegram text-primary me-2"></i>ตั้งค่า Telegram Bot</h2>
          <p class="text-muted">เชื่อมต่อบอทและตั้งค่าการแจ้งเตือนตารางเวรเข้ากลุ่มอัตโนมัติ</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
          <div class="card-body p-4 p-md-5">
            <form @submit.prevent="saveSettings">

              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-1-circle-fill text-primary me-2"></i>ข้อมูลการเชื่อมต่อ (API & Chat)</h6>
              <div class="mb-5 ms-4">
                
                <div class="mb-4">
                  <label class="form-label fw-bold text-muted small">Telegram Bot Token</label>
                  <div class="input-group shadow-sm rounded-3 overflow-hidden">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-robot text-primary"></i></span>
                    <input 
                      :type="showToken ? 'text' : 'password'" 
                      class="form-control border-start-0 border-end-0 bg-light fw-bold" 
                      v-model="settings.bot_token" 
                      placeholder="เช่น 123456:ABC-DEF..."
                      required
                    >
                    <button class="btn btn-light border border-start-0 text-secondary" type="button" @click="showToken = !showToken" tabindex="-1">
                      <i class="bi" :class="showToken ? 'bi-eye-slash text-danger' : 'bi-eye'"></i>
                    </button>
                  </div>
                  <div class="form-text mt-2 small">ได้จาก @BotFather ในแอป Telegram</div>
                </div>

                <div class="mb-2">
                  <label class="form-label fw-bold text-muted small">Group Chat ID</label>
                  <div class="input-group shadow-sm rounded-3 overflow-hidden">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-chat-quote text-success"></i></span>
                    <input 
                      type="text" 
                      class="form-control border-start-0" 
                      v-model="settings.chat_id" 
                      placeholder="เช่น -100123456789"
                      required
                    >
                  </div>
                  <div class="form-text mt-2 small">ID ของกลุ่มที่จะให้บอทส่งข้อความไปหา (มักจะมีเครื่องหมาย - นำหน้า)</div>
                </div>
              </div>

              <h6 class="fw-bold text-dark mb-3"><i class="bi bi-2-circle-fill text-primary me-2"></i>เหตุการณ์ที่ต้องการแจ้งเตือน (Auto Save)</h6>
              <div class="mb-5 ms-4">
                <div class="bg-primary-subtle p-4 rounded-4 border-primary border border-opacity-25">
                  <div class="form-check form-switch mb-3 fs-6">
                    <input class="form-check-input cursor-pointer shadow-sm" type="checkbox" id="notifyConfirmed" v-model="settings.notify_confirmed" @change="autoSaveSettings">
                    <label class="form-check-label fw-semibold text-dark" for="notifyConfirmed">แจ้งเมื่อ "ยืนยันตารางเวร" ประจำเดือน</label>
                  </div>
                  <div class="form-check form-switch mb-3 fs-6">
                    <input class="form-check-input cursor-pointer shadow-sm" type="checkbox" id="notifyReq" v-model="settings.notify_change_request" @change="autoSaveSettings">
                    <label class="form-check-label fw-semibold text-dark" for="notifyReq">แจ้งเมื่อมีการ "ส่งคำขอเปลี่ยน/แลกเวร"</label>
                  </div>
                  <div class="form-check form-switch fs-6 mb-0">
                    <input class="form-check-input cursor-pointer shadow-sm" type="checkbox" id="notifyApprove" v-model="settings.notify_approval" @change="autoSaveSettings">
                    <label class="form-check-label fw-semibold text-dark" for="notifyApprove">แจ้งเมื่อคำขอเปลี่ยนเวร "ได้รับการอนุมัติ"</label>
                  </div>
                </div>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-3-circle-fill text-primary me-2"></i>ตั้งเวลาแจ้งเตือนเวรประจำวัน</h6>
                <button type="button" class="btn btn-sm btn-success shadow-sm rounded-pill px-3 fw-bold" @click="addTimeSlot">
                  <i class="bi bi-plus-lg me-1"></i> เพิ่มเวลา
                </button>
              </div>
              <div class="mb-5 ms-4">
                
                <div v-if="notifyTimes.length === 0" class="text-center p-4 bg-light rounded-4 border border-dashed text-muted small">
                  ยังไม่มีการตั้งเวลาแจ้งเตือนประจำวัน
                </div>

                <div v-for="(item, index) in notifyTimes" :key="index" class="card mb-2 bg-light border-0 shadow-sm rounded-3">
                  <div class="card-body p-3">
                    <div class="row align-items-center g-2">
                      <div class="col-sm-4 col-md-3">
                        <input type="time" class="form-control fw-bold text-center text-primary" v-model="item.send_time" lang="th-TH" required>
                      </div>
                      <div class="col-sm-8 col-md-5">
                        <select class="form-select fw-semibold" v-model="item.notify_day">
                          <option :value="0">แจ้งรายชื่อเวร "วันนี้"</option>
                          <option :value="1">แจ้งรายชื่อเวร "วันพรุ่งนี้"</option>
                        </select>
                      </div>
                      <div class="col-auto ms-auto d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0" title="เปิด/ปิด เวลานี้">
                          <input class="form-check-input fs-5 cursor-pointer shadow-sm" type="checkbox" v-model="item.status">
                        </div>
                        <button type="button" class="btn btn-outline-danger border-0" @click="removeTimeSlot(index)" title="ลบรายการนี้">
                          <i class="bi bi-trash3-fill fs-5"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-light p-3 p-md-4 rounded-4 border mt-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                  
                  <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-warning fw-bold shadow-sm" @click="sendManualNotify(0)" :disabled="loading">
                      <i class="bi bi-bell-fill me-1"></i> ยิงแจ้งเตือนวันนี้
                    </button>
                    <button type="button" class="btn btn-info text-dark fw-bold shadow-sm" @click="sendManualNotify(1)" :disabled="loading">
                      <i class="bi bi-brightness-alt-high-fill me-1"></i> ยิงแจ้งเตือนพรุ่งนี้
                    </button>
                  </div>

                  <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-outline-secondary fw-bold" @click="testMessage" :disabled="loading" title="ส่งข้อความทดสอบเข้ากลุ่ม">
                      <i class="bi bi-send me-1"></i> ทดสอบ
                    </button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow" :disabled="loading">
                      <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-save2 me-2"></i> บันทึกการตั้งค่า
                    </button>
                  </div>

                </div>
              </div>

            </form>
          </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 bg-dark text-white overflow-hidden">
          <div class="card-header bg-black bg-opacity-25 border-0 py-3 px-4">
            <h6 class="fw-bold mb-0 text-info">
              <i class="bi bi-lightbulb-fill me-2 text-warning"></i>ขั้นตอนการสร้างบอทและตั้งค่า
            </h6>
          </div>
          <div class="card-body p-4 small opacity-75">
            <ol class="mb-0 ps-3" style="line-height: 1.9;">
              <li><strong>สร้างบอท:</strong> เปิดแอป Telegram ค้นหาบัญชี <strong>@BotFather</strong> แล้วพิมพ์คำสั่ง <code class="text-warning fw-bold">/newbot</code></li>
              <li><strong>ตั้งชื่อบอท:</strong> พิมพ์ชื่อบอทและ Username ที่ต้องการ (ต้องลงท้ายด้วยคำว่า bot) เมื่อสำเร็จจะได้รับ <strong>HTTP API Token</strong></li>
              <li><strong>ใส่ Token:</strong> ก๊อปปี้ Token ที่ได้มาวางในช่อง <span class="text-white">Telegram Bot Token</span> ใน (Step 1)</li>
              <li><strong>เชิญบอทเข้ากลุ่ม:</strong> สร้างกลุ่มใน Telegram ที่ต้องการให้แจ้งเตือน แล้วดึงบอทของคุณเข้าไปในกลุ่ม</li>
              <li><strong>หา Chat ID ของกลุ่ม:</strong> 
                <ul class="mb-1 mt-1 ps-4" style="list-style-type: circle;">
                  <li>พิมพ์ข้อความทักทายอะไรก็ได้ในกลุ่ม 1 ครั้ง</li>
                  <li>ดึงบอท <strong>@getidsbot</strong> เข้าไปในกลุ่ม บอทจะตอบข้อมูลกลับมา</li>
                  <li>ดูกระทู้ที่เขียนว่า <code>Chat ID:</code> แล้วก๊อปปี้ตัวเลขนั้นมา (มักจะมี <strong>เครื่องหมายลบ <code>-</code></strong> นำหน้า)</li>
                </ul>
              </li>
              <li><strong>ทดสอบ:</strong> นำ Chat ID มาใส่ใน (Step 1) กด <strong>บันทึก</strong> และกดปุ่ม <strong>"ทดสอบ"</strong> ด้านล่างเพื่อดูผลลัพธ์</li>
            </ol>
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