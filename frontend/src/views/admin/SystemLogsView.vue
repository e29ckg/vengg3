<template>
  <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold mb-0 text-primary">
        <i class="bi bi-journal-text me-2"></i>ประวัติการใช้งานระบบ (System Logs)
      </h4>
      <div class="w-25">
        <input type="text" class="form-control rounded-pill" v-model="searchQuery" placeholder="ค้นหา โมดูล, การกระทำ หรือ ชื่อผู้ใช้...">
      </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-bordered mb-0 align-middle text-center">
          <thead class="table-light">
            <tr>
              <th width="15%">วัน/เวลา</th>
              <th width="20%">ผู้ทำรายการ</th>
              <th width="10%">โมดูล</th>
              <th width="10%">การกระทำ</th>
              <th width="35%" class="text-start">รายละเอียด</th>
              <th width="10%">IP Address</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredLogs.length === 0">
              <td colspan="6" class="py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                ยังไม่มีข้อมูลประวัติการใช้งานระบบ
              </td>
            </tr>
            <tr v-for="log in filteredLogs" :key="log.id">
              <td class="text-muted"><small>{{ formatDate(log.created_at) }}</small></td>
              <td class="fw-bold text-dark">{{ log.first_name || 'System' }} {{ log.last_name || '' }}</td>
              <td>
                <span class="badge bg-secondary rounded-pill px-3">{{ log.module }}</span>
              </td>
              <td>
                <span :class="getActionBadgeClass(log.action)">{{ log.action }}</span>
              </td>
              <td class="text-start">{{ log.description }}</td>
              <td class="text-muted"><small>{{ log.ip_address || '-' }}</small></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const logs = ref([]);
const searchQuery = ref('');

// ดึงข้อมูลจาก API
const fetchLogs = async () => {
  try {
    const token = localStorage.getItem('token');
    const response = await axios.get('/public/index.php?route=admin/logs/get', {
      headers: { Authorization: `Bearer ${token}` }
    });
    logs.value = response.data;
  } catch (error) {
    console.error("Error fetching logs:", error);
    Swal.fire('ข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลประวัติการใช้งานได้', 'error');
  }
};

// ค้นหาข้อมูล (Filter)
const filteredLogs = computed(() => {
  if (!searchQuery.value) return logs.value;
  const query = searchQuery.value.toLowerCase();
  return logs.value.filter(log => {
    const fullName = `${log.first_name || ''} ${log.last_name || ''}`.toLowerCase();
    return log.module.toLowerCase().includes(query) ||
           log.action.toLowerCase().includes(query) ||
           log.description.toLowerCase().includes(query) ||
           fullName.includes(query);
  });
});

// จัดรูปแบบวันที่
const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleString('th-TH', { 
    year: 'numeric', month: 'short', day: 'numeric', 
    hour: '2-digit', minute: '2-digit', second: '2-digit' 
  });
};

// ตกแต่งสีป้ายสถานะ (Badge) ตาม Action
const getActionBadgeClass = (action) => {
  switch (action.toUpperCase()) {
    case 'LOGIN': return 'badge bg-info text-dark rounded-pill px-3';
    case 'CREATE': return 'badge bg-success rounded-pill px-3';
    case 'UPDATE': return 'badge bg-warning text-dark rounded-pill px-3';
    case 'DELETE': return 'badge bg-danger rounded-pill px-3';
    case 'APPROVE': return 'badge bg-primary rounded-pill px-3';
    default: return 'badge bg-dark rounded-pill px-3';
  }
};

onMounted(() => {
  fetchLogs();
});
</script>