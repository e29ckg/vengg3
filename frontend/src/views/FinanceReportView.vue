<template>
  <div class="container-fluid mt-4">
    <div class="card shadow-sm mb-4 d-print-none">
      <div class="card-header bg-success text-white">
        <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>พิมพ์หลักฐานการจ่ายเงิน (การเงิน)</h4>
      </div>
      <div class="card-body">
        <div class="row align-items-end">
          <div class="col-md-3">
            <label class="form-label">เลือกเดือน</label>
            <select class="form-select" v-model="selectedMonthValue" @change="updateSelectedMonth">
              <option v-for="(month, index) in thaiMonths" :key="index" :value="String(index + 1).padStart(2, '0')">
                {{ month }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">เลือกปี (พ.ศ.)</label>
            <select class="form-select" v-model="selectedYearValue" @change="updateSelectedMonth">
              <option v-for="year in availableYears" :key="year" :value="year">
                {{ year + 543 }}
              </option>
            </select>
          </div>
          <div class="col-md-6 text-end">
            <button class="btn btn-outline-success me-2" @click="exportToExcel" :disabled="!reportData.length">
              <i class="bi bi-file-earmark-excel"></i> ส่งออก Excel
            </button>
            <button class="btn btn-primary" @click="window.print()" :disabled="!reportData.length">
              <i class="bi bi-printer"></i> พิมพ์เอกสาร
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="printable-document" v-if="reportData.length > 0">
      <div class="text-center mb-3 doc-header">
        <h5 class="fw-bold">หลักฐานการจ่ายเงินตอบแทนการปฏิบัติงานนอกเวลาราชการ</h5>
        <h6 class="fw-bold">ส่วนราชการ ศาลจังหวัดประจวบคีรีขันธ์</h6>
        <h6 class="fw-bold">ประจำเดือน {{ formattedMonth }}</h6>
      </div>

      <table class="table table-bordered border-dark table-sm align-middle text-center print-table">
        <thead>
          <tr class="align-middle bg-light">
            <th width="4%">ลำดับ</th>
            <th width="15%">ชื่อ - สกุล</th>
            <th width="12%">ตำแหน่ง</th>
            <th width="6%">จำนวน<br>(วัน)</th>
            <th width="8%">อัตรา<br>(บาท/วัน)</th>
            <th width="8%">จำนวนเงิน<br>(บาท)</th>
            <th width="15%">วัน เดือน ปี<br>ที่ปฏิบัติงาน</th>
            <th width="12%">ลายมือชื่อ<br>ผู้รับเงิน</th>
            <th width="10%">วัน เดือน ปี<br>ที่รับเงิน</th>
            <th width="10%">หมายเหตุ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in reportData" :key="index">
            <td>{{ index + 1 }}</td>
            <td class="text-start px-2">{{ item.user_name }}</td>
            <td class="text-start px-2">{{ item.role_name }}</td>
            <td>{{ item.total_days }}</td>
            <td>{{ item.rate_per_day }}</td>
            <td class="fw-bold">{{ (item.total_days * item.rate_per_day).toLocaleString() }}</td>
            <td class="text-start px-2" style="font-size: 0.85rem;">{{ item.work_dates }}</td>
            <td></td> <td></td> <td>{{ item.remark || '' }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="fw-bold bg-light">
            <td colspan="5" class="text-end pe-3">รวมเงินทั้งสิ้น</td>
            <td>{{ totalAmount.toLocaleString() }}</td>
            <td colspan="4"></td>
          </tr>
        </tfoot>
      </table>

      <div class="row mt-5 text-center doc-footer">
        <div class="col-4">
          <p>ลงชื่อ......................................................ผู้จ่ายเงิน</p>
          <p>(......................................................)</p>
          <p>ตำแหน่ง......................................................</p>
        </div>
        <div class="col-4">
          <p>ลงชื่อ......................................................ผู้ตรวจสอบ</p>
          <p>(......................................................)</p>
          <p>ตำแหน่ง......................................................</p>
        </div>
        <div class="col-4">
          <p>ลงชื่อ......................................................ผู้อนุมัติ</p>
          <p>(......................................................)</p>
          <p>ตำแหน่ง......................................................</p>
        </div>
      </div>
    </div>
    
    <div v-else-if="!loading" class="alert alert-warning text-center d-print-none mt-4">
      ไม่พบข้อมูลการปฏิบัติงานในเดือนที่เลือก
    </div>
    <div v-if="loading" class="text-center mt-4 d-print-none">
      กำลังโหลดข้อมูล...
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import api from '../services/api'
import axios from 'axios';

const selectedMonthValue = ref('');
const selectedYearValue = ref('');
const selectedMonth = ref('');
const reportData = ref([]);
const loading = ref(false);

const thaiMonths = [
  "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", 
  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

const availableYears = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let i = currentYear - 5; i <= currentYear + 1; i++) {
    years.push(i);
  }
  return years;
});

const formattedMonth = computed(() => {
  if (!selectedMonthValue.value || !selectedYearValue.value) return '';
  const monthIndex = parseInt(selectedMonthValue.value) - 1;
  const thaiYear = parseInt(selectedYearValue.value) + 543;
  return `${thaiMonths[monthIndex]} พ.ศ. ${thaiYear}`;
});

const totalAmount = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (item.total_days * item.rate_per_day), 0);
});

const updateSelectedMonth = () => {
  if (selectedYearValue.value && selectedMonthValue.value) {
    selectedMonth.value = `${selectedYearValue.value}-${selectedMonthValue.value}`;
    fetchFinanceReport();
  }
};

onMounted(() => {
  const today = new Date();
  selectedMonthValue.value = String(today.getMonth() + 1).padStart(2, '0');
  selectedYearValue.value = today.getFullYear();
  updateSelectedMonth();
});

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
    // ข้อมูลจำลองสำหรับทดสอบหน้าตา UI
    reportData.value = [
      { user_name: 'นายกฤษฎา ใจดี', role_name: 'ผู้พิพากษา', total_days: 4, rate_per_day: 1200, work_dates: '1, 8, 15, 22', remark: '' },
      { user_name: 'นางสาววิมล ขยันยิ่ง', role_name: 'เจ้าหน้าที่รับฟ้อง', total_days: 5, rate_per_day: 420, work_dates: '2, 3, 10, 17, 24', remark: '' }
    ]; 
  } finally {
    loading.value = false;
  }
};

const exportToExcel = () => {
  alert('คุณสามารถเชื่อมฟังก์ชัน Export Excel ได้ในภายหลังครับ');
};
</script>

<style scoped>
/* ตั้งค่าเอกสารบนหน้าเว็บปกติ */
.printable-document {
  background: white;
  padding: 20px;
  border-radius: 8px;
}
.print-table th, .print-table td {
  border: 1px solid #000 !important; /* บังคับขอบตารางสีดำเข้ม */
}

@media print {
  @page {
    size: A4 landscape; /* บังคับพิมพ์แนวนอน */
    margin: 10mm; /* ตั้งค่าขอบกระดาษ */
  }
  
  body * {
    visibility: hidden; /* ซ่อนทุกอย่างก่อน */
  }
  
  .printable-document, .printable-document * {
    visibility: visible; /* แสดงเฉพาะส่วนเอกสาร */
  }
  
  .printable-document {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 0;
  }

  .print-table {
    font-size: 12pt; /* ปรับขนาดตัวอักษรสำหรับพิมพ์ */
  }
  
  .print-table th, .print-table td {
    padding: 4px;
  }

  .doc-header h5, .doc-header h6 {
    font-size: 14pt;
    margin-bottom: 5px;
  }
}
</style>