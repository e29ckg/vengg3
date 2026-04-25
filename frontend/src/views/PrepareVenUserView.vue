<template>
  <div class="bg-light min-vh-100">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold fs-4" href="#"><i class="bi bi-person-gear me-2"></i>เตรียมผู้อยู่เวร</a>
        <router-link to="/home" class="btn btn-outline-light btn-sm rounded-pill px-3">กลับหน้าหลัก</router-link>
      </div>
    </nav>

    <div class="container py-5">
      <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-people-fill me-2"></i>กำหนดรายชื่อผู้มีสิทธิ์ลงเวร</h3>

      <div class="row g-4">
        <div class="col-md-6" v-for="sub in venUserData" :key="sub.id">
          <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
              <div>
                <small class="text-muted d-block">{{ sub.main_name }}</small>
                <h5 class="fw-bold mb-0 text-primary">{{ sub.sub_name }}</h5>
              </div>
              <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm" @click="openAddUserModal(sub)">
                <i class="bi bi-plus-lg me-1"></i>เพิ่มคน
              </button>
            </div>
            
            <div class="card-body p-4">
              <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                <div v-for="u in sub.users" :key="u.vu_id" class="list-group-item d-flex justify-content-between align-items-center py-2 px-3 hover-bg">
                  <div>
                    <i class="bi bi-person-circle me-2 text-secondary"></i>
                    <span class="fw-semibold">{{ u.prefix }}{{ u.name }} {{ u.sname }}</span>
                  </div>
                  <button class="btn btn-link text-danger p-0" @click="removeUser(u.vu_id)">
                    <i class="bi bi-x-circle-fill"></i>
                  </button>
                </div>
                <div v-if="sub.users.length === 0" class="list-group-item text-center py-4 text-muted small">
                  ยังไม่ได้กำหนดรายชื่อพนักงาน
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="userPickerModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
          <div class="modal-header border-0 pb-0">
            <h5 class="modal-title fw-bold">เลือกพนักงานสำหรับ: {{ activeSub?.sub_name }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeUserModal"></button>
          </div>
          <div class="modal-body p-4">
            <div class="input-group mb-3 shadow-sm border rounded-pill overflow-hidden">
              <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control border-0" v-model="userSearch" placeholder="พิมพ์ชื่อเพื่อค้นหา...">
            </div>
            
            <div class="list-group list-group-flush border rounded-3 overflow-auto" style="max-height: 300px;">
              <button v-for="user in filteredUsers" :key="user.id" 
                      class="list-group-item list-group-item-action py-2"
                      @click="addUserToSub(user.id)">
                <i class="bi bi-plus-circle me-2 text-success"></i>
                {{ user.full_name }}
              </button>
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

const venUserData = ref([])
const allUsers = ref([])
const activeSub = ref(null)
const userSearch = ref('')
let userModal = null

const fetchVenUserList = async () => {
  const res = await api.get('?route=admin/ven_user&action=list')
  venUserData.value = res.data
}

const fetchAllUsers = async () => {
  const res = await api.get('?route=admin/user/list')
  allUsers.value = res.data
}

const filteredUsers = computed(() => {
  if (!userSearch.value) return allUsers.value
  return allUsers.value.filter(u => u.full_name.includes(userSearch.value))
})

const openAddUserModal = (sub) => {
  activeSub.value = sub
  userSearch.value = ''
  userModal.show()
}

const addUserToSub = async (userId) => {
  await api.post('?route=admin/ven_user&action=add', { sub_id: activeSub.value.id, user_id: userId })
  fetchVenUserList()
  userModal.hide()
}

const removeUser = async (vu_id) => {
  const result = await Swal.fire({ title: 'ยืนยันการลบ?', icon: 'warning', showCancelButton: true })
  if (result.isConfirmed) {
    await api.post('?route=admin/ven_user&action=remove', { vu_id })
    fetchVenUserList()
  }
}

onMounted(() => {
  fetchVenUserList()
  fetchAllUsers()
  userModal = new Modal(document.getElementById('userPickerModal'))
})
</script>

<style scoped>
.hover-bg:hover { background-color: #f8f9fa; }
</style>