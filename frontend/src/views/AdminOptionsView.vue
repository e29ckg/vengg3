<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-md-9 col-lg-6">
        <div class="card shadow-sm border-0">
             <div class="card-header bg-dark text-white py-3">
            <h5 class="mb-0"> <i class="bi bi-card-checklist"></i> จัดการข้อมูลตัวเลือกในระบบ</h5>
          </div>
          <div class="card-body p-4">
              <div class="card shadow-sm border-0">
                <div class="card-header bg-white pb-0">
                  <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                      <button class="nav-link active fw-bold" id="prefix-tab" data-bs-toggle="tab" data-bs-target="#prefix" type="button">
                        คำนำหน้าชื่อ
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link fw-bold" id="position-tab" data-bs-toggle="tab" data-bs-target="#position" type="button">
                        ตำแหน่ง
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link fw-bold" id="department-tab" data-bs-toggle="tab" data-bs-target="#department" type="button">
                        กลุ่มงาน
                      </button>
                    </li>
                  </ul>
                </div>

          </div>

        <div class="tab-content" id="myTabContent">
          
          <div class="tab-pane fade show active" id="prefix" role="tabpanel">
            <div class="d-flex mb-3">
              <input type="text" class="form-control me-2" v-model="newItems.prefix" placeholder="เพิ่มคำนำหน้าชื่อ เช่น นาย, นางสาว">
              <button class="btn btn-success px-4" @click="addItem('prefix')">
                <i class="bi bi-plus-circle"></i> เพิ่ม
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th width="80">ลำดับ</th>
                    <th>คำนำหน้าชื่อ</th>
                    <th width="120" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in options.prefixes" :key="index">
                    <td>{{ index + 1 }}</td>
                    <td>{{ item }}</td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger" @click="removeItem('prefix', item)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="position" role="tabpanel">
            <div class="d-flex mb-3">
              <input type="text" class="form-control me-2" v-model="newItems.position" placeholder="เพิ่มชื่อตำแหน่ง">
              <button class="btn btn-success px-4" @click="addItem('position')">
                <i class="bi bi-plus-circle"></i> เพิ่ม
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th width="80">ลำดับ</th>
                    <th>ชื่อตำแหน่ง</th>
                    <th width="120" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in options.positions" :key="index">
                    <td>{{ index + 1 }}</td>
                    <td>{{ item }}</td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger" @click="removeItem('position', item)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="department" role="tabpanel">
            <div class="d-flex mb-3">
              <input type="text" class="form-control me-2" v-model="newItems.department" placeholder="เพิ่มชื่อกลุ่มงาน/ส่วน">
              <button class="btn btn-success px-4" @click="addItem('department')">
                <i class="bi bi-plus-circle"></i> เพิ่ม
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th width="80">ลำดับ</th>
                    <th>ชื่อกลุ่มงาน</th>
                    <th width="120" class="text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, index) in options.departments" :key="index">
                    <td>{{ index + 1 }}</td>
                    <td>{{ item }}</td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-danger" @click="removeItem('department', item)">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          

        </div>
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

const options = ref({
  prefixes: [],
  positions: [],
  departments: []
})

const newItems = ref({
  prefix: '',
  position: '',
  department: ''
})

// ดึงข้อมูลทั้งหมด
const fetchOptions = async () => {
  try {
    const res = await api.get('?route=admin/user/options')
    if (res.data) {
      options.value.prefixes = res.data.prefixes || []
      options.value.positions = res.data.positions || []
      options.value.departments = res.data.departments || []
    }
  } catch (error) {
    console.error("Load error:", error)
  }
}

// เพิ่มข้อมูลใหม่
const addItem = async (type) => {
  const value = newItems.value[type].trim()
  if (!value) return

  try {
    await api.post('?route=admin/options/add', { type, value })
    Swal.fire({ icon: 'success', title: 'เพิ่มข้อมูลแล้ว', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false })
    newItems.value[type] = '' // เคลียร์ช่องกรอก
    fetchOptions() // รีโหลดข้อมูล
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถเพิ่มข้อมูลได้', 'error')
  }
}

// ลบข้อมูล
const removeItem = async (type, value) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ?',
    text: `ต้องการลบ "${value}" ใช่หรือไม่?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ใช่, ลบทิ้ง',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      await api.post('?route=admin/options/delete', { type, value })
      Swal.fire('สำเร็จ', 'ลบข้อมูลเรียบร้อยแล้ว', 'success')
      fetchOptions()
    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถลบได้ (อาจมีการใช้งานข้อมูลนี้อยู่)', 'error')
    }
  }
}

onMounted(() => fetchOptions())
</script>

<style scoped>
.nav-tabs .nav-link {
  color: #6c757d;
}
.nav-tabs .nav-link.active {
  color: #0d6efd;
  border-bottom: 3px solid #0d6efd;
}
</style>