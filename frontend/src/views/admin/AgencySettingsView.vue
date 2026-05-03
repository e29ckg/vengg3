<template>
  <div class="container-fluid py-4">
    <div class="row justify-content-center">
      <div class="col-md-9 col-lg-8">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-primary text-white py-3">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>ตั้งค่าข้อมูลหน่วยงานและผู้ลงนาม (เปิด-ปิดได้)</h5>
          </div>
          <div class="card-body p-4">
            <form @submit.prevent="saveSettings">
              
              <div class="mb-4">
                <label class="form-label fw-bold text-primary">ชื่อหน่วยงาน (หัวกระดาษ)</label>
                <input type="text" class="form-control bg-light" v-model="settings.agency_name" placeholder="เช่น ศาลจังหวัดเพชรบุรี" required>
              </div>

              <hr class="my-4">
              <h6 class="fw-bold mb-3"><i class="bi bi-person-fill-gear me-2"></i>ผู้บริหาร (เซ็นอนุมัติ)</h6>
              <div v-for="(item, index) in settings.directors" :key="'dir-'+index" class="card mb-2 border-0" :class="item.is_active ? 'bg-light' : 'bg-secondary bg-opacity-10'">
                <div class="card-body p-2 row g-2 align-items-center" :class="{'opacity-50': !item.is_active}">
                  <div class="col-auto" style="width: 85px;">
                    <span :class="index === 0 ? 'badge bg-primary' : 'badge bg-secondary'">{{ index === 0 ? 'ตัวจริง' : 'แทนลำดับ ' + index }}</span>
                  </div>
                  <div class="col"><input type="text" class="form-control form-control-sm" v-model="item.name" placeholder="ชื่อ-นามสกุล" :disabled="!item.is_active" :required="item.is_active"></div>
                  <div class="col"><input type="text" class="form-control form-control-sm" v-model="item.position" placeholder="ตำแหน่ง" :disabled="!item.is_active" :required="item.is_active"></div>
                  <div class="col-auto">
                    <div class="form-check form-switch mt-1 ms-2" title="เปิด/ปิดการใช้งาน">
                      <input class="form-check-input" type="checkbox" v-model="item.is_active">
                    </div>
                  </div>
                  <div class="col-auto" v-if="index > 0">
                    <button type="button" @click="removeSigner('directors', index)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
              <button v-if="settings.directors.length < 4" type="button" @click="addSigner('directors')" class="btn btn-sm btn-outline-primary mt-1">+ เพิ่มผู้ลงนามแทน</button>

              <hr class="my-4">
              <h6 class="fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>ผู้อำนวยการ (ตรวจสอบ/เสนอ)</h6>
              <div v-for="(item, index) in settings.admins" :key="'adm-'+index" class="card mb-2 border-0" :class="item.is_active ? 'bg-light' : 'bg-secondary bg-opacity-10'">
                <div class="card-body p-2 row g-2 align-items-center" :class="{'opacity-50': !item.is_active}">
                  <div class="col-auto" style="width: 85px;">
                    <span :class="index === 0 ? 'badge bg-primary' : 'badge bg-secondary'">{{ index === 0 ? 'ตัวจริง' : 'แทนลำดับ ' + index }}</span>
                  </div>
                  <div class="col"><input type="text" class="form-control form-control-sm" v-model="item.name" placeholder="ชื่อ-นามสกุล" :disabled="!item.is_active"></div>
                  <div class="col"><input type="text" class="form-control form-control-sm" v-model="item.position" placeholder="ตำแหน่ง" :disabled="!item.is_active"></div>
                  <div class="col-auto">
                    <div class="form-check form-switch mt-1 ms-2" title="เปิด/ปิดการใช้งาน">
                      <input class="form-check-input" type="checkbox" v-model="item.is_active">
                    </div>
                  </div>
                  <div class="col-auto" v-if="index > 0">
                    <button type="button" @click="removeSigner('admins', index)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
              <button v-if="settings.admins.length < 4" type="button" @click="addSigner('admins')" class="btn btn-sm btn-outline-primary mt-1">+ เพิ่มผู้ลงนามแทน</button>

              <hr class="my-4">
              <h6 class="fw-bold mb-3"><i class="bi bi-cash-coin me-2"></i>เจ้าหน้าที่การเงิน (ผู้จัดทำรายงาน)</h6>
              <div v-for="(item, index) in settings.finances" :key="'fin-'+index" class="card mb-2 border-0" :class="item.is_active ? 'bg-light' : 'bg-secondary bg-opacity-10'">
                <div class="card-body p-2 row g-2 align-items-center" :class="{'opacity-50': !item.is_active}">
                  <div class="col-auto" style="width: 85px;">
                    <span :class="index === 0 ? 'badge bg-primary' : 'badge bg-secondary'">{{ index === 0 ? 'ตัวจริง' : 'แทนลำดับ ' + index }}</span>
                  </div>
                  <div class="col"><input type="text" class="form-control form-control-sm" v-model="item.name" placeholder="ชื่อ-นามสกุล" :disabled="!item.is_active"></div>
                  <div class="col"><input type="text" class="form-control form-control-sm" v-model="item.position" placeholder="ตำแหน่ง" :disabled="!item.is_active"></div>
                  <div class="col-auto">
                    <div class="form-check form-switch mt-1 ms-2" title="เปิด/ปิดการใช้งาน">
                      <input class="form-check-input" type="checkbox" v-model="item.is_active">
                    </div>
                  </div>
                  <div class="col-auto" v-if="index > 0">
                    <button type="button" @click="removeSigner('finances', index)" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                  </div>
                </div>
              </div>
              <button v-if="settings.finances.length < 4" type="button" @click="addSigner('finances')" class="btn btn-sm btn-outline-primary mt-1">+ เพิ่มผู้ลงนามแทน</button>

              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary py-2" :disabled="loading">
                  <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                  <i class="bi bi-save me-2" v-else></i> บันทึกข้อมูลตั้งค่า
                </button>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'
import Swal from 'sweetalert2'

const loading = ref(false)
const settings = ref({
  agency_name: '',
  directors: [],
  admins: [],
  finances: []
})

// ฟังก์ชันดัดแปลงโครงสร้าง เพื่อให้แน่ใจว่าทุกคนมีตัวแปร is_active (สำหรับคนที่มีข้อมูลเก่าอยู่แล้ว)
const mapSigners = (arr) => {
  if (!arr || arr.length === 0) return [{ name: '', position: '', is_active: true }]
  return arr.map(s => ({
    name: s.name || '',
    position: s.position || '',
    is_active: s.is_active !== undefined ? s.is_active : true
  }))
}

// ฟังก์ชันเพิ่ม-ลด ผู้ลงนามแทน (ตั้งค่าเริ่มต้นให้ is_active เป็น true ทันทีที่กดเพิ่ม)
const addSigner = (type) => {
  if (settings.value[type].length < 4) {
    settings.value[type].push({ name: '', position: '', is_active: true })
  }
}
const removeSigner = (type, index) => {
  settings.value[type].splice(index, 1)
}

const fetchSettings = async () => {
  try {
    const res = await api.get('?route=admin/agency_settings')
    if (res.data) {
      settings.value.agency_name = res.data.agency_name || ''
      settings.value.directors = mapSigners(res.data.directors)
      settings.value.admins = mapSigners(res.data.admins)
      settings.value.finances = mapSigners(res.data.finances)
    }
  } catch (err) {
    console.error("Error fetching agency settings:", err)
  }
}

const saveSettings = async () => {
  loading.value = true
  try {
    await api.post('?route=admin/agency_settings', settings.value)
    Swal.fire('สำเร็จ', 'บันทึกข้อมูลหน่วยงานและผู้ลงนามเรียบร้อยแล้ว', 'success')
  } catch (err) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(fetchSettings)
</script>