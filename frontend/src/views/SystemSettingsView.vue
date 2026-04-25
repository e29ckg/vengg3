<template>
  <div class="bg-light min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4" href="#"><i class="bi bi-gear me-2"></i>Admin Panel</a>
        <div class="d-flex align-items-center">
          <router-link to="/home" class="btn btn-outline-light btn-sm rounded-pill px-3 me-3">
            <i class="bi bi-arrow-left me-1"></i>กลับหน้าปฏิทิน
          </router-link>
        </div>
      </div>
    </nav>
    <div class="container py-5">
      <div class="row">
        <div class="col-md-3 mb-4">
          <div class="list-group shadow-sm border-0 rounded-4 overflow-hidden">
            <button @click="currentTab = 'dep'" :class="['list-group-item list-group-item-action border-0 p-3', currentTab === 'dep' ? 'active' : '']">
              <i class="bi bi-briefcase me-2"></i>จัดการตำแหน่ง
            </button>
            <button @click="currentTab = 'group'" :class="['list-group-item list-group-item-action border-0 p-3', currentTab === 'group' ? 'active' : '']">
              <i class="bi bi-collection me-2"></i>จัดการกลุ่มงาน
            </button>
            <button @click="currentTab = 'fname'" :class="['list-group-item list-group-item-action border-0 p-3', currentTab === 'fname' ? 'active' : '']">
              <i class="bi bi-person-badge me-2"></i>คำนำหน้าชื่อ
            </button>
            <button @click="currentTab = 'ven_name'" :class="['list-group-item list-group-item-action border-0 p-3', currentTab === 'ven_name' ? 'active' : '']">
              <i class="bi bi-calendar-event me-2"></i>ประเภทเวร (หลัก)
            </button>
            <button @click="currentTab = 'sign_name'" :class="['list-group-item list-group-item-action border-0 p-3', currentTab === 'sign_name' ? 'active' : '']">
              <i class="bi bi-pen me-2"></i>ผู้มีอำนาจลงนาม
            </button>
            <button @click="currentTab = 'agency'" :class="['list-group-item list-group-item-action border-0 p-3', currentTab === 'agency' ? 'active' : '']">
              <i class="bi bi-building me-2"></i>ข้อมูลหน่วยงาน
            </button>
          </div>
        </div>

        <div class="col-md-9">
          <div class="card shadow-sm border-0 rounded-4 p-4" v-if="currentTab !== 'agency'">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h4 class="fw-bold mb-0">จัดการ{{ getTabTitle() }}</h4>
              <button class="btn btn-primary rounded-pill px-4" @click="openAddModal">
                <i class="bi bi-plus-circle me-2"></i>เพิ่มใหม่
              </button>
            </div>

            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th width="10%">ID</th>
                  <th>ชื่อรายการ</th>
                  <th width="20%" class="text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in items" :key="item.id">
                  <td>{{ item.id }}</td>
                  <td class="fw-semibold">{{ item.name }}</td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary rounded-circle me-2" @click="openEditModal(item)"><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger rounded-circle" @click="deleteItem(item.id)"><i class="bi bi-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="card shadow-sm border-0 rounded-4 p-4" v-else>
            <h4 class="fw-bold mb-4">ตั้งค่าข้อมูลหน่วยงาน</h4>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label fw-bold">ชื่อหน่วยงาน (สำหรับแสดงหัวรายงาน)</label>
                <input type="text" class="form-control" v-model="agencyInfo.name" placeholder="เช่น ศาลจังหวัด...">
              </div>
              <div class="col-12">
                <label class="form-label fw-bold">ที่อยู่ / รายละเอียดเพิ่มเติม</label>
                <textarea class="form-control" v-model="agencyInfo.address" rows="3"></textarea>
              </div>
              <div class="col-12 mt-4 text-end">
                <button class="btn btn-success rounded-pill px-5 fw-bold" @click="saveAgencyInfo">
                  บันทึกข้อมูลหน่วยงาน
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'

const currentTab = ref('dep')
const items = ref([])
const agencyInfo = ref({ name: '', address: '' })

const getTabTitle = () => {
  const titles = { dep: 'ตำแหน่ง', group: 'กลุ่มงาน', fname: 'คำนำหน้าชื่อ', ven_name: 'ประเภทเวร', sign_name: 'ผู้ลงนาม' }
  return titles[currentTab.value] || ''
}

const fetchItems = async () => {
  if (currentTab.value === 'agency') {
    // ดึงข้อมูลหน่วยงาน
    const res = await api.get('?route=admin/setting&table=agency_config&action=list')
    // สมมติโครงสร้าง: id=1 คือชื่อหน่วยงาน, id=2 คือที่อยู่ (หรือปรับตาม db คุณ)
    agencyInfo.value.name = res.data.find(i => i.name === 'agency_name')?.val || ''
    agencyInfo.value.address = res.data.find(i => i.name === 'agency_address')?.val || ''
  } else {
    const response = await api.get(`?route=admin/setting&table=${currentTab.value}&action=list`)
    items.value = response.data
  }
}

watch(currentTab, () => fetchItems())

// ฟังก์ชัน saveAgencyInfo สำหรับปุ่มบันทึกหน่วยงาน
const saveAgencyInfo = async () => {
  try {
    // ส่งข้อมูลไปอัปเดตที่ API (สร้าง route รองรับใน index.php ด้วยนะครับ)
    await api.post('?route=admin/setting&table=agency_config&action=update_agency', agencyInfo.value)
    Swal.fire('สำเร็จ', 'อัปเดตข้อมูลหน่วยงานเรียบร้อย', 'success')
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกได้', 'error')
  }
}

const openAddModal = async () => {
  const { value: name } = await Swal.fire({
    title: 'เพิ่มข้อมูลใหม่',
    input: 'text',
    inputLabel: 'กรุณากรอกชื่อ',
    showCancelButton: true
  })
  if (name) {
    await api.post(`?route=admin/setting&table=${currentTab.value}&action=create`, { name })
    fetchItems()
  }
}

const openEditModal = async (item) => {
  const { value: name } = await Swal.fire({
    title: 'แก้ไขข้อมูล',
    input: 'text',
    inputValue: item.name,
    showCancelButton: true
  })
  if (name) {
    await api.post(`?route=admin/setting&table=${currentTab.value}&action=update`, { id: item.id, name })
    fetchItems()
  }
}

const deleteItem = async (id) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ?',
    text: "หากลบแล้วข้อมูลที่เกี่ยวข้องอาจมีปัญหา",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33'
  })
  if (result.isConfirmed) {
    await api.post(`?route=admin/setting&table=${currentTab.value}&action=delete`, { id })
    fetchItems()
  }
}

onMounted(() => fetchItems())
</script>