<template>
  <div class="container mt-4">
    <div class="card shadow-sm">
      <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>ระบบออกรายงาน - สำหรับงานการเงิน</h4>
      </div>
      
      <div class="card-body">
        <div class="row mb-4">
          <div class="col-md-4">
            <label class="form-label">เลือกเดือนที่ต้องการออกรายงาน</label>
                <div class="d-flex gap-2">
                    <select class="form-select" v-model="selectedMonthValue" @change="updateSelectedMonth">
                        <option v-for="(month, index) in thaiMonths" :key="index" :value="String(index + 1).padStart(2, '0')">
                        {{ month }}
                        </option>
                    </select>
                    
                    <select class="form-select" v-model="selectedYearValue" @change="updateSelectedMonth">
                        <option v-for="year in availableYears" :key="year" :value="year">
                        {{ year + 543 }}
                        </option>
                    </select>
                </div>
          </div>
          <div class="col-md-8 d-flex align-items-end justify-content-end">
            <button class="btn btn-outline-success me-2" @click="exportToExcel" :disabled="!reportData.length">
              <i class="bi bi-file-earmark-excel"></i> ส่งออกเป็น Excel
            </button>
            <button class="btn btn-primary" @click="window.print()" :disabled="!reportData.length">
              <i class="bi bi-printer"></i> พิมพ์เอกสาร
            </button>
          </div>
        </div>

        <div class="table-responsive d-print-block" id="printableArea">
          <div class="d-none d-print-block text-center mb-3">
            <h5>รายงานสรุปการปฏิบัติหน้าที่เวรนอกเวลาทำการ</h5>
            <p>ประจำเดือน: {{ formattedMonth }}</p>
          </div>
          
          <table class="table table-bordered table-hover text-center">
            <thead class="table-light">
              <tr>
                <th>ลำดับ</th>
                <th>ชื่อ-นามสกุล</th>
                <th>ตำแหน่ง/หน้าที่</th>
                <th>จำนวนเวรที่ปฏิบัติ (ครั้ง)</th>
                <th>หมายเหตุ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-3">กำลังโหลดข้อมูล...</td>
              </tr>
              <tr v-else-if="reportData.length === 0">
                <td colspan="5" class="text-center py-3">ไม่พบข้อมูลการขึ้นเวรในเดือนที่เลือก</td>
              </tr>
              <tr v-else v-for="(item, index) in reportData" :key="index">
                <td>{{ index + 1 }}</td>
                <td class="text-start">{{ item.user_name }}</td>
                <td>{{ item.role_name }}</td>
                <td>{{ item.total_shifts }}</td>
                <td>{{ item.remark || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api';
// ถ้ามีการใช้ไลบรารีส่งออก Excel ให้ import เข้ามา เช่น
// import * as XLSX from 'xlsx';

const selectedMonth = ref(''); // ตัวแปรเก็บค่า YYYY-MM สำหรับส่ง API 
const selectedMonthValue = ref(''); // ค่าเดือนที่เลือกจาก Dropdown (01-12)
const selectedYearValue = ref('');  // ค่าปี ค.ศ. ที่เลือกจาก Dropdown
const reportData = ref([]);
const loading = ref(false);
// รายชื่อเดือนภาษาไทย
const thaiMonths = [
  "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", 
  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

// คำนวณปีที่ให้เลือก (ย้อนหลัง 5 ปี จนถึงปีถัดไป 1 ปี)
const availableYears = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 1; i++) {
    years.push(i);
  }
  return years;
});

// เมื่อผู้ใช้เปลี่ยนเดือนหรือปีจาก Dropdown ให้ประกอบร่างกลับเป็น YYYY-MM แล้วเรียก API
const updateSelectedMonth = () => {
  if (selectedYearValue.value && selectedMonthValue.value) {
    selectedMonth.value = `${selectedYearValue.value}-${selectedMonthValue.value}`;
    fetchFinanceReport();
  }
};

// ตั้งค่าเดือน/ปีปัจจุบันเมื่อเปิดหน้ามา
onMounted(() => {
  const today = new Date();
  selectedMonthValue.value = String(today.getMonth() + 1).padStart(2, '0');
  selectedYearValue.value = today.getFullYear();
  
  // สร้างค่า YYYY-MM เริ่มต้น และเรียกข้อมูลทันที
  updateSelectedMonth();
});

// แปลงเดือนให้อ่านง่ายสำหรับตอนพิมพ์ (ดึงจาก dropdown ได้เลย)
const formattedMonth = computed(() => {
  if (!selectedMonthValue.value || !selectedYearValue.value) return '';
  const monthIndex = parseInt(selectedMonthValue.value) - 1;
  const thaiYear = parseInt(selectedYearValue.value) + 543;
  return `${thaiMonths[monthIndex]} พ.ศ. ${thaiYear}`;
});

// ฟังก์ชันดึงข้อมูลจาก Backend (คงเดิม)
const fetchFinanceReport = async () => {
  if (!selectedMonth.value) return;
  loading.value = true;
  try {
    const response = await api.get(`?route=finance/report&month=${selectedMonth.value}`);
    if (response.data && response.data.status === 'success') {
      reportData.value = response.data.data;
    } else {
      reportData.value = [];
    }
  } catch (error) {
    console.error("Error fetching finance report:", error);
    reportData.value = []; 
  } finally {
    loading.value = false;
  }
};

// ฟังก์ชันส่งออกเป็น Excel
const exportToExcel = () => {
  alert('คุณสามารถเชื่อมฟังก์ชัน exportToExcel ด้วยไลบรารี xlsx แบบเดียวกับที่ใช้ในหน้า รายการการจัดเวร ได้เลยครับ!');
  // ตัวอย่างโค้ดถ้าใช้ xlsx:
  // const ws = XLSX.utils.json_to_sheet(reportData.value);
  // const wb = XLSX.utils.book_new();
  // XLSX.utils.book_append_sheet(wb, ws, "Finance_Report");
  // XLSX.writeFile(wb, `รายงานการเงิน_${selectedMonth.value}.xlsx`);
};
</script>

<style scoped>
@media print {
  /* ซ่อนปุ่มและ Navbar เวลาสั่งพิมพ์ */
  .btn, input, label {
    display: none !important;
  }
  .card {
    border: none !important;
    box-shadow: none !important;
  }
  .card-header {
    display: none !important;
  }
}
</style>