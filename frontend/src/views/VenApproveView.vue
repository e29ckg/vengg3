<template>
  <div class="container-fluid mt-4 pb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="fw-bold text-primary mb-0"><i class="bi bi-check-circle"></i> อนุมัติใบเปลี่ยน/สลับเวร</h4>
      
      <div class="input-group shadow-sm" style="max-width: 350px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
        <input type="text" class="form-control border-start-0 ps-0" v-model="searchQuery" placeholder="ค้นหาเลขที่, ชื่อ, หน้าที่...">
        <button v-if="searchQuery" class="btn btn-outline-secondary border-start-0 border" @click="searchQuery = ''" title="ล้างการค้นหา">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
      
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
        <div class="mt-2 text-muted">กำลังโหลดข้อมูล...</div>
      </div>

      <div v-else class="card-body p-0">
        
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3">เอกสาร / หน้าที่</th>
                <th class="text-center">ประเภท</th>
                <th>ผู้ขอ (เจ้าของเวรเดิม)</th>
                <th>ผู้รับ (ผู้มาแทน/สลับ)</th>
                <th class="text-center">สถานะ</th>
                <th class="text-center pe-3">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredRequests" :key="item.id">
                
                <td class="ps-3">
                  <div class="fw-bold text-primary">{{ item.change_no || 'ไม่มีเลขที่' }}</div>
                  <div class="small text-muted mb-1">ยื่นเมื่อ: {{ formatDateTime(item.created_at) }}</div>
                  <span class="badge bg-light text-dark border border-secondary">
                    {{ item.duty_main }} <span v-if="item.duty_role">({{ item.duty_role }})</span>
                  </span>
                </td>

                <td class="text-center">
                  <span v-if="item.is_swap == 1" class="badge bg-warning text-dark border border-warning rounded-pill px-3">
                    <i class="bi bi-arrow-left-right"></i> สลับเวร
                  </span>
                  <span v-else class="badge bg-info text-dark border border-info rounded-pill px-3">
                    <i class="bi bi-arrow-right"></i> ยกเวรให้
                  </span>
                </td>

                <td>
                  <div class="fw-bold text-danger"><i class="bi bi-person-dash"></i> {{ item.user1_name }}</div>
                  <div class="small text-muted mt-1">
                    <i class="bi bi-calendar-event"></i> เวรวันที่: <strong class="text-dark">{{ formatDate(item.s1_date || item.ven_date) }}</strong>
                  </div>
                </td>

                <td>
                  <div class="fw-bold text-success"><i class="bi bi-person-plus"></i> {{ item.user2_name }}</div>
                  <div class="small text-muted mt-1" v-if="item.is_swap == 1">
                    <i class="bi bi-calendar-check"></i> เวรที่นำมาสลับ: <strong class="text-dark">{{ formatDate(item.s2_date) }}</strong>
                  </div>
                  <div class="small text-muted mt-1" v-else>
                    <i class="bi bi-check2-circle"></i> รับเวรแทนทั้งหมด
                  </div>
                </td>

                <td class="text-center">
                  <span v-if="item.status == 0" class="badge bg-warning text-dark fs-6">รออนุมัติ</span>
                  <span v-else-if="item.status == 1" class="badge bg-success fs-6"><i class="bi bi-check-circle"></i> อนุมัติแล้ว</span>
                  <span v-else-if="item.status == 2" class="badge bg-danger fs-6" ><i class="bi bi-x-circle"></i> ไม่อนุมัติ</span>
                </td>

                <td class="text-center pe-3">
                  <div v-if="item.status == 0" class="d-flex flex-column gap-1">
                    <button class="btn btn-sm btn-success fw-bold shadow-sm" @click="handleApprove(item.id, 1, item.change_no)">
                      <i class="bi bi-check-lg"></i> อนุมัติ
                    </button>
                    <button disabled="" class="btn btn-sm btn-danger shadow-sm" @click="handleApprove(item.id, 2, item.change_no)">
                      <i class="bi bi-x-lg"></i> ไม่อนุมัติ
                    </button>
                  </div>
                  <div v-else>
                    <button class="btn btn-sm btn-outline-secondary" @click="handleEdit(item)" title="แก้ไขสถานะย้อนหลัง">
                      <i class="bi bi-pencil-square"></i> แก้ไข
                    </button>
                  </div>
                </td>

              </tr>
              
              <tr v-if="filteredRequests.length === 0">
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                  {{ searchQuery ? 'ไม่พบรายการที่ตรงกับการค้นหา' : 'ไม่มีรายการขอเปลี่ยนเวรที่รอตรวจสอบ' }}
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
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'

const changeRequests = ref([])
const searchQuery = ref('')
const isLoading = ref(true)

// Computed Property สำหรับกรองข้อมูลตามข้อความค้นหา
const filteredRequests = computed(() => {
  if (!searchQuery.value) return changeRequests.value

  const query = searchQuery.value.toLowerCase().trim()
  
  return changeRequests.value.filter(item => {
    const docNo = (item.change_no || '').toLowerCase()
    const dutyMain = (item.duty_main || '').toLowerCase()
    const dutyRole = (item.duty_role || '').toLowerCase()
    const user1 = (item.user1_name || '').toLowerCase()
    const user2 = (item.user2_name || '').toLowerCase()

    return docNo.includes(query) || 
           dutyMain.includes(query) || 
           dutyRole.includes(query) || 
           user1.includes(query) || 
           user2.includes(query)
  })
})

const fetchRequests = async () => {
  isLoading.value = true
  try {
    // 🌟 เรียก API ไปที่ Route ดึงรายการ
    const res = await api.get('?route=admin/ven_approve&action=list')
    changeRequests.value = res.data || []
  } catch (error) {
    console.error(error)
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error')
  } finally {
    isLoading.value = false
  }
}

const handleApprove = async (id, status, change_no) => {
  const actionText = status === 1 ? 'อนุมัติ' : 'ไม่อนุมัติ'
  const confirmColor = status === 1 ? '#198754' : '#dc3545'

  const result = await Swal.fire({
    title: `ยืนยันการ${actionText}?`,
    text: `เอกสารเลขที่ ${change_no || ''}`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: confirmColor,
    cancelButtonColor: '#6c757d',
    confirmButtonText: `ใช่, ${actionText}`,
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      Swal.fire({ title: 'กำลังประมวลผล...', didOpen: () => Swal.showLoading() })
      
      // 🌟 ส่ง API ไปอัปเดตและสลับตารางเวร (หากอนุมัติ)
      const res = await api.post(`?route=admin/ven_approve&action=force_update`, { change_id: id, status: status })
      
      if (res.data.success) {
        Swal.fire('สำเร็จ', `ทำรายการ${actionText}เรียบร้อยแล้ว`, 'success')
        fetchRequests() 
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', error.response?.data?.error || 'ไม่สามารถทำรายการได้', 'error')
    }
  }
}

const handleEdit = async (item) => {
  const { value: newStatus } = await Swal.fire({
    title: 'แก้ไขสถานะใบเปลี่ยนเวร',
    html: `เอกสารเลขที่ <b>${item.change_no || ''}</b><br><small class="text-danger">*การแก้ไขสถานะย้อนหลัง อาจไม่ส่งผลต่อการสลับชื่อในปฏิทินเวรอัตโนมัติ โปรดตรวจสอบตารางเวรอีกครั้ง</small>`,
    input: 'select',
    inputOptions: {
      '1': 'อนุมัติ',
      // '2': 'ไม่อนุมัติ',
      '0': 'รออนุมัติ (รีเซ็ต)'
    },
    inputValue: item.status.toString(),
    showCancelButton: true,
    confirmButtonColor: '#ffc107',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'บันทึกการแก้ไข',
    cancelButtonText: 'ยกเลิก'
  })

  if (newStatus && newStatus != item.status) {
    try {
      // หมายเหตุ: การแก้ไขย้อนหลัง คุณอาจต้องใช้ Endpoint เฉพาะสำหรับการ Force Update
      const res = await api.post(`?route=admin/ven_approve&action=force_update`, { 
        change_id: item.id, 
        status: parseInt(newStatus) 
      })
      if (res.data.success) {
        Swal.fire('สำเร็จ', 'อัปเดตสถานะเรียบร้อยแล้ว', 'success')
        fetchRequests() 
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', 'ไม่สามารถแก้ไขสถานะได้', 'error')
    }
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute:'2-digit' }) + ' น.'
}

onMounted(() => {
  fetchRequests()
})
</script>