<template>
  <div class="bg-light min-vh-100 pb-5">
    <div class="container py-5">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          
          <div class="d-flex align-items-center mb-4">
            <h3 class="fw-bold mb-0 text-dark"><i class="bi bi-house-gear-fill me-2 text-primary"></i>ข้อมูลหน่วยงาน</h3>
          </div>

          <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
              <p class="text-muted small fw-bold mb-0 uppercase text-primary">Agency Information & Signatures</p>
            </div>
            
            <div class="card-body p-4 pt-3">
              <form @submit.prevent="saveConfig">
                
                <div class="row g-3 mb-4">
                  <div class="col-md-12">
                    <label class="form-label fw-bold small text-muted">ชื่อหน่วยงาน (ชื่อเต็ม)</label>
                    <div class="input-group">
                      <span class="input-group-text bg-light border-end-0"><i class="bi bi-building"></i></span>
                      <input type="text" class="form-control bg-light-subtle border-start-0" v-model="config.agency_name" placeholder="ตัวอย่าง: ศาลจังหวัดเพชรบุรี" required>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">ชื่อย่อ</label>
                    <input type="text" class="form-control" v-model="config.agency_short_name" placeholder="ตัวอย่าง: ศาลจังหวัดฯ">
                  </div>
                </div>

                <hr class="my-4 opacity-50">

                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-badge me-2 text-success"></i>ข้อมูลผู้บริหาร (ผู้อนุมัติ)</h6>
                <div class="row g-3 mb-4">
                  <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">ชื่อ-นามสกุล (ใส่วงเล็บ)</label>
                    <input type="text" class="form-control" v-model="config.director_name" placeholder="ตัวอย่าง: (นายสมชาย รักยุติธรรม)">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">ตำแหน่ง</label>
                    <input type="text" class="form-control" v-model="config.director_position" placeholder="ตัวอย่าง: ผู้พิพากษาหัวหน้าศาล...">
                  </div>
                </div>

                <h6 class="fw-bold mb-3 text-dark"><i class="bi bi-person-workspace me-2 text-info"></i>ข้อมูลผู้จัดทำตาราง (คนร่าง)</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">ชื่อ-นามสกุล (ใส่วงเล็บ)</label>
                    <input type="text" class="form-control" v-model="config.admin_name" placeholder="ตัวอย่าง: (นางสาวขยัน ทำงานดี)">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-bold small text-muted">ตำแหน่ง</label>
                    <input type="text" class="form-control" v-model="config.admin_position" placeholder="ตัวอย่าง: ผู้อำนวยการสำนักอำนวยการ...">
                  </div>
                </div>

                <div class="mt-5 p-3 bg-light border rounded-4 border-dashed">
                  <p class="small fw-bold text-muted mb-2 text-center uppercase">-- ตัวอย่างลายเซ็นท้ายตารางเวร --</p>
                  <div class="row text-center mt-3">
                    <div class="col-6">
                      <div class="small mb-4">ผู้จัดทำตารางเวร</div>
                      <div class="fw-bold">{{ config.admin_name || '.......................................................' }}</div>
                      <div class="small">{{ config.admin_position || 'ตำแหน่ง .................................' }}</div>
                    </div>
                    <div class="col-6">
                      <div class="small mb-4">ผู้อนุมัติ</div>
                      <div class="fw-bold">{{ config.director_name || '.......................................................' }}</div>
                      <div class="small">{{ config.director_position || 'ตำแหน่ง .................................' }}</div>
                    </div>
                  </div>
                </div>

                <div class="mt-4 text-end">
                  <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow" :disabled="isSubmitting">
                    <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i class="bi bi-save2 me-2"></i>บันทึกข้อมูลหน่วยงาน
                  </button>
                </div>

              </form>
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

const config = ref({
  agency_name: '',
  agency_short_name: '',
  director_name: '',
  director_position: '',
  admin_name: '',
  admin_position: ''
})

const isSubmitting = ref(false)

// ดึงข้อมูลจากฐานข้อมูล
const fetchConfig = async () => {
  try {
    const response = await api.get('?route=admin/agency_config&action=get')
    if (response.data) {
      config.value = response.data
    }
  } catch (error) {
    console.error("Fetch Config Error:", error)
  }
}

// บันทึกข้อมูล
const saveConfig = async () => {
  isSubmitting.value = true
  try {
    await api.post('?route=admin/agency_config&action=update', config.value)
    Swal.fire({
      title: 'สำเร็จ!',
      text: 'อัปเดตข้อมูลหน่วยงานเรียบร้อยแล้ว',
      icon: 'success',
      timer: 2000,
      showConfirmButton: false
    })
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  } finally {
    isSubmitting.value = false
  }
}

onMounted(fetchConfig)
</script>

<style scoped>
.border-dashed { border-style: dashed !important; border-width: 2px !important; }
.bg-light-subtle { background-color: #f8f9fa !important; }
</style>