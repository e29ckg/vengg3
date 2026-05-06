<template>
  <div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="fw-bold text-primary mb-0"><i class="bi bi-clock-history"></i> ประวัติการเปลี่ยนเวรของฉัน</h4>
      
      <div class="input-group shadow-sm" style="max-width: 300px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
        <input type="text" class="form-control border-start-0 ps-0" v-model="searchQuery" placeholder="ค้นหาเลขที่เอกสาร, ชื่อ...">
      </div>
    </div>

    <div class="card shadow-sm border-0">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>เลขที่เอกสาร</th>
                <th>วันที่ทำรายการ</th>
                <th>รายละเอียดเวรที่โอน</th>
                <th>ผู้โอน (ฉัน)</th>
                <th>ผู้รับโอน (ผู้แทน)</th>
                <th>สถานะ</th>
                <th class="text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="history in filteredHistory" :key="history.id">
                <td class="fw-bold text-primary">{{ history.change_no || '-' }}</td>
                <td>{{ formatDate(history.created_at) }}</td>
                <td>
                  <div class="fw-bold text-dark"><i class="bi bi-calendar-event"></i> {{ formatDate(history.ven_date) }}</div>
                  <small class="text-muted">{{ history.duty_main }} - {{ history.duty_role }}</small>
                </td>
                <td :class="{'text-danger fw-bold': history.user1_id == currentUserId}">
                  {{ history.user1_name }}
                </td>
                <td :class="{'text-success fw-bold': history.user2_id == currentUserId}">
                  {{ history.user2_name }}
                </td>
                <td>
                  <span v-if="history.status == 0" class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> รออนุมัติ</span>
                  <span v-else-if="history.status == 1" class="badge bg-success"><i class="bi bi-check-circle"></i> อนุมัติแล้ว</span>
                  <span v-else class="badge bg-danger"><i class="bi bi-x-circle"></i> ไม่อนุมัติ</span>
                </td>
                <td class="text-center">
                  <div class="d-flex justify-content-center gap-1">
                    <button class="btn btn-sm btn-outline-primary" @click="downloadWord(history)" title="พิมพ์ใบเปลี่ยนเวร">
                      <i class="bi bi-printer"></i> พิมพ์ 
                    </button>
                    
                    <button v-if="history.status == 0 && history.user1_id == currentUserId" 
                            class="btn btn-sm btn-outline-danger" 
                            @click="cancelRequest(history.id, history.s1_id)" 
                            title="ยกเลิกคำขอ">
                      <i class="bi bi-trash"></i>
                    </button>
                    
                  </div>
                </td>
              </tr>
              <tr v-if="filteredHistory.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                  ไม่มีประวัติการเปลี่ยนเวร
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

// 🌟 ฟังก์ชันดึงข้อมูล User อย่างปลอดภัย ป้องกันแอปพัง (JSON.parse Error)
const getCurrentUserId = () => {
  try {
    const userStr = localStorage.getItem('user_id');
    if (!userStr || userStr === 'undefined') return '';
    
    // พยายาม Parse เป็น JSON ก่อน
    const parsed = JSON.parse(userStr);
    return parsed || '';
  } catch (error) {
    // ถ้า Parse ไม่ได้ (อาจจะเก็บเป็น String ธรรมดา) ให้คืนค่าเป็นข้อความไปเลย
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
    (h.user2_name || '').toLowerCase().includes(q)
  )
})

const fetchHistory = async () => {
  try {
    const res = await api.get('?route=user/ven_change_history')
    historyList.value = res.data || []
  } catch (error) {
    console.error("Fetch history error:", error)
  }
}

const cancelRequest = async (changeId, scheduleId) => {
  const result = await Swal.fire({
    title: 'ยกเลิกคำขอ?',
    text: "เวรจะถูกดึงกลับมาเป็นชื่อของคุณเหมือนเดิม",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    confirmButtonText: 'ใช่, ยกเลิกเลย'
  })

  if (result.isConfirmed) {
    try {
      await api.post('?route=ven/cancel_change', { change_id: changeId, schedule_id: scheduleId })
      Swal.fire('สำเร็จ', 'ยกเลิกคำขอและดึงเวรกลับมาเรียบร้อย', 'success')
      fetchHistory()
    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถยกเลิกคำขอได้', 'error')
    }
  }
}

const agencyConfig = ref({
  agency_name: '',
  directors: [],
  admins: [],
  finances: []
})

// ฟังก์ชันช่วยหาผู้ลงนามที่เปิดใช้งาน (is_active)
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
    // แสดง Loading ระหว่างสร้างไฟล์ Word
    Swal.fire({
      title: 'กำลังสร้างเอกสาร...',
      text: 'กรุณารอสักครู่',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const director = getActiveSigner('directors');

    // 🌟 เนื่องจากข้อมูล venDetail ใน Word ต้องใช้ข้อมูลคำสั่งด้วย เราอาจจะต้องเตรียมออบเจ็กต์ให้ตรงกับที่ Service ต้องการ
    // ประยุกต์เอาข้อมูลจาก historyData มาจัดรูปแบบนิดหน่อย
    const venDetail = {
      agency_name: agencyConfig.value.agency_name, // 🌟 ส่งชื่อหน่วยงาน
      director_name: director.name,               // 🌟 ส่งชื่อผู้ลงนามอนุมัติ
      director_position: director.position,       // 🌟 ส่งตำแหน่งผู้ลงนาม
      command_num: historyData.com_num || "..........", 
      command_date: historyData.com_date || "-",
      ven_name: historyData.duty_main,
      duty_role: historyData.duty_role,
      ven_date: historyData.ven_date,
      ven_time: historyData.dn || historyData.duty_main, // ดึงกะเวลามาใส่
      duty_main: historyData.duty_main,
      duty_main_full: historyData.duty_main_full || historyData.duty_main
    };

    const changeDataObj = {
      change_no: historyData.change_no || historyData.id,
      change_date: historyData.created_at,
      user1_name: historyData.user1_name,
      user2_name: historyData.user2_name,
      user1_dep: historyData.user1_dep || "", // ถ้าคุณมีฟิลด์แผนก ให้ใส่ตรงนี้
      user2_dep: historyData.user2_dep || "",
      ref_change_no: historyData.ref_change_no ? `(อ้างถึงใบเปลี่ยนเวรเลขที่ ${historyData.ref_change_no})` : ""
    }

    // 🌟 เรียกใช้ฟังก์ชันที่ import มา
    await exportShiftChangeToWord(changeDataObj, venDetail);

    // ปิด Loading และแจ้งเตือนสำเร็จ
    Swal.fire({
      icon: 'success',
      title: 'ดาวน์โหลดสำเร็จ',
      text: 'ไฟล์ใบเปลี่ยนเวรถูกดาวน์โหลดลงเครื่องของคุณแล้ว',
      timer: 2000,
      showConfirmButton: false
    });

  } catch (error) {
    console.error("Word Error:", error);
    Swal.fire('ผิดพลาด', 'ไม่สามารถสร้างไฟล์ Word ได้ โปรดตรวจสอบไฟล์ Template', 'error');
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchHistory()
  fetchAgencyConfig()
})
</script>