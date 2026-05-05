<template>
  <div class="bg-light min-vh-100">
    <div class="container py-5">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0"><i class="bi bi-people-fill me-2"></i>จัดการสมาชิก</h3>
        
        <div class="input-group w-50 mx-3 shadow-sm rounded-pill overflow-hidden border">
          <span class="input-group-text bg-white border-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" class="form-control border-0 shadow-none" v-model="searchQuery" placeholder="ค้นหาจาก ชื่อ, นามสกุล หรือ Username...">
        </div>

        <button class="btn btn-success rounded-pill fw-bold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createUserModal">
          <i class="bi bi-person-plus-fill me-2"></i>เพิ่มสมาชิก
        </button>
      </div>

      <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">#</th>
                  <th>ชื่อผู้ใช้งาน</th>
                  <th>ชื่อ-นามสกุล</th>
                  <th>ตำแหน่ง</th>
                  <th>สิทธิ์การใช้งาน</th>
                  <th>สถานะระบบ</th>
                  <th class="text-center pe-4">จัดการ</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="6" class="text-center py-4"><div class="spinner-border text-primary"></div></td>
                </tr>
                <tr v-for="user in filteredUsers" :key="user.id" v-else>
                  <td class="ps-4 text-muted">{{ user.srt }}</td>
                  <td class="fw-bold">{{ user.username }}</td>
                  <td>
                    {{ user.full_name !== 'null null' ? user.full_name : '-' }}
                    <span v-if="user.status == 0" class="badge bg-secondary ms-1" style="font-size: 0.65rem;">ย้าย/ระงับเวร</span>
                  </td>
                  <td>
                    {{ user.position !== 'null null' ? user.position : '-' }}
                  </td>
                  <td>
                    <span class="badge rounded-pill" :class="getRoleColor(user.role)">
                      {{ getRoleName(user.role) }}
                    </span>
                  </td>
                  <td>
                    <span class="badge rounded-pill" :class="user.status === 10 ? 'bg-success' : 'bg-danger'">
                      {{ user.status === 10 ? 'ใช้งาน' : 'ล็อคระบบ' }}
                    </span>
                  </td>
                  <td class="text-center pe-4">
                    <button class="btn btn-sm btn-outline-primary rounded-circle me-1" title="แก้ไข" 
                            data-bs-toggle="modal" data-bs-target="#editUserModal" @click="openEditModal(user)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm rounded-circle me-1" 
                            :class="user.status === 10 ? 'btn-outline-warning' : 'btn-outline-success'"
                            :title="user.status === 10 ? 'ระงับการเข้าสู่ระบบ' : 'เปิดให้เข้าสู่ระบบ'"
                            @click="toggleUserStatus(user.id, user.status)">
                      <i class="bi" :class="user.status === 10 ? 'bi-lock-fill' : 'bi-unlock-fill'"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger rounded-circle" title="ลบผู้ใช้งาน" 
                            @click="deleteUser(user.id)">
                      <i class="bi bi-trash-fill"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-success text-white border-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>เพิ่มสมาชิกใหม่</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeCreateModal"></button>
          </div>
          
          <form @submit.prevent="submitCreateUser">
            <div class="modal-body p-4 bg-light">
              <div class="card border-0 shadow-sm p-3 rounded-3 mb-3">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">คำนำหน้าชื่อ</label>
                    <select class="form-select" v-model="newUser.prefix_name">
                      <option value="">เลือก</option>
                      <option v-for="f in prefixes" :key="f" :value="f">{{ f }}</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ชื่อ *</label>
                    <input type="text" class="form-control" v-model="newUser.first_name" required>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted small mb-1">สกุล</label>
                    <input type="text" class="form-control" v-model="newUser.last_name">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ตำแหน่ง</label>
                    <select class="form-select" v-model="newUser.position" required>
                      <option value="">เลือก</option>
                      <option v-for="po in positions" :key="po" :value="po">{{ po }}</option>
                    </select>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted small mb-1">กลุ่มงาน</label>
                    <select class="form-select" v-model="newUser.departments" required>
                      <option value="">เลือก</option>
                      <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">โทรศัพท์</label>
                    <input type="text" class="form-control" v-model="newUser.phone" required>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">เลขที่บัญชี</label>
                    <input type="text" class="form-control" v-model="newUser.bank_account">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ธนาคาร/สาขา</label>
                    <input type="text" class="form-control" v-model="newUser.bank_comment">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">สถานะ</label>
                    <select class="form-select border-primary" v-model="newUser.status">
                      <option :value="10">ปฏิบัติงานปกติ</option>
                      <option :value="0">ย้าย/ระงับ</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small mb-1">ลำดับอาวุโส</label>
                    <input type="number" min="1" class="form-control border-primary" v-model="newUser.srt" placeholder="เช่น 1">
                  </div>
                </div>
              </div>

              <div class="card border-0 shadow-sm p-3 rounded-3">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ชื่อผู้ใช้งาน (Username) *</label>
                    <input type="text" class="form-control" v-model="newUser.username" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">รหัสผ่าน (Password) *</label>
                    <input type="password" class="form-control" v-model="newUser.password" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">สิทธิ์การใช้งาน (Role) *</label>
                    <select class="form-select" v-model="newUser.role" required>
                      <option value="1">ผู้ปฏิบัติงาน</option>
                      <option value="2">งานอำนวยการ</option>
                      <option value="3">งานการเงิน</option>
                      <option value="9">Admin</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>บันทึก
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header bg-primary text-white border-0 rounded-top-4">
            <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลสมาชิก</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="closeEditModal"></button>
          </div>
          
          <form @submit.prevent="submitEditUser">
            <div class="modal-body p-4 bg-light">
              <div class="card border-0 shadow-sm p-3 rounded-3 mb-3">
                <div class="row g-3">
                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">คำนำหน้าชื่อ</label>
                    <select class="form-select" v-model="editingUser.prefix_name">
                      <option value="">เลือก</option>
                      <option v-for="f in prefixes" :key="f" :value="f">{{ f}}</option>
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ชื่อ *</label>
                    <input type="text" class="form-control" v-model="editingUser.first_name" required>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted small mb-1">สกุล</label>
                    <input type="text" class="form-control" v-model="editingUser.last_name">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ตำแหน่ง</label>
                    <select class="form-select" v-model="editingUser.position">
                      <option value="">เลือก</option>
                      <option v-for="d in positions" :key="d" :value="d">{{ d }}</option>
                    </select>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-semibold text-muted small mb-1">กลุ่มงาน</label>
                    <select class="form-select" v-model="editingUser.department">
                      <option value="">เลือก</option>
                      <option v-for="g in departments" :key="g" :value="g">{{ g }}</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">โทรศัพท์</label>
                    <input type="text" class="form-control" v-model="editingUser.phone">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">เลขที่บัญชี</label>
                    <input type="text" class="form-control" v-model="editingUser.bank_account">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold text-muted small mb-1">ธนาคาร/สาขา</label>
                    <input type="text" class="form-control" v-model="editingUser.bank_comment">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label fw-semibold text-muted small mb-1">สถานะ</label>
                    <select class="form-select border-primary" v-model="editingUser.status">
                      <option :value="10">ปฏิบัติงานปกติ</option>
                      <option :value="0">ย้าย/ระงับ</option>
                    </select>
                  </div>
                  <div class="col-md-2">
                    <label class="form-label fw-semibold text-muted small mb-1">ลำดับอาวุโส</label>
                    <input type="number" min="1" class="form-control border-primary" v-model="editingUser.srt">
                  </div>
                </div>
              </div>

              <div class="card border-0 shadow-sm p-3 rounded-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small mb-1">ชื่อผู้ใช้งาน (ไม่สามารถเปลี่ยนได้)</label>
                    <input type="text" class="form-control bg-light" :value="editingUser.username" disabled>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-muted small mb-1">สิทธิ์การใช้งาน (Role) *</label>
                    <select class="form-select" v-model="editingUser.role" required>
                      <option value="1">ผู้ปฏิบัติงาน</option>
                      <option value="2">งานอำนวยการ</option>
                      <option value="3">งานการเงิน</option>
                      <option value="9">Admin</option>
                    </select>
                  </div>
                  <div class="col-12 border-top pt-2">
                    <label class="form-label fw-semibold text-danger small mb-1">เปลี่ยนรหัสผ่าน (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</label>
                    <input type="password" class="form-control" v-model="editingUser.password" placeholder="พิมพ์รหัสผ่านใหม่ที่นี่...">
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer border-0 pb-4 pe-4">
              <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
              <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" :disabled="isSubmitting">
                <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2"></span>บันทึกการแก้ไข
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue' 
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'
import api from '../services/api' 

const router = useRouter()
const users = ref([])
const isLoading = ref(true)
const isSubmitting = ref(false)

// 🌟 ตัวแปรสำหรับช่องค้นหา
const searchQuery = ref('')

// 🌟 สร้างฟังก์ชันกรองข้อมูลแบบ Real-time
const filteredUsers = computed(() => {
  // ถ้าช่องค้นหาว่างเปล่า ให้แสดงรายชื่อทั้งหมด
  if (!searchQuery.value) {
    return users.value;
  }
  
  // แปลงคำค้นหาเป็นตัวพิมพ์เล็กทั้งหมด
  const lowerCaseQuery = searchQuery.value.toLowerCase();
  
  // กรองข้อมูล (ค้นหาได้ทั้ง full_name และ username)
  return users.value.filter(user => {
    const fullName = user.full_name ? user.full_name.toLowerCase() : '';
    const username = user.username ? user.username.toLowerCase() : '';
    
    return fullName.includes(lowerCaseQuery) || username.includes(lowerCaseQuery);
  });
})

// ตัวเลือก Dropdown
const prefixes = ref({})
const positions = ref({})
const departments = ref({})

// 🌟 ข้อมูลสร้างใหม่ (ปรับค่าเริ่มต้น st=1, srt=999 ให้ตรงกับตอนเคลียร์ค่า)
const newUser = ref({
  username: '', password: '', role: 1,
  prefix_name: '', first_name: '', last_name: '',
  position: '', department: '', phone: '', status: 10,
  bank_account: '', bank_comment: '', st: 1, srt: 999
})

// ข้อมูลสำหรับแก้ไข
const editingUser = ref({
  id: '', username: '', role: 1, password: '',
  prefix_name: '', first_name: '', last_name: '',
  position: '', department: '', phone: '', status: '',
  bank_account: '', bank_comment: '', st: 1, srt: 999
})

// --- ดึงข้อมูลตอนเริ่ม ---
const fetchOptions = async () => {
  try {
    const response = await api.get('?route=admin/user/options')
    prefixes.value = response.data.prefixes
    positions.value = response.data.positions
    departments.value = response.data.departments
  } catch (error) {
    console.error("โหลดข้อมูลตัวเลือกไม่สำเร็จ", error)
  }
}

const fetchUsers = async () => {
  isLoading.value = true
  try {
    const response = await api.get('?route=admin/user/list')
    users.value = response.data
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลผู้ใช้ได้', 'error')
    router.push('/home')
  } finally {
    isLoading.value = false
  }
}

// --- ฟังก์ชันช่วยเหลือ (Format) ---
const getRoleName = (role) => {
  if (role === 9) return 'Admin'
  if (role === 3) return 'การเงิน'
  if (role === 2) return 'อำนวยการ'
  return 'ผู้ใช้ทั่วไป'
}

const getRoleColor = (role) => {
  if (role === 9) return 'bg-dark'
  if (role === 3) return 'bg-info text-dark'
  if (role === 2) return 'bg-warning text-dark'
  return 'bg-secondary'
}

// --- การกระทำ (Actions) ---
const toggleUserStatus = async (id, currentStatus) => {
  const newStatus = currentStatus === 10 ? 0 : 10
  const actionText = currentStatus === 10 ? 'ระงับการใช้งาน' : 'ปลดล็อกการใช้งาน'
  const result = await Swal.fire({
    title: `ยืนยัน${actionText}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: currentStatus === 10 ? '#dc3545' : '#198754',
    confirmButtonText: 'ตกลง',
    cancelButtonText: 'ยกเลิก'
  })

  if (result.isConfirmed) {
    try {
      await api.post('?route=admin/user/status', { id, status: newStatus })
      fetchUsers()
      Swal.fire('สำเร็จ', `ระบบได้${actionText}เรียบร้อยแล้ว`, 'success')
    } catch (error) {
      Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเปลี่ยนสถานะได้', 'error')
    }
  }
}

const submitCreateUser = async () => {
  isSubmitting.value = true
  try {
    const response = await api.post('?route=admin/user/create', newUser.value)
    document.getElementById('closeCreateModal').click()
    
    // เคลียร์ค่าหลังจากบันทึกสำเร็จ
    newUser.value = {
      username: '', password: '', role: 1,
      prefix_name: '', first_name: '', last_name: '',
      position: '', department: '', phone: '',
      bank_account: '', bank_comment: '', st: 1, srt: 999 
    }
    
    Swal.fire('สำเร็จ', response.data.message, 'success')
    fetchUsers()
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', error.response?.data?.error || 'ไม่สามารถสร้างผู้ใช้งานได้', 'error')
  } finally {
    isSubmitting.value = false
  }
}

const openEditModal = (user) => {
  editingUser.value = {
    id: user.id,
    username: user.username,
    role: user.role,
    password: '', 
    prefix_name: user.prefix_name || '',
    first_name: user.first_name || '',
    last_name: user.last_name || '',
    position: user.position || '',             
    department: user.department || '', 
    phone: user.phone || '',
    bank_account: user.bank_account || '',
    bank_comment: user.bank_comment || '',
    status: user.status !== undefined ? user.status : 10, // สถานะ 10=ใช้งาน, 0=ย้าย/ระงับ
    st: user.st !== undefined ? user.st : 1, // สถานะ 1=ปกติ, 0=โอน/ระงับ
    srt: user.srt || 999 // ดึงข้อมูลลำดับอาวุโสมาแสดง
  }
}

const submitEditUser = async () => {
  isSubmitting.value = true
  try {
    const response = await api.post('?route=admin/user/update', editingUser.value)
    document.getElementById('closeEditModal').click()
    Swal.fire('สำเร็จ', response.data.message, 'success')
    fetchUsers()
  } catch (error) {
    Swal.fire('ข้อผิดพลาด', error.response?.data?.error || 'ไม่สามารถแก้ไขข้อมูลได้', 'error')
  } finally {
    isSubmitting.value = false
  }
}

// 🌟 ฟังก์ชันลบ (Soft Delete)
const deleteUser = async (id) => {
  const result = await Swal.fire({
    title: 'ยืนยันการลบ?',
    text: "ข้อมูลผู้ใช้นี้จะถูกซ่อนจากระบบ (Soft Delete)",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    confirmButtonText: 'ใช่, ลบเลย'
  })

  if (result.isConfirmed) {
    try {
      await api.post('?route=admin/user/delete', { id })
      Swal.fire('ลบสำเร็จ!', 'ลบข้อมูลเรียบร้อยแล้ว', 'success')
      fetchUsers()
    } catch (error) {
      Swal.fire('ผิดพลาด', 'ไม่สามารถลบข้อมูลได้', 'error')
    }
  }
}

onMounted(() => {
  fetchOptions()
  fetchUsers()
})
</script>