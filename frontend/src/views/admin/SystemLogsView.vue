<template>
  <div class="container-fluid py-4">
    <div class="row align-items-center mb-4 gap-3 gap-md-0">
      <div class="col-12 col-md-6">
        <h4 class="fw-bold mb-0 text-primary">
          <i class="bi bi-journal-text me-2"></i>ประวัติการใช้งานระบบ (System Logs)
        </h4>
      </div>
      <div class="col-12 col-md-4 ms-auto">
        <div class="input-group shadow-sm">
          <span class="input-group-text bg-white border-end-0 rounded-start-pill">
            <i class="bi bi-search text-muted"></i>
          </span>
          <input 
            type="text" 
            class="form-control border-start-0 rounded-end-pill focus-ring focus-ring-light" 
            v-model="searchQuery" 
            placeholder="ค้นหา ผู้ทำรายการ, โมดูล หรือรายละเอียด..."
          >
        </div>
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-bordered mb-0 align-middle text-center">
          <thead class="table-light text-nowrap">
            <tr>
              <th width="15%" class="py-3">วัน/เวลา</th>
              <th width="20%">ผู้ทำรายการ</th>
              <th width="15%">โมดูล</th>
              <th width="15%">การกระทำ</th>
              <th width="35%" class="text-start">รายละเอียด</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="5" class="py-5 text-muted">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div>กำลังโหลดข้อมูลประวัติการใช้งาน...</div>
              </td>
            </tr>
            
            <tr v-else-if="filteredLogs.length === 0">
              <td colspan="5" class="py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                ไม่พบข้อมูลที่ตรงกับการค้นหา
              </td>
            </tr>

            <tr v-else v-for="log in filteredLogs" :key="log.id">
              <td class="text-muted text-nowrap"><small>{{ formatDate(log.created_at) }}</small></td>
              <td class="fw-bold text-dark">{{ log.full_name || 'System' }}</td>
              <td>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-1 rounded-pill">
                  {{ log.module }}
                </span>
              </td>
              <td>
                <span :class="getActionBadgeClass(log.action)">{{ log.action }}</span>
              </td>
              <td class="text-start text-break">{{ log.description }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../../services/api'; // ปรับ Path ให้ตรงกับโปรเจกต์ของคุณ
import Swal from 'sweetalert2';

const logs = ref([]);
const searchQuery = ref('');
const isLoading = ref(true);

// ดึงข้อมูลจาก API
const fetchLogs = async () => {
  isLoading.value = true;
  try {
    const token = localStorage.getItem('token');
    const response = await api.get('?route=admin/logs/get', {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    // ชี้ไปที่ response.data.data ตามโครงสร้างที่ Backend ส่งมา
    if (response.data && response.data.success) {
      logs.value = response.data.data || [];
    } else {
      logs.value = [];
    }
  } catch (error) {
    console.error("Error fetching logs:", error);
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลประวัติการใช้งานได้', 'error');
  } finally {
    isLoading.value = false;
  }
};

// ค้นหาข้อมูล (Filter) แบบปลอดภัย (ป้องกันค่า null)
const filteredLogs = computed(() => {
  if (!searchQuery.value) return logs.value;
  const query = searchQuery.value.toLowerCase();
  
  return logs.value.filter(log => {
    const fullName = (log.full_name || 'System').toLowerCase();
    const moduleName = (log.module || '').toLowerCase();
    const actionName = (log.action || '').toLowerCase();
    const desc = (log.description || '').toLowerCase();
    
    return moduleName.includes(query) ||
           actionName.includes(query) ||
           desc.includes(query) ||
           fullName.includes(query);
  });
});

// จัดรูปแบบวันที่ (พ.ศ. ภาษาไทย)
const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString('th-TH', { 
    year: 'numeric', month: 'short', day: 'numeric', 
    hour: '2-digit', minute: '2-digit', second: '2-digit' 
  }) + ' น.';
};

// ตกแต่งสีป้ายสถานะ (Badge) ตาม Action
const getActionBadgeClass = (action) => {
  if (!action) return 'badge bg-dark rounded-pill px-3';
  
  switch (action.toUpperCase()) {
    case 'LOGIN': return 'badge bg-info text-dark rounded-pill px-3 py-1 border';
    case 'CREATE': return 'badge bg-success rounded-pill px-3 py-1';
    case 'UPDATE': return 'badge bg-warning text-dark rounded-pill px-3 py-1';
    case 'DELETE': return 'badge bg-danger rounded-pill px-3 py-1';
    case 'APPROVE': return 'badge bg-primary rounded-pill px-3 py-1';
    case 'CANCEL': return 'badge bg-danger bg-opacity-75 rounded-pill px-3 py-1';
    default: return 'badge bg-dark rounded-pill px-3 py-1';
  }
};

onMounted(() => {
  fetchLogs();
});
</script>