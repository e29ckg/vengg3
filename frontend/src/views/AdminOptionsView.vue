<template>
  <div class="container py-4" style="max-width: 900px">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-dark text-white py-3">
        <h5 class="mb-0"><i class="bi bi-card-checklist me-2"></i>จัดการข้อมูลตัวเลือกในระบบ</h5>
      </div>
      <div class="card-body p-4">
        <ul class="nav nav-tabs mb-4" role="tablist" aria-label="ประเภทตัวเลือก">
          <li v-for="category in categories" :key="category.type" class="nav-item" role="presentation">
            <button :id="`${category.type}-tab`" type="button" role="tab" class="nav-link fw-bold"
              :class="{active: activeType === category.type}" :aria-selected="activeType === category.type"
              aria-controls="options-panel" @click="activeType = category.type">
              {{ category.label }} <span class="badge bg-secondary ms-1">{{ options[category.key].length }}</span>
            </button>
          </li>
        </ul>
        <div id="options-panel" role="tabpanel" :aria-labelledby="`${activeType}-tab`">
          <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
            <h6 class="mb-0">รายการ{{ activeCategory.label }}</h6>
            <button type="button" class="btn btn-success" :disabled="loading || busy" @click="addItem">
              <i class="bi bi-plus-circle me-1"></i>เพิ่ม{{ activeCategory.label }}
            </button>
          </div>
          <div v-if="error" class="alert alert-danger d-flex justify-content-between align-items-center gap-2" role="alert">
            <span>{{ error }}</span>
            <button type="button" class="btn btn-sm btn-outline-danger text-nowrap" :disabled="loading || busy" @click="fetchOptions">ลองอีกครั้ง</button>
          </div>
          <div v-if="loading" class="text-center text-muted py-4" role="status">กำลังโหลดข้อมูล...</div>
          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light"><tr><th style="width: 80px">ลำดับ</th><th>{{ activeCategory.label }}</th><th style="width: 100px" class="text-center">จัดการ</th></tr></thead>
              <tbody>
                <tr v-for="(item, index) in activeItems" :key="item">
                  <td>{{ index + 1 }}</td><td>{{ item }}</td>
                  <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" :disabled="busy"
                    :aria-label="`ลบ${item}`" @click="removeItem(item)"><i class="bi bi-trash"></i></button></td>
                </tr>
                <tr v-if="!activeItems.length && !error"><td colspan="3" class="text-center text-muted py-4">ยังไม่มี{{ activeCategory.label }} กดปุ่มเพิ่มเพื่อเริ่มต้น</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'

const categories = [
  {type: 'prefix', key: 'prefixes', label: 'คำนำหน้าชื่อ', placeholder: 'เช่น นาย, นางสาว'},
  {type: 'position', key: 'positions', label: 'ตำแหน่ง', placeholder: 'กรอกชื่อตำแหน่ง'},
  {type: 'department', key: 'departments', label: 'กลุ่มงาน', placeholder: 'กรอกชื่อกลุ่มงาน/ส่วน'}
]
const options = ref({prefixes: [], positions: [], departments: []})
const activeType = ref('prefix'), loading = ref(false), busy = ref(false), error = ref('')
const activeCategory = computed(() => categories.find(category => category.type === activeType.value))
const activeItems = computed(() => options.value[activeCategory.value.key])

async function fetchOptions() {
  loading.value = true
  error.value = ''
  try {
    const {data} = await api.get('?route=admin/options/get')
    for (const category of categories) options.value[category.key] = Array.isArray(data?.[category.key]) ? data[category.key] : []
  } catch (e) {
    error.value = e.response?.data?.error || 'โหลดข้อมูลไม่สำเร็จ กรุณาลองอีกครั้ง'
  } finally { loading.value = false }
}

async function addItem() {
  if (busy.value || loading.value) return
  const category = activeCategory.value
  busy.value = true
  try {
    const result = await Swal.fire({
      title: `เพิ่ม${category.label}`, input: 'text', inputLabel: category.label, inputPlaceholder: category.placeholder,
      showCancelButton: true, confirmButtonText: 'บันทึก', cancelButtonText: 'ยกเลิก', showLoaderOnConfirm: true,
      allowOutsideClick: () => !Swal.isLoading(), allowEscapeKey: () => !Swal.isLoading(),
      preConfirm: async input => {
        const value = input.trim()
        if (!value) { Swal.showValidationMessage(`กรุณากรอก${category.label}`); return false }
        if (options.value[category.key].includes(value)) { Swal.showValidationMessage('มีข้อมูลนี้อยู่แล้วในระบบ'); return false }
        try {
          await api.post('?route=admin/options/add', {type: category.type, value})
          return value
        } catch (e) { Swal.showValidationMessage(e.response?.data?.error || 'บันทึกไม่สำเร็จ กรุณาลองอีกครั้ง'); return false }
      }
    })
    if (result.isConfirmed) {
      options.value[category.key].push(result.value)
      await fetchOptions()
      await Swal.fire({icon: 'success', title: 'เพิ่มข้อมูลแล้ว', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false})
    }
  } finally { busy.value = false }
}

async function removeItem(value) {
  if (busy.value) return
  const category = activeCategory.value
  busy.value = true
  try {
    const result = await Swal.fire({
      title: `ลบ${category.label}?`, text: `ต้องการลบ "${value}" ใช่หรือไม่?`, icon: 'warning', showCancelButton: true,
      confirmButtonText: 'ลบข้อมูล', cancelButtonText: 'ยกเลิก', confirmButtonColor: '#d33', showLoaderOnConfirm: true,
      allowOutsideClick: () => !Swal.isLoading(), allowEscapeKey: () => !Swal.isLoading(),
      preConfirm: async () => {
        try { await api.post('?route=admin/options/delete', {type: category.type, value}); return true }
        catch (e) { Swal.showValidationMessage(e.response?.data?.error || 'ลบไม่สำเร็จ กรุณาลองอีกครั้ง'); return false }
      }
    })
    if (result.isConfirmed) {
      options.value[category.key] = options.value[category.key].filter(item => item !== value)
      await fetchOptions()
    }
  } finally { busy.value = false }
}
onMounted(fetchOptions)
</script>
