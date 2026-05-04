<template>
  <div class="bg-light min-vh-100">
    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="bi bi-file-text me-2"></i>จัดการคำสั่งเวร</h3>
        <button class="btn btn-success fw-bold px-4 shadow-sm rounded-pill" @click="openModal('add')">
          <i class="bi bi-plus-circle me-2"></i>เพิ่มคำสั่ง
        </button>
      </div>

      <div v-for="(commands, month) in groupedCommands" :key="month" class="mb-5">
        <h5 class="fw-bold text-secondary mb-3">เวรเดือน {{ formatMonthThai(month) }}</h5>
        
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <ul class="list-group list-group-flush">
            <li v-for="com in commands" :key="com.id" class="list-group-item d-flex justify-content-between align-items-center py-3 hover-bg">
              <div>
                <span class="fw-bold text-dark fs-6">เลขคำสั่งที่ {{ com.com_num }}</span>
                <span class="text-muted mx-2">|</span>
                <span class="text-muted">ลงวันที่ {{ formatDateThai(com.com_date) }}</span>
                <span class="text-muted mx-2">|</span>
                <span class="text-primary fw-semibold"><i class="bi bi-tag-fill me-1"></i>{{ com.ven_name_title }}</span>
              </div>
              
              <div class="d-flex align-items-center">
                <div class="form-check form-switch me-3 fs-5 mb-0" title="เปิด/ปิด การใช้งานคำสั่งนี้">
                  <input class="form-check-input custom-switch" type="checkbox" role="switch" 
                         :checked="com.status == 1" 
                         @change="toggleStatus(com.id, $event.target.checked ? 1 : 0)">
                </div>
                
                <button class="btn btn-sm btn-warning fw-bold text-dark rounded-3 px-3 me-2 shadow-sm" @click="openModal('edit', com)">
                  <i class="bi bi-pencil-square me-1"></i>แก้ไข
                </button>
                <button class="btn btn-sm btn-danger rounded-3 px-3 shadow-sm" @click="deleteCommand(com)">
                  <i class="bi bi-trash me-1"></i>ลบ
                </button>
              </div>
            </li>
            <li v-if="commands.length === 0" class="list-group-item text-center text-muted py-4">ไม่มีข้อมูลคำสั่ง</li>
          </ul>
        </div>
      </div>
      
      <div v-if="Object.keys(groupedCommands).length === 0" class="text-center text-muted mt-5 py-5">
        <div class="bg-white p-5 rounded-4 shadow-sm d-inline-block">
          <i class="bi bi-inbox text-secondary" style="font-size: 4rem;"></i>
          <h4 class="mt-3 fw-bold">ยังไม่มีข้อมูลคำสั่งในระบบ</h4>
          <p class="mb-0">คลิกที่ปุ่ม "เพิ่มคำสั่ง" ด้านบนเพื่อเริ่มต้นจัดเวร</p>
        </div>
      </div>
    </div>

    <div class="modal fade" id="commandModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
          <div class="modal-header bg-primary text-white border-0 px-4 pt-4 pb-3">
            <h5 class="modal-title fw-bold">
              <i class="bi" :class="isEditing ? 'bi-pencil-square' : 'bi-plus-circle'"></i> 
              {{ isEditing ? 'แก้ไขคำสั่งเวร' : 'สร้างคำสั่งเวรใหม่' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeComModal"></button>
          </div>
          
          <form @submit.prevent="submitCommand">
            <div class="modal-body p-4 bg-light">
              
              <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3 p-md-4">
                  <h6 class="fw-bold text-primary mb-3 border-bottom pb-2"><i class="bi bi-info-circle-fill me-2"></i>1. ข้อมูลคำสั่งพื้นฐาน</h6>
                  <div class="row g-3">
                    <div class="col-md-4">
                      <label class="form-label fw-semibold text-muted small">เลขคำสั่ง *</label>
                      <input type="text" class="form-control bg-light" v-model="form.com_num" required placeholder="เช่น 4/2569">
                    </div>
                    <div class="col-md-8">
                      <label class="form-label fw-semibold text-muted small">ชื่อเวร / กลุ่มหน้าที่ *</label>
                      <select class="form-select bg-light" v-model="form.ven_name_id" required>
                        <option value="" disabled>-- เลือกชื่อเวรที่ต้องการจัด --</option>
                        <option v-for="vn in venNames" :key="vn.id" :value="vn.id">{{ vn.name }}</option>
                      </select>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3 p-md-4">
                  <h6 class="fw-bold text-success mb-3 border-bottom pb-2"><i class="bi bi-calendar-check-fill me-2"></i>2. กำหนดเวลา</h6>
                  <div class="row g-4">
                    <div class="col-md-6">
                      <label class="form-label fw-semibold text-muted small">วันที่ออกคำสั่ง (ลงวันที่) *</label>
                      <div class="input-group shadow-sm rounded-3 overflow-hidden">
                        <select class="form-select border-0 text-center" v-model="selectedComDay" required>
                          <option value="" disabled>วัน</option>
                          <option v-for="d in 31" :key="d" :value="String(d).padStart(2, '0')">{{ d }}</option>
                        </select>
                        <select class="form-select border-0 border-start text-center" v-model="selectedComMonth" required>
                          <option value="" disabled>เดือน</option>
                          <option v-for="(m, index) in thaiMonthsList" :key="index" :value="String(index + 1).padStart(2, '0')">{{ m }}</option>
                        </select>
                        <select class="form-select border-0 border-start text-center bg-white" v-model="selectedComYear" required>
                          <option value="" disabled>ปี พ.ศ.</option>
                          <option v-for="y in yearList" :key="y" :value="y">{{ y }}</option>
                        </select>
                      </div>
                    </div>

                   <div class="col-md-6">
                      <label class="form-label fw-semibold text-muted small">ประจำเดือน (เวรเดือน) *</label>
                      <div class="input-group shadow-sm rounded-3 overflow-hidden">
                        <select 
                          class="form-select border-0 text-center" 
                          v-model="selectedMonth" 
                          :disabled="form.id" 
                          required
                        >
                          <option value="" disabled>เดือน</option>
                          <option v-for="(m, index) in thaiMonthsList" :key="index" :value="String(index + 1).padStart(2, '0')">{{ m }}</option>
                        </select>
                        
                        <select 
                          class="form-select border-0 border-start text-center" 
                          :class="{'bg-white': !form.id, 'bg-light': form.id}"
                          v-model="selectedYear" 
                          :disabled="form.id" 
                          required
                        >
                          <option value="" disabled>ปี พ.ศ.</option>
                          <option v-for="y in yearList" :key="y" :value="y">{{ y }}</option>
                        </select>
                      </div>
                      
                      <div v-if="form.id" class="form-text text-danger mt-1" style="font-size: 0.85rem;">
                        <i class="bi bi-lock-fill"></i> ไม่สามารถแก้ไขเดือน/ปีได้ เนื่องจากมีการจัดตารางเวรแล้ว
                      </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-3 mt-3">
                      <div class="card-body p-3 p-md-4">
                        <h6 class="fw-bold text-info mb-3 border-bottom pb-2">
                          <i class="bi bi-calendar-day-fill me-2"></i>3. ระบุวันที่จัดเวรในเดือนนี้
                        </h6>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                          <p class="small text-muted mb-0">คลิกเลือกวันที่คำสั่งนี้มีผลบังคับใช้</p>
                          <div class="btn-group btn-group-sm shadow-sm">
                            <button type="button" class="btn btn-outline-primary fw-bold" @click="selectAllDays">
                              <i class="bi bi-check-all me-1"></i>ทุกวัน
                            </button>
                            <button type="button" class="btn btn-outline-danger fw-bold" @click="clearAllDays">
                              <i class="bi bi-eraser-fill me-1"></i>ล้าง
                            </button>
                          </div>
                        </div>
                        
                        <div class="d-flex flex-wrap gap-2">
                          <button type="button" 
                                  v-for="d in daysInSelectedMonth" :key="d"
                                  class="btn rounded-circle shadow-sm fw-bold d-flex align-items-center justify-content-center"
                                  style="width: 42px; height: 42px; transition: 0.2s;"
                                  :class="form.ven_com_days.includes(d) ? 'btn-primary' : 'btn-outline-secondary'"
                                  @click="toggleComDay(d)">
                            {{ d }}
                          </button>
                        </div>
                        
                        <div v-if="form.ven_com_days.length > 0" class="mt-3 small text-success fw-bold">
                          <i class="bi bi-check-circle-fill me-1"></i>เลือกไว้แล้ว {{ form.ven_com_days.length }} วัน (วันที่: {{ form.ven_com_days.join(', ') }})
                        </div>
                      </div>
                    </div>


                  </div>
                </div>
              </div>

            </div>
            
            <div class="modal-footer border-0 pb-4 pe-4 bg-white">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                <i class="bi bi-save me-2"></i>บันทึกข้อมูล
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'

const commands = ref([])
const venNames = ref([])
let comModalInstance = null

const isEditing = ref(false)
const form = ref({ id: '', com_num: '', com_date: '', ven_month: '', ven_name_id: '', ven_com_days: [] })

// 🌟 ตัวแปรสำหรับ Dropdown เดือน/ปี ไทย
const selectedComDay = ref('')
const selectedComMonth = ref('')
const selectedComYear = ref('')

const selectedMonth = ref('')
const selectedYear = ref('')

const thaiMonthsList = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม']

// สร้างรายการ ปี พ.ศ. (ย้อนหลัง 2 ปี ถึง ล่วงหน้า 3 ปี)
const yearList = computed(() => {
  const currentYearBE = new Date().getFullYear() + 543
  let years = []
  for (let i = currentYearBE - 2; i <= currentYearBE + 3; i++) {
    years.push(i)
  }
  return years
})

// ดึงข้อมูลคำสั่งทั้งหมด
const fetchCommands = async () => {
  const res = await api.get('?route=admin/ven_com&action=list')
  commands.value = res.data || []
}

// ดึงรายชื่อเวรหลัก
const fetchVenNames = async () => {
  const res = await api.get('?route=admin/setting&action=list_venname')
      venNames.value = res.data.data || []
}

// จัดกลุ่มคำสั่งตามเดือน (ven_month)
const groupedCommands = computed(() => {
  const groups = {}
  commands.value.forEach(com => {
    const monthKey = com.ven_month // รูปแบบ YYYY-MM
    if (!groups[monthKey]) {
      groups[monthKey] = []
    }
    groups[monthKey].push(com)
  })
  return groups
})

// เปิด Modal
const openModal = (mode, com = null) => {
  if (mode === 'add') {
    isEditing.value = false
    form.value = { id: '', com_num: '', com_date: '', ven_month: '', ven_name_id: '', ven_com_days: [] } // 🌟 เพิ่ม ven_com_days
    
    // ตั้งค่าเริ่มต้นเป็น วัน/เดือน/ปี ปัจจุบัน
    const now = new Date()
    
    selectedComDay.value = String(now.getDate()).padStart(2, '0')
    selectedComMonth.value = String(now.getMonth() + 1).padStart(2, '0')
    selectedComYear.value = now.getFullYear() + 543

    selectedMonth.value = String(now.getMonth() + 1).padStart(2, '0')
    selectedYear.value = now.getFullYear() + 543

  } else {
    isEditing.value = true
    form.value = { ...com, ven_com_days: [] } // 🌟 ตั้งค่าเริ่มต้นป้องกัน Error

    if (com.ven_com_days) {
      form.value.ven_com_days = com.ven_com_days.split(',').map(Number)
    }
    
    // แยก YYYY-MM-DD ของ com_date ใส่ Dropdown
    if (com.com_date) {
      const [y, m, d] = com.com_date.split('-')
      selectedComDay.value = d
      selectedComMonth.value = m
      selectedComYear.value = parseInt(y) + 543
    }

    // แยก YYYY-MM ของ ven_month ใส่ Dropdown
    if (com.ven_month) {
      const [y, m] = com.ven_month.split('-')
      selectedMonth.value = m
      selectedYear.value = parseInt(y) + 543
    }
  }
  comModalInstance.show()
}

// บันทึกคำสั่ง
const submitCommand = async () => {
  try {
    // แปลง พ.ศ. กลับเป็น ค.ศ. (YYYY-MM-DD) ก่อนส่ง
    const comYAD = selectedComYear.value - 543
    form.value.com_date = `${comYAD}-${selectedComMonth.value}-${selectedComDay.value}`

    const yAD = selectedYear.value - 543
    form.value.ven_month = `${yAD}-${selectedMonth.value}`

    const payload = {
      ...form.value,
      ven_com_days: form.value.ven_com_days.join(',')
    }

    const action = isEditing.value ? 'update' : 'create'
    await api.post(`?route=admin/ven_com&action=${action}`, payload)
    
    document.getElementById('closeComModal').click()
    Swal.fire({ title: 'สำเร็จ', text: 'บันทึกคำสั่งเวรเรียบร้อยแล้ว', icon: 'success', timer: 1500, showConfirmButton: false })
    fetchCommands()
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  }
}

// เปลี่ยนสถานะคำสั่ง (เปิด/ปิด)
const toggleStatus = async (id, status) => {
  await api.post('?route=admin/ven_com&action=toggle_status', { id, status })
  const com = commands.value.find(c => c.id === id)
  if (com) com.status = status
}

// ลบคำสั่ง
// ฟังก์ชันสำหรับกดปุ่มลบ
const deleteCommand = async (com) => {
  // ข้อความที่บังคับให้แอดมินพิมพ์ (เช่น 'ลบคำสั่ง 123/2567')
  const expectedText = `ลบคำสั่ง ${com.com_num}`; 

  const { value: typedText } = await Swal.fire({
    title: '⚠️ ยืนยันการลบคำสั่งเวร',
    html: `
      <div class="text-start">
        <p class="text-danger fw-bold mb-2">คำเตือนอันตราย!</p>
        <p class="mb-3">การลบคำสั่งนี้จะทำการ <b class="text-danger">ลบรายชื่อเวรที่จัดไว้ทั้งหมด</b> ในคำสั่งนี้ด้วย และไม่สามารถกู้คืนได้!</p>
        <p class="mb-1">กรุณาพิมพ์ข้อความด้านล่างเพื่อยืนยัน:</p>
        <div class="p-2 bg-light border rounded text-center mb-2 user-select-none">
          <strong class="text-primary fs-5">${expectedText}</strong>
        </div>
      </div>
    `,
    input: 'text',
    inputPlaceholder: 'พิมพ์ข้อความยืนยันที่นี่...',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: '<i class="bi bi-trash"></i> ลบทิ้งถาวร',
    cancelButtonText: 'ยกเลิก',
    // 🌟 ตัวตรวจสอบว่าพิมพ์ตรงไหม
    inputValidator: (value) => {
      if (!value) {
        return 'กรุณาพิมพ์ข้อความเพื่อยืนยัน'
      }
      if (value !== expectedText) {
        return 'พิมพ์ข้อความไม่ถูกต้อง กรุณาลองใหม่'
      }
    }
  });

  // ถ้าพิมพ์ถูกต้องและกดตกลง
  if (typedText === expectedText) {
    try {
      // เรียก API ไปลบข้อมูล (ปรับ URL ให้ตรงกับโปรเจกต์ของคุณ)
      await api.delete(`?route=admin/ven_com/delete&id=${com.id}`);
      
      Swal.fire(
        'ลบสำเร็จ!',
        'คำสั่งและตารางเวรที่เกี่ยวข้องถูกลบเรียบร้อยแล้ว',
        'success'
      );
      
      // ดึงข้อมูลคำสั่งใหม่เพื่อรีเฟรชตาราง
      fetchCommands(); 
      
    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error');
    }
  }
}

// --- ฟังก์ชันช่วยเหลือ (Helpers) แปลงวันที่เป็นภาษาไทย ---
const formatMonthThai = (ymString) => {
  if (!ymString) return ''
  const [year, month] = ymString.split('-')
  return `${thaiMonthsList[parseInt(month) - 1]} ${parseInt(year) + 543}`
}

const formatDateThai = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return `${date.getDate()} ${thaiMonthsList[date.getMonth()]} ${date.getFullYear() + 543}`
}

// 🌟 คำนวณจำนวนวันในเดือนที่กำลังเลือก (เช่น กุมภา มี 28 หรือ 29 วัน)
const daysInSelectedMonth = computed(() => {
  if (!selectedMonth.value || !selectedYear.value) return 31
  const y = selectedYear.value - 543
  const m = parseInt(selectedMonth.value)
  return new Date(y, m, 0).getDate() // หาวันสุดท้ายของเดือนนั้น
})

// 🌟 ฟังก์ชันเมื่อคลิกปุ่มวันที่
const toggleComDay = (day) => {
  const index = form.value.ven_com_days.indexOf(day)
  if (index > -1) {
    form.value.ven_com_days.splice(index, 1) // ถ้ามีแล้วให้เอาออก (Toggle)
  } else {
    form.value.ven_com_days.push(day) // ถ้ายังไม่มีให้เพิ่มเข้าไป
  }
  // จัดเรียงตัวเลขจากน้อยไปมากให้สวยงาม
  form.value.ven_com_days.sort((a, b) => a - b)
}

// 🌟 ฟังก์ชันเลือกทุกวัน
const selectAllDays = () => {
  // สร้าง Array ของตัวเลขตั้งแต่ 1 ถึงจำนวนวันสุดท้ายของเดือน
  const allDays = []
  for (let i = 1; i <= daysInSelectedMonth.value; i++) {
    allDays.push(i)
  }
  form.value.ven_com_days = allDays
}

// 🌟 ฟังก์ชันล้างทั้งหมด
const clearAllDays = () => {
  form.value.ven_com_days = []
}

onMounted(() => {
  fetchCommands()
  fetchVenNames()
  comModalInstance = new Modal(document.getElementById('commandModal'))
})
</script>

<style scoped>
.hover-bg:hover {
  background-color: #f8f9fa;
  transition: 0.2s;
}

/* แต่งสี Switch ให้เด่นชัด */
.custom-switch:checked {
  background-color: #0d6efd;
  border-color: #0d6efd;
}
.custom-switch {
  width: 3rem;
  height: 1.5rem;
  cursor: pointer;
}
</style>