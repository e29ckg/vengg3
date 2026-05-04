<template>
  <div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <h4 class="fw-bold text-primary mb-0"><i class="bi bi-check-circle"></i> อนุมัติใบเปลี่ยนเวร</h4>
      
      <div class="input-group shadow-sm" style="max-width: 350px;">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-primary"></i></span>
        <input type="text" class="form-control border-start-0 ps-0" v-model="searchQuery" placeholder="ค้นหาเลขที่, ชื่อ, หน้าที่...">
        <button v-if="searchQuery" class="btn btn-outline-secondary border-start-0 border" @click="searchQuery = ''" title="ล้างการค้นหา">
          <i class="bi bi-x-lg"></i>
        </button>
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
                <th>รายละเอียดเวร</th>
                <th>ผู้โอน (ผู้ขอ)</th>
                <th>ผู้รับโอน (ผู้แทน)</th>
                <th>สถานะ</th>
                <th class="text-center">จัดการ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredRequests" :key="item.id">
                <td class="fw-bold text-primary">{{ item.change_no || 'ไม่มีเลขที่' }}</td>
                <td>{{ formatDate(item.created_at) }}</td>
                <td>
                  <div><i class="bi bi-calendar-event"></i> {{ formatDate(item.ven_date) }}</div>
                  <small class="text-muted">{{ item.duty_main }} - {{ item.duty_role }}</small>
                </td>
                <td class="text-danger"><i class="bi bi-person-dash"></i> {{ item.user1_name }}</td>
                <td class="text-success"><i class="bi bi-person-plus"></i> {{ item.user2_name }}</td>
                <td>
                  <span v-if="item.status == 0" class="badge bg-warning text-dark">รออนุมัติ</span>
                  <span v-else-if="item.status == 1" class="badge bg-success">อนุมัติแล้ว</span>
                  <span v-else class="badge bg-danger">ไม่อนุมัติ</span>
                </td>
                <td class="text-center">
                  <div v-if="item.status == 0" class="btn-group btn-group-sm">
                    <button class="btn btn-success" @click="handleApprove(item.id, 1, item.change_no)">
                      <i class="bi bi-check-lg"></i> อนุมัติ
                    </button>
                    <button class="btn btn-danger" @click="handleApprove(item.id, 2, item.change_no)">
                      <i class="bi bi-x-lg"></i> ไม่อนุมัติ
                    </button>
                  </div>
                  <div v-else>
                    <button class="btn btn-sm btn-outline-warning" @click="handleEdit(item)">
                      <i class="bi bi-pencil-square"></i> แก้ไข
                    </button>
                  </div>
                </td>
              </tr>
              
              <tr v-if="filteredRequests.length === 0">
                <td colspan="7" class="text-center py-5 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                  {{ searchQuery ? 'ไม่พบรายการที่ตรงกับการค้นหา' : 'ไม่มีรายการขอเปลี่ยนเวร' }}
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
import { ref, onMounted, computed } from 'vue' // 🌟 นำเข้า computed
import api from '../services/api'
import Swal from 'sweetalert2'

const changeRequests = ref([])
const searchQuery = ref('') // 🌟 ตัวแปรเก็บข้อความค้นหา

// 🌟 สร้าง Computed Property สำหรับกรองข้อมูลตามข้อความค้นหา
const filteredRequests = computed(() => {
  if (!searchQuery.value) return changeRequests.value // ถ้าไม่ได้พิมพ์ค้นหา ให้โชว์ทั้งหมด

  const query = searchQuery.value.toLowerCase().trim()
  
  return changeRequests.value.filter(item => {
    // ป้องกันกรณีบางฟิลด์เป็น null
    const docNo = (item.change_no || '').toLowerCase()
    const dutyMain = (item.duty_main || '').toLowerCase()
    const dutyRole = (item.duty_role || '').toLowerCase()
    const user1 = (item.user1_name || '').toLowerCase()
    const user2 = (item.user2_name || '').toLowerCase()

    // เช็คว่ามีคำค้นหาอยู่ในฟิลด์ใดฟิลด์หนึ่งหรือไม่
    return docNo.includes(query) || 
           dutyMain.includes(query) || 
           dutyRole.includes(query) || 
           user1.includes(query) || 
           user2.includes(query)
  })
})

const fetchRequests = async () => {
  try {
    const res = await api.get('?route=admin/ven_approve&action=list')
    changeRequests.value = res.data || []
  } catch (error) {
    console.error(error)
  }
}

const handleApprove = async (id, status, change_no) => {
  const actionText = status === 1 ? 'อนุมัติ' : 'ไม่อนุมัติ'
  const confirmColor = status === 1 ? '#198754' : '#dc3545'

  const result = await Swal.fire({
    title: `ยืนยันการ${actionText}?`,
    text: "คุณแน่ใจหรือไม่ที่จะทำรายการนี้",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: confirmColor,
    cancelButtonColor: '#6c757d',
    confirmButtonText: `ใช่, ${actionText}`
  })

  if (result.isConfirmed) {
    try {
      const res = await api.post(`?route=admin/ven_approve&action=update`, { id, status, change_no })
      if (res.data.success) {
        Swal.fire('สำเร็จ', `ทำรายการ${actionText}เรียบร้อยแล้ว`, 'success')
        fetchRequests() // โหลดข้อมูลใหม่
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', 'ไม่สามารถทำรายการได้', 'error')
    }
  }
}

// ฟังก์ชันสำหรับแก้ไขสถานะหลังทำรายการไปแล้ว
const handleEdit = async (item) => {
  const { value: newStatus } = await Swal.fire({
    title: 'แก้ไขสถานะใบเปลี่ยนเวร',
    text: `เอกสารเลขที่ ${item.change_no || 'ไม่มีเลขที่'}`,
    input: 'select',
    inputOptions: {
      '1': 'อนุมัติ',
      '2': 'ไม่อนุมัติ',
      '0': 'รออนุมัติ (รีเซ็ต)'
    },
    inputValue: item.status.toString(),
    showCancelButton: true,
    confirmButtonColor: '#ffc107',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'บันทึกการแก้ไข',
    cancelButtonText: 'ยกเลิก'
  })

  // ถ้ามีการเลือกสถานะใหม่ และไม่ซ้ำกับสถานะเดิม
  if (newStatus && newStatus != item.status) {
    try {
      const res = await api.post(`?route=admin/ven_approve&action=update`, { 
        id: item.id, 
        status: parseInt(newStatus) 
      })
      if (res.data.success) {
        Swal.fire('สำเร็จ', 'อัปเดตสถานะเรียบร้อยแล้ว', 'success')
        fetchRequests() // โหลดข้อมูลตารางใหม่
      }
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', 'ไม่สามารถแก้ไขสถานะได้', 'error')
    }
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  fetchRequests()
})
</script>