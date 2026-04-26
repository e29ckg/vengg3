<template>
  <div class="bg-light min-vh-100 pb-5">
    <nav class="navbar navbar-dark bg-info shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-5"><i class="bi bi-arrow-left-right me-2"></i>ระบบแลกเปลี่ยนเวร</a>
        
        <div class="d-flex align-items-center bg-white rounded-pill px-3 py-1 shadow-sm">
          <i class="bi bi-person-circle text-info me-2 fs-5"></i>
          <select class="form-select form-select-sm border-0 bg-transparent fw-bold text-dark" v-model="currentUserId" @change="fetchMyData">
            <option v-for="u in allUsers" :key="u.id" :value="u.id">{{ u.full_name }}</option>
          </select>
        </div>
      </div>
    </nav>

    <div class="container py-4">
      <div class="row g-4">
        
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event me-2"></i>เวรของฉัน (ที่สามารถแลกได้)</h5>
              <p class="small text-muted mt-1">เฉพาะเวรที่สถานะคำสั่งถูกยืนยันแล้ว</p>
            </div>
            <div class="card-body p-4 pt-2">
              <div v-if="mySchedules.length === 0" class="text-center text-muted py-5">
                <i class="bi bi-emoji-smile fs-1 d-block mb-3"></i> ไม่มีเวรในระบบ หรือเวรยังไม่ถูกยืนยัน
              </div>
              
              <div v-for="sch in mySchedules" :key="sch.id" class="p-3 mb-3 border rounded-3 bg-white shadow-sm d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="fw-bold text-primary mb-1">{{ formatDateThai(sch.date) }}</h6>
                  <span class="badge" :style="{ backgroundColor: sch.color }">{{ sch.sub_name }}</span>
                  <div class="small text-muted mt-1">คำสั่งที่: {{ sch.com_num }}</div>
                </div>
                <button class="btn btn-outline-info btn-sm rounded-pill px-3 fw-bold shadow-sm" @click="openSwapModal(sch)">
                  <i class="bi bi-arrow-repeat me-1"></i>ขอแลกเวร
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card border-0 shadow-sm rounded-4 h-100 bg-info bg-opacity-10">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
              <h5 class="fw-bold text-dark mb-0"><i class="bi bi-envelope-paper me-2"></i>กล่องคำขอแลกเวร</h5>
            </div>
            <div class="card-body p-4 pt-2 text-center text-muted">
               <br><br>
               <i class="bi bi-inbox fs-1 mb-2 d-block"></i>
               (ส่วนแสดงสถานะคำขอ จะพัฒนาต่อในสเตปถัดไป<br>ตอนนี้ทดสอบการกดปุ่มขอแลกเวรฝั่งซ้ายก่อนได้เลยครับ)
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="modal fade" id="swapModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
          <div class="modal-header bg-primary text-white border-0">
            <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>เลือกเวรที่ต้องการสลับ</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeSwapModal"></button>
          </div>
          <div class="modal-body p-4 bg-light">
            <div class="alert alert-info border-0 shadow-sm">
              <span class="fw-bold">เวรของคุณ:</span> {{ selectedMySch?.sub_name }} (วันที่ {{ formatDateThai(selectedMySch?.date) }})
            </div>
            
            <h6 class="fw-bold mt-4 mb-3">เวรของคนอื่นในคำสั่งเดียวกัน:</h6>
            <div class="row g-2">
              <div class="col-md-6" v-for="target in availableTargets" :key="target.id">
                <div class="card border-0 shadow-sm">
                  <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-bold text-dark">{{ target.user_name }}</div>
                      <div class="small text-muted">{{ formatDateThai(target.date) }} <span class="badge bg-secondary ms-1">{{ target.sub_name }}</span></div>
                    </div>
                    <button class="btn btn-success btn-sm rounded-pill fw-bold" @click="sendSwapRequest(target)">
                      แลกคนนี้
                    </button>
                  </div>
                </div>
              </div>
              <div v-if="availableTargets.length === 0" class="text-center text-muted py-3">ไม่มีใครให้แลกในหน้าที่นี้เลยครับ</div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'

// ตัวแปร Mock Login
const allUsers = ref([])
const currentUserId = ref(null)

const mySchedules = ref([])
const availableTargets = ref([])
const selectedMySch = ref(null)
let swapModalInstance = null

// ดึงรายชื่อคนทั้งหมดเพื่อทำ Mock Login
const fetchUsers = async () => {
  const res = await api.get('?route=admin/user/list')
  allUsers.value = res.data
  if (allUsers.value.length > 0) {
    currentUserId.value = allUsers.value[0].id // ล็อกอินเป็นคนแรกอัตโนมัติ
    fetchMyData()
  }
}

// ดึงเวรของคนที่เลือกล็อกอินอยู่
const fetchMyData = async () => {
  if (!currentUserId.value) return
  const res = await api.get(`?route=user/swap&action=my_shifts&user_id=${currentUserId.value}`)
  mySchedules.value = res.data || []
}

// เปิด Modal ค้นหาเพื่อน
const openSwapModal = async (mySch) => {
  selectedMySch.value = mySch
  
  // ดึงเวรทั้งหมดในเดือนนั้น มากรองหาคนอื่นที่อยู่เวรประเภทเดียวกัน
  const [y, m] = mySch.date.split('-')
  const res = await api.get(`?route=admin/ven_schedule&action=list_month&month=${y}-${m}`)
  
  availableTargets.value = res.data.filter(s => 
    s.user_id !== currentUserId.value && // ไม่ใช่ตัวเอง
    s.com_id === mySch.com_id // ต้องอยู่ในคำสั่งเดียวกัน (ป้องกันการแลกข้ามประเภท)
  )
  
  swapModalInstance.show()
}

// ส่งคำขอแลกเวร
const sendSwapRequest = async (targetSch) => {
  const result = await Swal.fire({
    title: 'ส่งคำขอแลกเวร?',
    html: `คุณแน่ใจหรือไม่ที่จะขอแลกเวรกับ <b>${targetSch.user_name}</b> ?<br>ระบบจะส่งคำขอไปให้เขาพิจารณา`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'ส่งคำขอ',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      await api.post('?route=user/swap&action=request', {
        s1_id: selectedMySch.value.id,
        user1_id: currentUserId.value,
        s2_id: targetSch.id,
        user2_id: targetSch.user_id
      })
      document.getElementById('closeSwapModal').click()
      Swal.fire('ส่งคำขอสำเร็จ!', 'รอให้อีกฝ่ายกดยอมรับ ตารางจึงจะอัปเดตครับ', 'success')
    } catch (e) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถส่งคำขอได้', 'error')
    }
  }
}

// ฟอร์แมตวันที่ให้สวยงาม
const formatDateThai = (dateStr) => {
  const [y, m, d] = dateStr.split('-')
  const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.']
  return `${parseInt(d)} ${thaiMonths[parseInt(m)-1]} ${parseInt(y)+543}`
}

onMounted(() => {
  fetchUsers()
  swapModalInstance = new Modal(document.getElementById('swapModal'))
})
</script>