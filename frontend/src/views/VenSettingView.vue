<template>
  <div class="bg-light min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-5" href="#">
          <i class="bi bi-briefcase-fill me-2"></i>กลุ่มงานช่วยอำนวยการ
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#directorMenu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="directorMenu">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
            
            <li class="nav-item">
              <router-link to="/director/ven-settings" class="nav-link" active-class="active fw-bold text-warning">
                <i class="bi bi-card-checklist me-1"></i> ชื่อเวร / กลุ่มหน้าที่
              </router-link>
            </li>
            
            <li class="nav-item">
              <router-link to="/director/commands" class="nav-link" active-class="active fw-bold text-warning">
                <i class="bi bi-file-earmark-text me-1"></i> จัดการคำสั่งเวร
              </router-link>
            </li>
            
            <li class="nav-item">
              <router-link to="/director/schedule" class="nav-link" active-class="active fw-bold text-warning">
                <i class="bi bi-calendar-week me-1"></i> จัดเวร
              </router-link>
            </li>
            
          </ul>
          
          <div class="d-flex">
            <router-link to="/home" class="btn btn-outline-light btn-sm rounded-pill px-3 shadow-sm">
              <i class="bi bi-house-door-fill me-1"></i>กลับหน้าหลัก
            </router-link>
          </div>
        </div>
        
      </div>
    </nav>

    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark"><i class="bi bi-card-checklist me-2"></i>ชื่อเวร / กลุ่มหน้าที่</h3>
        <button class="btn btn-success fw-bold px-4 shadow-sm rounded-pill" @click="addMainVen">
          <i class="bi bi-plus-circle me-2"></i>เพิ่มชื่อเวร
        </button>
      </div>

      <div class="row row-cols-1 row-cols-lg-2 g-4">
        <div class="col" v-for="(ven, index) in venData" :key="ven.id">
          <div class="card h-100 shadow-sm border-0 rounded-4">
            
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-start">
              <h6 class="fw-bold mb-0 text-dark lh-base" style="width: 75%;">
                {{ ven.srt }} {{ ven.name }}
              </h6>
              <div>
                <button class="btn btn-warning btn-sm fw-bold text-dark rounded-3 px-3 me-1" @click="editMainVen(ven)">
                  แก้ไข
                </button>
                <button class="btn btn-outline-danger btn-sm rounded-3 px-2" @click="deleteItem('ven_name', ven.id)" title="ลบเวรนี้">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </div>

            <div class="card-body px-4 pb-4 pt-2">
              <ul class="list-group list-group-flush mb-4 border rounded-3 overflow-hidden">
                <li class="list-group-item d-flex justify-content-between align-items-center border-bottom-0 sub-item"
                    v-for="(sub, sIndex) in ven.subs" :key="sub.id"
                    :style="{ backgroundColor: sub.color, color: getTextColor(sub.color) }"
                    draggable="true" 
                    @dragstart="onDragStartSub(sIndex, ven.id)"
                    @dragover.prevent
                    @drop="onDropSub(sIndex, ven.id)">
                  
                  <span class="fw-semibold fs-6 d-flex align-items-center">
                    <i class="bi bi-grip-vertical me-2 drag-handle fs-5" title="ลากเพื่อสลับตำแหน่ง"></i>
                    {{ sub.srt }} <span class="ms-2">{{ sub.name }}</span> 
                    <span class="ms-2 badge bg-white text-dark rounded-pill shadow-sm">
                      <i class="bi bi-cash-coin text-warning me-1"></i>{{ Number(sub.price).toLocaleString() }}
                    </span>
                  </span>
                  
                  <div>
                    <button class="btn btn-sm border-0 opacity-75 hover-opacity-100" 
                            :style="{ color: getTextColor(sub.color) }" 
                            @click="openManageUsersModal(sub)" 
                            title="กำหนดผู้มีสิทธิ์ลงเวรนี้">
                      <i class="bi bi-people-fill fs-5"></i>
                    </button>
                    <button class="btn btn-sm border-0 opacity-75 hover-opacity-100" :style="{ color: getTextColor(sub.color) }" @click="openSubModal('edit', ven.id, sub)">
                      <i class="bi bi-pencil-square fs-5"></i>
                    </button>
                    <button class="btn btn-sm border-0 opacity-75 hover-opacity-100" :style="{ color: getTextColor(sub.color) }" @click="deleteItem('ven_name_sub', sub.id)">
                      <i class="bi bi-trash fs-5"></i>
                    </button>
                  </div>
                </li>
                <li class="list-group-item text-center text-muted py-3" v-if="ven.subs.length === 0">
                  <small>ยังไม่มีข้อมูลหน้าที่ย่อย</small>
                </li>
              </ul>

              <div class="text-center">
                <button class="btn btn-outline-success btn-sm fw-bold rounded-pill px-4" @click="openSubModal('add', ven.id)">
                  <i class="bi bi-plus-lg me-1"></i>เพิ่มหน้าที่
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="subVenModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-success text-white border-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-tags me-2"></i>{{ isEditingSub ? 'แก้ไขหน้าที่ย่อย' : 'เพิ่มหน้าที่ย่อย' }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeSubModal"></button>
          </div>
          <form @submit.prevent="submitSubVen">
            <div class="modal-body p-4">
              <div class="mb-3">
                <label class="form-label fw-semibold">ชื่อหน้าที่ *</label>
                <input type="text" class="form-control" v-model="subForm.name" required placeholder="เช่น ผู้พิพากษา, งานรับฟ้อง">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">ค่าตอบแทน (บาท) *</label>
                <input type="number" class="form-control" v-model="subForm.price" required min="0">
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold">สีป้ายแสดงผล *</label>
                <div class="d-flex flex-wrap gap-2 mb-3">
                  <div v-for="c in presetColors" :key="c" 
                       @click="selectColor(c)"
                       class="rounded-circle shadow-sm"
                       :style="{ 
                         backgroundColor: c, 
                         width: '35px', height: '35px', 
                         cursor: 'pointer',
                         border: subForm.color.toLowerCase() === c.toLowerCase() ? '3px solid #333' : '2px solid #fff'
                       }"
                       :title="c">
                  </div>
                  <div class="rounded-circle shadow-sm overflow-hidden" 
                       style="width: 35px; height: 35px; border: 2px solid #fff;" 
                       title="เลือกสีอื่นๆ">
                    <input type="color" v-model="subForm.color" class="p-0 border-0 w-100 h-100" style="cursor: pointer;">
                  </div>
                </div>
                <div class="input-group input-group-sm">
                  <span class="input-group-text" :style="{ backgroundColor: subForm.color, width: '40px' }"></span>
                  <input type="text" class="form-control" v-model="subForm.color" required placeholder="คลิกเลือกสีด้านบน หรือพิมพ์โค้ดสี HEX">
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">บันทึกข้อมูล</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="mainVenModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-primary text-white border-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-card-heading me-2"></i>{{ isEditingMain ? 'แก้ไขชื่อเวร' : 'เพิ่มชื่อเวร' }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeMainModal"></button>
          </div>
          <form @submit.prevent="submitMainVen">
            <div class="modal-body p-4">
              <div class="row g-3 mb-3">
                <div class="col-md-2">
                  <label class="form-label fw-semibold text-muted small">ลำดับ</label>
                  <input type="number" class="form-control" v-model="mainForm.srt" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-muted small">ชื่อเวร (แบบย่อ)</label>
                  <input type="text" class="form-control" v-model="mainForm.name" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold text-muted small">กลางวัน/กลางคืน</label>
                  <select class="form-select" v-model="mainForm.dn" required>
                    <option value="กลางวัน(08.30-16.30)">กลางวัน(08.30-16.30)</option>
                    <option value="กลางคืน(16.30-08.30)">กลางคืน(16.30-08.30)</option>
                    <option value="nightCourt(16.30-20.00)">nightCourt(16.30-20.00)</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold text-muted small">ชื่อเวรเต็ม (แสดงในคำสั่ง)</label>
                <textarea class="form-control" v-model="mainForm.name_full" rows="4" required placeholder="ให้ข้าราชการตุลาการ..."></textarea>
              </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4 d-flex justify-content-end">
              <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold">บันทึก</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="manageVenUsersModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-info text-white border-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-people-fill me-2"></i>ผู้มีสิทธิ์อยู่เวร: {{ activeManageSub?.name }}</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeManageUsersModal"></button>
          </div>
          <div class="modal-body p-4 row g-4">
             <div class="col-md-6 border-end">
                <h6 class="fw-bold mb-3 text-success">รายชื่อที่มีสิทธิ์ปัจจุบัน</h6>
                <ul class="list-group list-group-flush border rounded-3 overflow-hidden">
                   <li v-for="(u, index) in assignedUsers" :key="u.vu_id" class="list-group-item d-flex justify-content-between align-items-center bg-light">
                      <span>
                        <span class="badge bg-secondary me-2">{{ index + 1 }}</span>
                        <i class="bi bi-person-check-fill text-success me-2"></i>{{ u.prefix }}{{ u.name }} {{ u.sname }}
                      </span>
                      
                      <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary border-0" @click="moveUserUp(index)" :disabled="index === 0" title="เลื่อนขึ้น">
                          <i class="bi bi-arrow-up"></i>
                        </button>
                        <button class="btn btn-outline-secondary border-0" @click="moveUserDown(index)" :disabled="index === assignedUsers.length - 1" title="เลื่อนลง">
                          <i class="bi bi-arrow-down"></i>
                        </button>
                        <button class="btn btn-outline-danger border-0 ms-1" @click="removeUserFromSub(u.vu_id)" title="ลบออก">
                          <i class="bi bi-trash"></i>
                        </button>
                      </div>
                   </li>
                   <li v-if="assignedUsers.length === 0" class="list-group-item text-muted text-center py-4">ยังไม่ได้กำหนดรายชื่อ</li>
                </ul>
             </div>
             <div class="col-md-6">
                <h6 class="fw-bold mb-3 text-primary">เพิ่มรายชื่อใหม่</h6>
                <div class="input-group mb-3 shadow-sm rounded-pill overflow-hidden border">
                   <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
                   <input type="text" class="form-control border-0 shadow-none" v-model="userSearchQuery" placeholder="ค้นหาชื่อพนักงาน...">
                </div>
                <div class="list-group list-group-flush border rounded-3 overflow-auto" style="max-height: 250px;">
                   <button v-for="user in filteredAllUsers" :key="user.id" 
                           class="list-group-item list-group-item-action py-2 d-flex justify-content-between align-items-center" 
                           @click="addUserToSub(user.id)">
                      <span>{{ user.full_name }}</span>
                      <i class="bi bi-plus-circle text-primary fs-5"></i>
                   </button>
                   <div v-if="filteredAllUsers.length === 0" class="text-center text-muted p-3">ไม่พบรายชื่อ หรือพนักงานทุกคนถูกเพิ่มแล้ว</div>
                </div>
             </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../services/api'
import Swal from 'sweetalert2'
import { Modal } from 'bootstrap'

// ==========================================
// 🌟 1. ส่วนจัดการข้อมูลหลัก (Ven Full Data)
// ==========================================
const venData = ref([])

const fetchVenFullData = async () => {
  try {
    const response = await api.get('?route=admin/setting&action=ven_full')
    // 🌟 ดึงข้อมูลมาแล้ว สั่งเรียงลำดับหน้าที่ย่อย (subs) ตามค่า srt เลย
    venData.value = response.data.map(ven => {
      if (ven.subs) ven.subs.sort((a, b) => a.srt - b.srt)
      return ven
    })
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลเวรได้', 'error')
  }
}

const deleteItem = async (table, id) => {
  const result = await Swal.fire({ title: 'ยืนยันการลบ?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33' })
  if (result.isConfirmed) {
    await api.post(`?route=admin/setting&table=${table}&action=delete`, { id })
    fetchVenFullData()
  }
}

const getTextColor = (bgColor) => {
  if (!bgColor) return '#000'
  const darkColors = ['blueviolet', 'brown', 'green', 'magenta', 'darkblue', '#8a2be2', '#a52a2a', '#008000', '#ff00ff']
  return darkColors.includes(bgColor.toLowerCase()) ? '#fff' : '#000'
}

// ==========================================
// 🌟 2. ส่วนจัดการชื่อเวรหลัก (Main Ven)
// ==========================================
let mainModalInstance = null
const isEditingMain = ref(false)
const mainForm = ref({ id: '', srt: 1, name: '', dn: 'กลางวัน(08.30-16.30)', name_full: '' })

const addMainVen = () => {
  isEditingMain.value = false
  mainForm.value = { id: '', srt: venData.value.length + 1, name: '', dn: 'กลางวัน(08.30-16.30)', name_full: '' }
  mainModalInstance.show()
}

const editMainVen = async (ven) => {
  Swal.fire({ title: 'กำลังดึงข้อมูล...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
  try {
    const response = await api.get(`?route=admin/setting&table=ven_name&action=get_by_id&id=${ven.id}`);
    const data = response.data;
    if (data) {
      isEditingMain.value = true;
      mainForm.value = { 
        id: data.id, 
        srt: data.srt || 0, 
        name: data.name, 
        dn: data.dn || 'กลางวัน(08.30-16.30)',
        name_full: data.name_full || '' 
      };
      Swal.close();
      mainModalInstance.show();
    } else {
      Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลที่ต้องการ', 'error');
    }
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลจากเซิร์ฟเวอร์ได้', 'error');
  }
}

const submitMainVen = async () => {
  try {
    const action = isEditingMain.value ? 'update_venname' : 'create_venname' 
    await api.post(`?route=admin/setting&table=ven_name&action=${action}`, mainForm.value)
    document.getElementById('closeMainModal').click()
    Swal.fire('สำเร็จ', 'บันทึกชื่อเวรหลักเรียบร้อย', 'success')
    fetchVenFullData()
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  }
}

// ==========================================
// 🌟 3. ส่วนจัดการหน้าที่ย่อย (Sub Ven)
// ==========================================
let subModalInstance = null
const isEditingSub = ref(false)
const subForm = ref({ id: '', ven_name_id: '', name: '', price: 0, color: 'BlueViolet', srt: 1 }) // เพิ่ม srt
const presetColors = ref(['BlueViolet', 'Brown', 'Green', 'Magenta', 'Teal', 'DarkBlue', 'Crimson', 'DarkOrange', '#0d6efd', '#198754', '#dc3545', '#ffc107'])

const selectColor = (colorCode) => { subForm.value.color = colorCode }

const openSubModal = (mode, venNameId, sub = null) => {
  if (mode === 'add') {
    isEditingSub.value = false
    const parentVen = venData.value.find(v => v.id === venNameId)
    const nextSrt = parentVen && parentVen.subs ? parentVen.subs.length + 1 : 1
    subForm.value = { id: '', ven_name_id: venNameId, name: '', price: 0, color: 'BlueViolet', srt: nextSrt }
  } else {
    isEditingSub.value = true
    subForm.value = { ...sub }
  }
  subModalInstance.show()
}

const submitSubVen = async () => {
  try {
    const action = isEditingSub.value ? 'update_sub' : 'create_sub'
    await api.post(`?route=admin/setting&table=ven_name_sub&action=${action}`, subForm.value)
    document.getElementById('closeSubModal').click()
    Swal.fire('สำเร็จ', 'บันทึกข้อมูลหน้าที่ย่อยเรียบร้อย', 'success')
    fetchVenFullData()
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error')
  }
}

// ==========================================
// 🌟 3.1 Drag & Drop จัดเรียงหน้าที่ย่อย
// ==========================================
const draggedIndex = ref(null)
const draggedVenId = ref(null)

const onDragStartSub = (index, venId) => {
  draggedIndex.value = index
  draggedVenId.value = venId
}

const onDropSub = async (dropIndex, venId) => {
  // เช็คว่าลากข้ามกลุ่ม หรือ ปล่อยที่เดิมหรือไม่
  if (draggedVenId.value !== venId) return 
  if (draggedIndex.value === dropIndex) return 

  const ven = venData.value.find(v => v.id === venId)
  
  // สลับตำแหน่งใน Array
  const draggedItem = ven.subs.splice(draggedIndex.value, 1)[0]
  ven.subs.splice(dropIndex, 0, draggedItem)

  // จัดเลข srt ใหม่
  ven.subs.forEach((sub, i) => { sub.srt = i + 1 })

  // เตรียมข้อมูล Payload
  const payload = ven.subs.map(sub => ({ id: sub.id, srt: sub.srt }))

  try {
    await api.post('?route=admin/setting&table=ven_name_sub&action=update_order', payload)
    draggedIndex.value = null
    draggedVenId.value = null
  } catch (error) {
    Swal.fire('ผิดพลาด', 'ไม่สามารถบันทึกการจัดเรียงได้', 'error')
    fetchVenFullData()
  }
}

// ==========================================
// 🌟 4. ส่วนจัดการผู้อยู่เวร (Ven Users)
// ==========================================
const activeManageSub = ref(null)
const assignedUsers = ref([])
const allUsers = ref([])
const userSearchQuery = ref('')
let manageUsersModalInstance = null

const fetchAllUsers = async () => {
  const res = await api.get('?route=admin/user/list') 
  allUsers.value = res.data
}

const fetchAssignedUsers = async (subId) => {
  const res = await api.get(`?route=admin/ven_user&action=get_by_sub&sub_id=${subId}`)
  assignedUsers.value = res.data || []
}

const filteredAllUsers = computed(() => {
  const assignedIds = assignedUsers.value.map(u => u.user_id)
  let available = allUsers.value.filter(u => !assignedIds.includes(u.id))
  if (userSearchQuery.value) {
    available = available.filter(u => u.full_name.includes(userSearchQuery.value))
  }
  return available
})

const openManageUsersModal = async (sub) => {
  activeManageSub.value = sub
  userSearchQuery.value = ''
  await fetchAssignedUsers(sub.id)
  manageUsersModalInstance.show()
}

const addUserToSub = async (userId) => {
  await api.post('?route=admin/ven_user&action=add', { sub_id: activeManageSub.value.id, user_id: userId })
  await fetchAssignedUsers(activeManageSub.value.id) 
}

const removeUserFromSub = async (vuId) => {
  await api.post('?route=admin/ven_user&action=remove', { vu_id: vuId })
  await fetchAssignedUsers(activeManageSub.value.id) 
}

const saveUserOrder = async () => {
  const orderedIds = assignedUsers.value.map(u => u.vu_id)
  await api.post('?route=admin/ven_user&action=update_order', { ordered_ids: orderedIds })
}

const moveUserUp = async (index) => {
  if (index > 0) {
    const temp = assignedUsers.value[index];
    assignedUsers.value[index] = assignedUsers.value[index - 1];
    assignedUsers.value[index - 1] = temp;
    await saveUserOrder(); 
  }
}

const moveUserDown = async (index) => {
  if (index < assignedUsers.value.length - 1) {
    const temp = assignedUsers.value[index];
    assignedUsers.value[index] = assignedUsers.value[index + 1];
    assignedUsers.value[index + 1] = temp;
    await saveUserOrder();
  }
}

// ==========================================
// 🌟 Initialize เมื่อหน้าเว็บโหลดเสร็จ
// ==========================================
onMounted(() => {
  fetchVenFullData()
  fetchAllUsers() 
  subModalInstance = new Modal(document.getElementById('subVenModal'))
  mainModalInstance = new Modal(document.getElementById('mainVenModal'))
  manageUsersModalInstance = new Modal(document.getElementById('manageVenUsersModal'))
})
</script>

<style scoped>
.list-group-item { 
  border-right: 0; 
  border-left: 0; 
  border-top: 1px solid rgba(255, 255, 255, 0.2); 
}
.list-group-item:first-child { 
  border-top: 0; 
}
.hover-opacity-100:hover { 
  opacity: 1 !important; 
  transform: scale(1.1); 
  transition: 0.2s; 
}

/* 🌟 สไตล์สำหรับการลากวาง (Drag & Drop) */
.sub-item {
  transition: transform 0.1s;
}
.sub-item:active {
  transform: scale(0.98);
  opacity: 0.9;
}
.drag-handle {
  cursor: grab;
  opacity: 0.6;
}
.drag-handle:active {
  cursor: grabbing;
}
.drag-handle:hover {
  opacity: 1;
}
</style>