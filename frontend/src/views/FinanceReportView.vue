<template>
  <div class="container-fluid mt-4">
    <div class="card shadow-sm mb-4 d-print-none">
      <div class="card-header bg-success text-white">
        <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i>พิมพ์หลักฐานการจ่ายเงิน (การเงิน)</h4>
      </div>
      <div class="card-body">
        <div class="row align-items-end">
  <div class="col-md-2">
    <label class="form-label">เลือกเดือน</label>
    <select class="form-select" v-model="selectedMonthValue" @change="updateSelectedMonth">
      <option v-for="(month, index) in thaiMonths" :key="index" :value="String(index + 1).padStart(2, '0')">
        {{ month }}
      </option>
    </select>
  </div>
  
  <div class="col-md-2">
    <label class="form-label">เลือกปี (พ.ศ.)</label>
    <select class="form-select" v-model="selectedYearValue" @change="updateSelectedMonth">
      <option v-for="year in availableYears" :key="year" :value="year">
        {{ year + 543 }}
      </option>
    </select>
  </div>

  <div class="col-md-4">
    <label class="form-label">เลือกคำสั่ง</label>
    <select class="form-select" v-model="selectedCommand" @change="fetchFinanceReport" :disabled="loadingCommands">
      <option value="">-- ทั้งหมด / ไม่ระบุ --</option>
      <option v-for="cmd in commandList" :key="cmd.id" :value="cmd.id">
        {{cmd.com_num}} {{cmd.name}}
      </option>
    </select>
  </div>
  <div class="col-md-4 text-end">
    <button class="btn btn-outline-success me-2" @click="exportToExcel" :disabled="!reportData.length">
      <i class="bi bi-file-earmark-excel"></i> ส่งออก Excel
    </button>
    <button @click="printDocument" class="btn btn-primary d-print-none">
      <i class="bi bi-printer"></i> พิมพ์เอกสาร
    </button>
  </div>
</div>
      </div>
    </div>

    <div class="printable-document" v-if="reportData.length > 0">
      <div class="text-center mb-3 doc-header">
        <h5 class="fw-bold" contenteditable="true">หลักฐานการจ่ายเงินตอบแทนการปฏิบัติงานนอกเวลาราชการ</h5>
        <h6 class="fw-bold" contenteditable="true">ส่วนราชการ {{agencyConfig.agency_name}} ประจำเดือน {{ formattedMonth }}</h6>
        <h6 class="fw-bold" contenteditable="true"> คำสั่ง{{agencyConfig.agency_name}} ที่ {{  commandList.find(cmd => cmd.id === selectedCommand)?.com_num }} {{ commandList.find(cmd => cmd.id === selectedCommand)?.name }}</h6>
      </div>

      <table class="table table-bordered border-dark table-sm align-middle text-center print-table">
        <thead>
          <tr class="align-middle bg-light text-center">
            <th rowspan="2" width="4%">ลำดับ</th>
            <th rowspan="2" width="15%">ชื่อ - สกุล</th>
            <th rowspan="2" width="8%">อัตรา<br>(บาท/วัน)</th>
            
            <th :colspan="monthInfo.total_days" class="py-2">วันที่ปฏิบัติงานนอกเวลาราชการ</th>
            
            <th colspan="3">ระยะเวลาปฏิบัติงาน</th>

            <th rowspan="2" width="8%">จำนวนเงิน<br>(บาท)</th>
            <th rowspan="2" width="8%">วัน เดือน ปี<br>ที่รับเงิน</th>
            <th rowspan="2" width="5%">ลายมือชื่อ<br>ผู้รับเงิน</th>
            <th rowspan="2" width="5%">หมายเหตุ</th>
          </tr>
          <tr class="align-middle bg-light text-center">
            <th v-for="index in monthInfo.total_days" :key="index" 
                class="px-1" 
                :class="{ 'table-secondary': monthInfo.holidays && monthInfo.holidays.includes(index) }"
                style="font-size: 0.8rem; min-width: 22px;">
              {{ index }}
            </th>
            <th style="font-size: 0.85rem;">วันปกติ</th>
            <th style="font-size: 0.85rem;">วันหยุด</th>
            <th style="font-size: 0.85rem;">ชั่วโมง</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in reportData" :key="index">
            <td>{{ index + 1 }}</td>
            <td contenteditable="true" class="text-start px-2">{{item.prefix_name }}{{ item.first_name }} {{ item.last_name }}</td>
            <td contenteditable="true">{{ item.rate_per_day }}</td>

            <td contenteditable="true" v-for="day in monthInfo.total_days" :key="day" 
                class="p-0 align-middle"
                :class="{ 'table-secondary': monthInfo.holidays && monthInfo.holidays.includes(day) }">
              <span  v-if="checkWorkDay(item.work_dates, day)" class="fw-bold text-dark">&#10003;</span>
            </td>
            
            <td contenteditable="true">{{ countRegularDays(item.work_dates, item.total_days) || '-' }}</td>
            <td contenteditable="true">{{ countHolidays(item.work_dates) || '-' }}</td>
            <td contenteditable="true"></td>
            
            <td class="fw-bold" contenteditable="true">{{ (item.total_days * item.rate_per_day).toLocaleString() }}</td>
            <td class="text-start px-2" style="font-size: 0.85rem;" contenteditable="true"></td>
            <td contenteditable="true"></td> <td contenteditable="true">{{ item.remark || '' }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="fw-bold bg-light">
            <td :colspan="6 + monthInfo.total_days" class="text-end pe-3" contenteditable="true">รวมเงินทั้งสิ้น ({{ numberToThaiBaht(totalAmount) }})</td>
            <td contenteditable="true">{{ totalAmount.toLocaleString() }}</td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>

      <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
          <p class="mb-2" contenteditable="true">(ลงชื่อ).......................................................ผู้จ่ายเงิน</p>
          <p class="mb-1 fw-bold " contenteditable="true">({{ getActiveSigner('finances').name }})</p>
          <p class="small text-muted " contenteditable="true">{{ getActiveSigner('finances').position }}</p>
        </div>
        <div class="col-6">
          <p class="mb-2" contenteditable="true">(ลงชื่อ).......................................................ผู้อนุมัติ</p>
          <p class="mb-1 fw-bold " contenteditable="true">({{ getActiveSigner('admins').name }})</p>
          <p class="small text-muted " contenteditable="true">{{ getActiveSigner('admins').position }}</p>
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
import api from '../services/api'; // ใช้ api ที่คุณตั้งค่าไว้
import * as XLSX from 'xlsx';

const selectedMonthValue = ref('');
const selectedYearValue = ref('');
const selectedMonth = ref('');
const reportData = ref([]);
const loading = ref(false);

// 1. เพิ่มตัวแปรสำหรับจัดการ "คำสั่ง"
const selectedCommand = ref('');
const commandList = ref([]);
const loadingCommands = ref(false);

const monthInfo = ref({ total_days: 0, holidays: [] });

const thaiMonths = [
  "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", 
  "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];

const agencyConfig = ref({
  agency_name: '',
  directors: [],
  admins: [],
  finances: []
})

const getActiveSigner = (type) => {
  if (!agencyConfig.value[type] || agencyConfig.value[type].length === 0) {
    return { name: '.......................................', position: '..............................' }
  }
  return agencyConfig.value[type].find(s => s.is_active) || agencyConfig.value[type][0]
}

// 3. ฟังก์ชันดึงข้อมูลหน่วยงานจาก Database
const fetchAgencyConfig = async () => {
  try {
    const res = await api.get('?route=admin/agency_settings')
    if (res.data) {
      agencyConfig.value = res.data
    }
  } catch (error) {
    console.error("Error fetching agency config:", error)
  }
}

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

// 2. เพิ่มฟังก์ชันสำหรับดึงรายการคำสั่งของเดือนนั้นๆ
const fetchCommandsList = async () => {
  loadingCommands.value = true;
  selectedCommand.value = ''; // รีเซ็ตค่าเมื่อเปลี่ยนเดือน
  try {
    // หมายเหตุ: ปรับ route=... ให้ตรงกับ API ที่ดึงข้อมูลคำสั่งของคุณ
    const response = await api.get(`?route=get_commands&month=${selectedMonth.value}`);
    if (response.data && response.data.status === 'success') {
      commandList.value = response.data.data;
    } else {
      commandList.value = [];
    }
  } catch (error) {
    console.error("Error fetching commands:", error);
    // ข้อมูลจำลองสำหรับทดสอบ (ลบออกได้เมื่อ API เสร็จ)
    /*
    commandList.value = [
      { id: 1, name: 'คำสั่งที่ 123/2567' },
      { id: 2, name: 'คำสั่งที่ 124/2567' }
    ];
    */
  } finally {
    loadingCommands.value = false;
  }
};

// 3. ปรับฟังก์ชันอัปเดตเดือน ให้ดึงคำสั่งก่อน แล้วค่อยดึงรายงาน
const updateSelectedMonth = async () => {
  if (selectedYearValue.value && selectedMonthValue.value) {
    selectedMonth.value = `${selectedYearValue.value}-${selectedMonthValue.value}`;
    await fetchCommandsList(); // รอให้ดึงรายการคำสั่งเสร็จก่อน
    fetchFinanceReport();      // จากนั้นดึงข้อมูลตารางรายงาน
  }
};

// ฟังก์ชันสำหรับเช็คว่าวันนั้นตรงกับวันที่ขึ้นเวรหรือไม่
const checkWorkDay = (workDatesString, day) => {
  if (!workDatesString) return false;
  
  // แปลงข้อความ "1, 8, 15" ให้เป็น Array ของตัวเลข [1, 8, 15]
  const workedDays = workDatesString.split(',').map(d => parseInt(d.trim()));
  
  // ตรวจสอบว่าวันที่กำลังวาด (day) มีอยู่ใน Array หรือไม่
  return workedDays.includes(day);
};

onMounted(() => {
  const today = new Date();
  selectedMonthValue.value = String(today.getMonth() + 1).padStart(2, '0');
  selectedYearValue.value = today.getFullYear();
  updateSelectedMonth();
  fetchAgencyConfig();
});

// 4. อัปเดตการดึงข้อมูลรายงานให้แนบ command_id ไปด้วย
const fetchFinanceReport = async () => {
  if (!selectedMonth.value) return;
  loading.value = true;
  try {
    // สร้าง URL โดยเช็คว่ามีการเลือกคำสั่งหรือไม่
    let url = `?route=finance/report&month=${selectedMonth.value}`;
    if (selectedCommand.value) {
      url += `&command_id=${selectedCommand.value}`;
    }

    const response = await api.get(url);
    if (response.data && response.data.status === 'success') {
      // แยกเก็บข้อมูล
      monthInfo.value = response.data.data.month_info;
      reportData.value = response.data.data.report_list;
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

// 1. ฟังก์ชันนับจำนวน "วันหยุด" ที่มาขึ้นเวร
const countHolidays = (workDatesString) => {
  if (!workDatesString || !monthInfo.value.holidays) return 0;
  
  // แปลง "1, 8, 15" เป็น Array [1, 8, 15]
  const workedDays = workDatesString.split(',').map(d => parseInt(d.trim()));
  
  // กรองหาเฉพาะวันที่มาทำงาน และตรงกับวันหยุด
  const holidaysWorked = workedDays.filter(day => monthInfo.value.holidays.includes(day));
  
  return holidaysWorked.length; // คืนค่าจำนวนวันหยุดที่มาทำงาน
};

// 2. ฟังก์ชันนับจำนวน "วันปกติ" ที่มาขึ้นเวร
const countRegularDays = (workDatesString, totalDays) => {
  // วันปกติ = วันทำงานทั้งหมด ลบด้วย วันหยุด
  const holidaysCount = countHolidays(workDatesString);
  return totalDays - holidaysCount;
};

// 🌟 ฟังก์ชันแปลงตัวเลขเป็นคำอ่านภาษาไทย (Thai Baht Text)
const numberToThaiBaht = (number) => {
  if (isNaN(number) || number === null || number === 0) return "ศูนย์บาทถ้วน";
  
  let numStr = Number(number).toFixed(2);
  let [integerPart, decimalPart] = numStr.split(".");
  
  const numbers = ["ศูนย์", "หนึ่ง", "สอง", "สาม", "สี่", "ห้า", "หก", "เจ็ด", "แปด", "เก้า"];
  const units = ["", "สิบ", "ร้อย", "พัน", "หมื่น", "แสน", "ล้าน"];
  
  const convert = (str) => {
    let text = "";
    let len = str.length;
    for (let i = 0; i < len; i++) {
      let n = parseInt(str[i]);
      let unit = len - i - 1;
      if (n === 0) continue;
      
      if (unit === 1 && n === 1) {
        text += "สิบ";
      } else if (unit === 1 && n === 2) {
        text += "ยี่สิบ";
      } else if (unit === 0 && n === 1 && len > 1 && str[i-1] !== '0') {
        text += "เอ็ด";
      } else {
        text += numbers[n] + units[unit % 6];
      }
    }
    return text;
  };

  let bahtText = integerPart === "0" ? "ศูนย์" : convert(integerPart);
  bahtText += "บาท";

  if (decimalPart === "00") {
    bahtText += "ถ้วน";
  } else {
    bahtText += convert(decimalPart) + "สตางค์";
  }

  return bahtText;
};

const printDocument = () => {
  // คำสั่งนี้จะเปิดหน้าต่าง Print ของ Browser (Chrome, Edge, Safari) ขึ้นมา
  window.print();
};

const exportToExcel = () => {
  if (!reportData.value.length) return;

  const totalDays = monthInfo.value.total_days || 31;
  const aoa = []; // Array of Arrays สำหรับเก็บข้อมูลแต่ละแถว

  const finance = getActiveSigner('finances');
  const admin = getActiveSigner('admins');
  const director = getActiveSigner('directors');

  // ==========================================
  // 1. ส่วนที่เพิ่มใหม่: หัวเอกสาร (แทรก 4 บรรทัด)
  // ==========================================
  aoa.push(['หลักฐานการจ่ายเงินตอบแทนการปฏิบัติงานนอกเวลาราชการ']);
  aoa.push([`ส่วนราชการ ${agencyConfig.value.agency_name} ประจำเดือน ${formattedMonth.value}`]); // แก้ไขชื่อหน่วยงานได้ตามต้องการ
  aoa.push([`คำสั่ง${agencyConfig.value.agency_name} ที่ ${commandList.value.find(cmd => cmd.id === selectedCommand.value)?.com_num } ${commandList.value.find(cmd => cmd.id === selectedCommand.value)?.name }`]);
  aoa.push([]); // บรรทัดว่าง 1 บรรทัดก่อนเริ่มตาราง

  // --- แถวที่ 1: หัวตารางชั้นบน ---
  const headerRow1 = ['ลำดับ', 'ชื่อ - สกุล', 'อัตรา (บาท/วัน)'];
  // เพิ่มช่อง "วันที่ปฏิบัติงานนอกเวลาราชการ" (ใช้ช่องแรกช่องเดียว นอกนั้นปล่อยว่างไว้เพื่อ Merge)
  for (let i = 1; i <= totalDays; i++) {
    headerRow1.push(i === 1 ? 'วันที่ปฏิบัติงานนอกเวลาราชการ' : '');
  }
  // เพิ่มช่อง "ระยะเวลาปฏิบัติงาน" (กินพื้นที่ 3 คอลัมน์)
  headerRow1.push('ระยะเวลาปฏิบัติงาน', '', '');
  // คอลัมน์ที่เหลือ
  headerRow1.push('จำนวนเงิน (บาท)', 'วัน เดือน ปี ที่รับเงิน', 'ลายมือชื่อผู้รับเงิน', 'หมายเหตุ');
  aoa.push(headerRow1);

  // --- แถวที่ 2: หัวตารางชั้นล่าง (ตัวเลขวัน และประเภทวัน) ---
  const headerRow2 = ['', '', '']; // ปล่อยว่าง 3 ช่องแรกเพื่อ Merge ลงมา
  for (let i = 1; i <= totalDays; i++) {
    headerRow2.push(i.toString());
  }
  headerRow2.push('วันปกติ', 'วันหยุด', 'ชั่วโมง');
  headerRow2.push('', '', '', ''); // ปล่อยว่างคอลัมน์ท้ายๆ เพื่อ Merge ลงมา
  aoa.push(headerRow2);

  // --- แถวข้อมูล (Data Rows) ---
  reportData.value.forEach((item, index) => {
    const rowData = [
      index + 1,
      `${item.prefix_name}${item.first_name} ${item.last_name}`,
      item.rate_per_day
    ];

    // ติ๊กถูกในวันที่ทำงาน (ใน Excel ใช้เครื่องหมาย ✓)
    for (let day = 1; day <= totalDays; day++) {
      rowData.push(checkWorkDay(item.work_dates, day) ? '✓' : '');
    }

    // จำนวนวัน/ชั่วโมง
    rowData.push(
      countRegularDays(item.work_dates, item.total_days) || '-',
      countHolidays(item.work_dates) || '-',
      '' // ชั่วโมง
    );

    // จำนวนเงิน และช่องว่างสำหรับเซ็นชื่อ
    rowData.push(
      item.total_days * item.rate_per_day, 
      '', // วันที่รับเงิน
      '', // ลายมือชื่อ
      item.remark || ''
    );

    aoa.push(rowData);
  });

  // --- แถวสรุปยอดรวมท้ายตาราง (Footer) ---
const footerRow = [];
  
  // 1. คอลัมน์แรก (ซ้ายสุด) ใส่ข้อความรวมเงินและคำอ่านภาษาไทย
  footerRow.push(`รวมเงินทั้งสิ้น (${numberToThaiBaht(totalAmount.value)})`);

  // 2. ดันช่องว่างไปจนถึงก่อนช่อง "จำนวนเงิน (บาท)"
  // (สมมติว่าช่องจำนวนเงินอยู่คอลัมน์ที่ 3 + totalDays + 2)
  for (let i = 1; i < 3 + totalDays + 3; i++) {
    footerRow.push('');
  }

  // 3. ใส่ยอดเงินตัวเลขในคอลัมน์ "จำนวนเงิน (บาท)"
  footerRow.push(totalAmount.value);

  // 4. ใส่ช่องว่างสำหรับคอลัมน์ที่เหลือ (วันที่รับเงิน, ลายมือชื่อ, หมายเหตุ)
  footerRow.push('', '', '');

  // นำแถวนี้ใส่เข้าไปในตาราง
  aoa.push(footerRow);

  // 🌟 เก็บหมายเลขแถวสุดท้ายนี้ไว้ เพื่อนำไปผสานเซลล์ (Merge)
  const footerRowIndex = aoa.length - 1;

  
  // ต่อท้ายด้วยส่วนลงนาม (Footer)
  aoa.push([]); // บรรทัดว่าง
  aoa.push([
    "", "", // ดันช่องให้ไปอยู่ตรงกลางตารางสวยๆ
    `(ลงชื่อ).......................................................ผู้จ่ายเงิน`,
    "", "",
    `(ลงชื่อ).......................................................ผู้อนุมัติ`
  ]);
  aoa.push([
    "", "",
    `(${finance.name})`,
    "", "",
    `(${admin.name})`
  ]);
  aoa.push([
    "", "",
    `${finance.position}`,
    "", "",
    `${admin.position}`
  ]);

  // --- สร้าง Worksheet ---
  const ws = XLSX.utils.aoa_to_sheet(aoa);

  // ==========================================
  // 2. กำหนดการผสานเซลล์ (Merge Cells) ฉบับอัปเดต
  // ==========================================
  const offset = 4; // จำนวนบรรทัดที่เราแทรกเพิ่มไปด้านบน (เพื่อให้ตารางขยับลงมา)
  const totalCols = 3 + totalDays + 3 + 3; // ตำแหน่ง index ของคอลัมน์ขวาสุด

  const merges = [
    // --- ผสานเซลล์หัวเอกสาร (คลุมตั้งแต่ซ้ายสุดไปขวาสุดของตาราง) ---
    { s: { r: 0, c: 0 }, e: { r: 0, c: totalCols } }, // ชื่อรายงาน
    { s: { r: 1, c: 0 }, e: { r: 1, c: totalCols } }, // ชื่อหน่วยงาน
    { s: { r: 2, c: 0 }, e: { r: 2, c: totalCols } }, // ประจำเดือน

    // --- ผสานเซลล์ตาราง (บวก offset เพิ่มเข้าไปในรหัสแถว r) ---
    { s: { r: offset + 0, c: 0 }, e: { r: offset + 1, c: 0 } }, // ลำดับ
    { s: { r: offset + 0, c: 1 }, e: { r: offset + 1, c: 1 } }, // ชื่อ-สกุล
    { s: { r: offset + 0, c: 2 }, e: { r: offset + 1, c: 2 } }, // อัตรา
    { s: { r: offset + 0, c: 3 }, e: { r: offset + 0, c: 3 + totalDays - 1 } }, // วันที่ปฏิบัติงาน
    { s: { r: offset + 0, c: 3 + totalDays }, e: { r: offset + 0, c: 3 + totalDays + 2 } }, // ระยะเวลา
    { s: { r: offset + 0, c: 3 + totalDays + 3 }, e: { r: offset + 1, c: 3 + totalDays + 3 } }, // จำนวนเงิน
    { s: { r: offset + 0, c: 3 + totalDays + 4 }, e: { r: offset + 1, c: 3 + totalDays + 4 } }, // วันที่รับเงิน
    { s: { r: offset + 0, c: 3 + totalDays + 5 }, e: { r: offset + 1, c: 3 + totalDays + 5 } }, // ลายมือชื่อ
    { s: { r: offset + 0, c: 3 + totalDays + 6 }, e: { r: offset + 1, c: 3 + totalDays + 6 } }, // หมายเหตุ
    
    // ผสานเซลล์แถว Footer "รวมเงินทั้งสิ้น"
    { s: { r: aoa.length - 1, c: 0 }, e: { r: aoa.length - 1, c: 3 + totalDays + 2 } } 
  ];
// นำค่า footerRowIndex มาใช้กำหนดจุดผสานเซลล์
  merges.push({ 
    s: { r: footerRowIndex, c: 0 }, // เริ่มจากคอลัมน์ที่ 0 (ซ้ายสุด)
    e: { r: footerRowIndex, c: 3 + totalDays + 2 } // ไปสิ้นสุดที่คอลัมน์ก่อนช่องจำนวนเงิน
  });
  ws['!merges'] = merges;


  // --- สร้าง Workbook และสั่งดาวน์โหลด ---
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "รายงานการเงิน");
  
  // ตั้งชื่อไฟล์ตามเดือนที่เลือก
  XLSX.writeFile(wb, `รายงานการเงิน_${selectedMonth.value}.xlsx`);
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

.document-area {
  background: white;
  padding: 20px;
}

/* =========================================
   ตั้งค่าเฉพาะเวลาสั่งพิมพ์เอกสาร (@media print)
   ========================================= */
@media print {
  /* 1. ตั้งค่าหน้ากระดาษ */
  @page {
    size: A4 landscape; /* ใช้ portrait สำหรับแนวตั้ง หรือ landscape สำหรับแนวนอน */
    margin: 1.5cm; /* ระยะขอบกระดาษ */
  }

  /* 2. ซ่อนสิ่งที่ไม่ต้องการให้ติดไปในกระดาษ */
  /* .d-print-none,  */
  .d-print-none *,
  nav, 
  .sidebar,
  button {
    display: none !important;
  }

  /* 3. บังคับให้แสดงสีพื้นหลัง (เช่น สีไฮไลต์แถวตาราง) */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  /* 4. ป้องกันไม่ให้ตารางถูกตัดขาดครึ่งแถวเวลาขึ้นหน้าใหม่ */
  table {
    page-break-inside: auto;
  }
  tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }
  
  /* 5. ปรับขนาดตัวอักษรสำหรับพิมพ์ให้พอดีอ่าน */
  body {
    font-size: 12pt;
    color: black;
  }
}
</style>