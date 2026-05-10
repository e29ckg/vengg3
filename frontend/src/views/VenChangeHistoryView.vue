<template>
  <div class="container-fluid mt-4 pb-5">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h3 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>ประวัติการเปลี่ยนเวร</h3>
        <p class="text-muted mb-0 small">ติดตามสถานะใบขอเปลี่ยนเวรและสลับเวรของคุณ</p>
      </div>
      
      <div class="input-group shadow-sm" style="max-width: 350px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
        <input type="text" class="form-control border-start-0 ps-0" v-model="searchQuery" placeholder="ค้นหาเลขที่, ชื่อ...">
        <button v-if="searchQuery" class="btn btn-outline-secondary border-start-0 border bg-white" @click="searchQuery = ''" title="ล้างการค้นหา">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
      
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 text-muted">กำลังโหลดประวัติ...</div>
      </div>

      <div v-else class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted">
              <tr>
                <th class="ps-4 fw-semibold" style="width: 15%">เอกสาร / ยื่นเมื่อ</th>
                <th class="fw-semibold text-center" style="width: 10%">ประเภท</th>
                <th class="fw-semibold" style="width: 25%">ผู้ขอ (ฉัน)</th>
                <th class="fw-semibold" style="width: 25%">ผู้รับ (ผู้มาแทน/สลับ)</th>
                <th class="text-center fw-semibold" style="width: 10%">สถานะ</th>
                <th class="text-center pe-4 fw-semibold" style="width: 15%">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="history in filteredHistory" :key="history.id" class="transition-all hover-bg">
                
                <td class="ps-4">
                  <div class="fw-bold text-primary fs-6">{{ history.change_no || 'ไม่มีเลขที่' }}</div>
                  <div class="small text-muted"><i class="bi bi-calendar-plus me-1"></i>{{ formatDate(history.created_at) }}</div>
                  <span class="badge bg-light text-dark border border-secondary-subtle mt-1 px-2 fw-normal">
                    {{ history.duty_main }} <span v-if="history.duty_role">({{ history.duty_role }})</span>
                  </span>
                </td>

                <td class="text-center">
                  <span v-if="history.is_swap == 1" class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-arrow-left-right me-1"></i> สลับเวร
                  </span>
                  <span v-else class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle rounded-pill px-3 py-1">
                    <i class="bi bi-arrow-right me-1"></i> ยกเวรให้
                  </span>
                </td>

                <td>
                  <div class="d-flex align-items-center mb-1">
                    <div class="avatar-circle bg-danger-subtle text-danger fw-bold me-2">
                      {{ history.user1_name.charAt(0) }}
                    </div>
                    <span :class="{'fw-bold text-danger': history.user1_id == currentUserId, 'fw-semibold text-dark': history.user1_id != currentUserId}">
                      {{ history.user1_name }}
                      <span v-if="history.user1_id == currentUserId" class="badge bg-danger ms-1" style="font-size: 0.6rem;">ฉัน</span>
                    </span>
                  </div>
                  <div class="small text-muted ms-4 ps-2">
                    <i class="bi bi-calendar-event me-1"></i>เวรวันที่: <strong class="text-dark">{{ formatFullDate(history.s1_date || history.ven_date) }}</strong>
                  </div>
                </td>

                <td>
                  <div class="d-flex align-items-center mb-1">
                    <div class="avatar-circle bg-success-subtle text-success fw-bold me-2">
                      {{ history.user2_name.charAt(0) }}
                    </div>
                    <span :class="{'fw-bold text-success': history.user2_id == currentUserId, 'fw-semibold text-dark': history.user2_id != currentUserId}">
                      {{ history.user2_name }}
                      <span v-if="history.user2_id == currentUserId" class="badge bg-success ms-1" style="font-size: 0.6rem;">ฉัน</span>
                    </span>
                  </div>
                  
                  <div class="small text-muted ms-4 ps-2" v-if="history.is_swap == 1">
                    <i class="bi bi-calendar-check me-1"></i>เวรที่นำมาสลับ: <strong class="text-dark">{{ formatFullDate(history.s2_date) }}</strong>
                  </div>
                  <div class="small text-muted ms-4 ps-2" v-else>
                    <i class="bi bi-check2-circle text-success me-1"></i>รับเวรแทนทั้งหมด
                  </div>
                </td>

                <td class="text-center">
                  <div v-if="history.status == 0" class="d-inline-flex align-items-center text-warning fw-bold bg-warning-subtle px-2 py-1 rounded">
                    <i class="bi bi-hourglass-split me-1"></i>รออนุมัติ
                  </div>
                  <div v-else-if="history.status == 1" class="d-inline-flex align-items-center text-success fw-bold bg-success-subtle px-2 py-1 rounded">
                    <i class="bi bi-check-circle-fill me-1"></i>อนุมัติแล้ว
                  </div>
                  <div v-else class="d-inline-flex align-items-center text-danger fw-bold bg-danger-subtle px-2 py-1 rounded">
                    <i class="bi bi-x-circle-fill me-1"></i>ไม่อนุมัติ
                  </div>
                </td>

                <td class="text-center pe-4">
                  <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button class="btn btn-sm btn-primary rounded-circle shadow-sm print-btn" 
                            @click="downloadWord(history)" 
                            title="พิมพ์ใบเปลี่ยนเวร">
                      <i class="bi bi-printer-fill"></i>
                    </button>
                    
                    <button v-if="history.status == 0 && history.user1_id == currentUserId" 
                            class="btn btn-sm btn-outline-danger rounded-circle shadow-sm del-btn" 
                            @click="cancelRequest(history.id, history.s1_id)" 
                            title="ยกเลิกคำขอ">
                      <i class="bi bi-trash3-fill"></i>
                    </button>
                  </div>
                </td>

              </tr>

              <tr v-if="filteredHistory.length === 0 && !isLoading">
                <td colspan="6" class="text-center py-5">
                  <div class="p-4 bg-light rounded-4 d-inline-block border border-light">
                    <i class="bi bi-inbox text-secondary opacity-25" style="font-size: 3rem;"></i>
                    <h6 class="mt-3 fw-bold text-dark mb-1">ไม่พบประวัติการเปลี่ยนเวร</h6>
                    <p class="text-muted small mb-0">{{ searchQuery ? 'ลองเปลี่ยนคำค้นหาใหม่' : 'คุณยังไม่เคยทำรายการเปลี่ยนหรือสลับเวร' }}</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'
import { exportShiftChangeToWord } from '../services/wordService'

const historyList = ref([])
const searchQuery = ref('')
const isLoading = ref(true)

// ฟังก์ชันดึงข้อมูล User ป้องกันแอปพัง
const getCurrentUserId = () => {
  try {
    const userStr = localStorage.getItem('user_id');
    if (!userStr || userStr === 'undefined') return '';
    const parsed = JSON.parse(userStr);
    return parsed || '';
  } catch (error) {
    return localStorage.getItem('user_id') || '';
  }
};

const currentUserId = ref(getCurrentUserId());

const filteredHistory = computed(() => {
  if (!searchQuery.value) return historyList.value
  const q = searchQuery.value.toLowerCase()
  return historyList.value.filter(h => 
    (h.change_no || '').toLowerCase().includes(q) ||
    (h.user1_name || '').toLowerCase().includes(q) ||
    (h.user2_name || '').toLowerCase().includes(q) ||
    (h.duty_main || '').toLowerCase().includes(q)
  )
})

const fetchHistory = async () => {
  isLoading.value = true
  try {
    const res = await api.get('?route=user/ven_change_history')
    historyList.value = res.data || []
  } catch (error) {
    console.error("Fetch history error:", error)
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดประวัติได้', 'error')
  } finally {
    isLoading.value = false
  }
}

const cancelRequest = async (changeId, scheduleId) => {
  const result = await Swal.fire({
    title: 'ยืนยันการยกเลิกคำขอ?',
    text: "หากยกเลิก ชื่อบนปฏิทินจะถูกดึงกลับมาเป็นของคุณดังเดิม",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'ใช่, ยกเลิกคำขอ',
    cancelButtonText: 'ปิดหน้าต่าง'
  })

  if (result.isConfirmed) {
    try {
      Swal.fire({ title: 'กำลังยกเลิก...', didOpen: () => Swal.showLoading() })
      await api.post('?route=ven/cancel_change', { change_id: changeId, schedule_id: scheduleId })
      Swal.fire('สำเร็จ', 'ยกเลิกคำขอและคืนค่าตารางเวรเรียบร้อย', 'success')
      fetchHistory()
    } catch (error) {
      Swal.fire('ผิดพลาด', error.response?.data?.error || 'ไม่สามารถยกเลิกคำขอได้', 'error')
    }
  }
}

// ----------------------------------------------------
// ระบบโหลดข้อมูลออกรายงาน (Export Word)
// ----------------------------------------------------
const agencyConfig = ref({ agency_name: '', directors: [], admins: [], finances: [] })

const getActiveSigner = (type) => {
  if (!agencyConfig.value[type] || agencyConfig.value[type].length === 0) {
    return { name: '.......................................', position: '..............................' }
  }
  return agencyConfig.value[type].find(s => s.is_active) || agencyConfig.value[type][0]
}

const fetchAgencyConfig = async () => {
  try {
    const res = await api.get('?route=admin/agency_settings')
    if (res.data) agencyConfig.value = res.data
  } catch (error) {
    console.error("Error fetching agency config:", error)
  }
}

const downloadWord = async (historyData) => {
  try {
    Swal.fire({
      title: 'กำลังเตรียมเอกสาร...',
      text: 'ระบบกำลังดึงข้อมูลลงฟอร์ม',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const director = getActiveSigner('directors');

    const venDetail = {
      agency_name: agencyConfig.value.agency_name, 
      director_name: director.name,              
      director_position: director.position,      
      command_num: historyData.com_num || "..........", 
      command_date: historyData.com_date || "-",
      ven_name: historyData.duty_main,
      ven_name_full: historyData.duty_main_full,
      duty_role: historyData.duty_role,
      ven_date: historyData.ven_date,
      
      ven_time: historyData.dn || historyData.duty_main,
      duty_main: historyData.duty_main,
      duty_main_full: historyData.duty_main_full || historyData.duty_main

    };

    const changeDataObj = {
      change_no: historyData.change_no || historyData.id,
      change_date: historyData.created_at,
      user1_name: historyData.user1_name,
      user2_name: historyData.user2_name,
      user1_dep: historyData.user1_dep || "", 
      user2_dep: historyData.user2_dep || "",
      s1_date: historyData.s1_date,
      s2_date: historyData.s2_date,
      is_swap: historyData.is_swap,
      
    }

    await exportShiftChangeToWord(changeDataObj, venDetail);

    Swal.fire({
      icon: 'success',
      title: 'ดาวน์โหลดสำเร็จ',
      text: 'เปิดไฟล์เพื่อตรวจสอบและสั่งพิมพ์ได้เลย',
      timer: 2000,
      showConfirmButton: false
    });

  } catch (error) {
    console.error("Word Error:", error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถสร้างไฟล์ Word ได้ โปรดตรวจสอบว่ามีไฟล์ Template ในระบบหรือไม่', 'error');
  }
}

// ----------------------------------------------------
// Utils
// ----------------------------------------------------
const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatFullDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })
}

onMounted(() => {
  fetchHistory()
  fetchAgencyConfig()
})
</script>

<style scoped>
.hover-bg:hover {
  background-color: #f8f9fa;
}
.transition-all {
  transition: background-color 0.2s ease-in-out;
}
.avatar-circle {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
}
.print-btn {
  width: 35px; height: 35px;
  display: flex; align-items: center; justify-content: center;
  transition: transform 0.2s;
}
.print-btn:hover { transform: scale(1.1); }
.del-btn {
  width: 35px; height: 35px;
  display: flex; align-items: center; justify-content: center;
  transition: transform 0.2s;
}
.del-btn:hover { transform: scale(1.1); background-color: #dc3545; color: white; }
</style>