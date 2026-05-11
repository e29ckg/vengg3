<template>
  <div class="container mt-4 mb-5">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-body p-4 p-md-5">
        <div class="text-center mb-5">
          <h2 class="fw-bold text-primary"><i class="bi bi-book-half me-2"></i>คู่มือการใช้งานระบบ</h2>
          <p class="text-muted">ระบบบริหารจัดการเวรนอกเวลาทำการ (Shift Management System)</p>
        </div>

        <div class="row g-4">
          <div class="col-md-6 col-lg-3">
            <div class="h-100 p-4 border rounded-4 bg-white shadow-sm hover-up">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-primary-subtle text-primary rounded-3 p-2 me-3">
                  <i class="bi bi-person-badge fs-4"></i>
                </div>
                <h5 class="fw-bold mb-0">เจ้าหน้าที่ทั่วไป</h5>
              </div>
              <ul class="list-unstyled small lh-lg text-secondary">
                <li><i class="bi bi-check2-circle me-2 text-primary"></i>ตรวจสอบตารางเวรในปฏิทิน</li>
                <li><i class="bi bi-check2-circle me-2 text-primary"></i>ขอยกเวรให้เพื่อน (โอนขาด)</li>
                <li><i class="bi bi-check2-circle me-2 text-primary"></i>ขอสลับเวร (แลกเปลี่ยน)</li>
                <li><i class="bi bi-check2-circle me-2 text-primary"></i>ติดตามสถานะการอนุมัติ</li>
                <li><i class="bi bi-check2-circle me-2 text-primary"></i>ดาวน์โหลดใบเปลี่ยนเวร (Word)</li>
              </ul>
            </div>
          </div>

          <div class="col-md-6 col-lg-3">
            <div class="h-100 p-4 border rounded-4 bg-white shadow-sm hover-up">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-success-subtle text-success rounded-3 p-2 me-3">
                  <i class="bi bi-calendar-check fs-4"></i>
                </div>
                <h5 class="fw-bold mb-0">งานอำนวยการ</h5>
              </div>
              <ul class="list-unstyled small lh-lg text-secondary">
                <li><i class="bi bi-check2-circle me-2 text-success"></i>สร้างคำสั่งจัดเวรรายเดือน</li>
                <li><i class="bi bi-check2-circle me-2 text-success"></i>จัดเวรแบบ Drag & Drop</li>
                <li><i class="bi bi-check2-circle me-2 text-success"></i>อนุมัติ/ไม่อนุมัติ การเปลี่ยนเวร</li>
                <li><i class="bi bi-check2-circle me-2 text-success"></i>พิมพ์ตารางเวรภาพรวม</li>
              </ul>
            </div>
          </div>

          <div class="col-md-6 col-lg-3">
            <div class="h-100 p-4 border rounded-4 bg-white shadow-sm hover-up">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-info-subtle text-info rounded-3 p-2 me-3">
                  <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <h5 class="fw-bold mb-0">งานการเงิน</h5>
              </div>
              <ul class="list-unstyled small lh-lg text-secondary">
                <li><i class="bi bi-check2-circle me-2 text-info"></i>ตรวจสอบสรุปยอดการอยู่เวร</li>
                <li><i class="bi bi-check2-circle me-2 text-info"></i>เรียกดูรายงานรายบุคคล</li>
                <li><i class="bi bi-check2-circle me-2 text-info"></i>ส่งออกรายงานสรุปเงิน (Excel)</li>
                <li><i class="bi bi-check2-circle me-2 text-info"></i>ตรวจสอบวันหยุดราชการ</li>
              </ul>
            </div>
          </div>

          <div class="col-md-6 col-lg-3">
            <div class="h-100 p-4 border rounded-4 bg-dark shadow-sm hover-up text-white">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-box bg-warning text-dark rounded-3 p-2 me-3">
                  <i class="bi bi-shield-lock fs-4"></i>
                </div>
                <h5 class="fw-bold mb-0 text-warning">ผู้ดูแลระบบ</h5>
              </div>
              <ul class="list-unstyled small lh-lg opacity-75">
                <li><i class="bi bi-check2-circle me-2 text-warning"></i>จัดการ User และระงับสิทธิ์</li>
                <li><i class="bi bi-check2-circle me-2 text-warning"></i>ลากสลับลำดับอาวุโส (srt)</li>
                <li><i class="bi bi-check2-circle me-2 text-warning"></i>ตั้งค่า Google / Telegram</li>
                <li><i class="bi bi-check2-circle me-2 text-warning"></i>กำหนดกฎการเปลี่ยนเวร</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="mt-5 p-4 rounded-4 bg-light">
          <h5 class="fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>ข้อแนะนำเพิ่มเติม</h5>
          <div class="row mt-3">
            <div class="col-md-6">
              <div class="d-flex mb-2">
                <i class="bi bi-1-circle-fill text-primary me-2"></i>
                <span class="small">การเปลี่ยนเวรต้องทำล่วงหน้าตามจำนวนวันที่แอดมินกำหนดในระบบ</span>
              </div>
              <div class="d-flex mb-2">
                <i class="bi bi-2-circle-fill text-primary me-2"></i>
                <span class="small">ระบบจะตรวจสอบกฎการทำงานติดต่อกัน 24 ชั่วโมงให้อัตโนมัติ</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex mb-2">
                <i class="bi bi-3-circle-fill text-primary me-2"></i>
                <span class="small">เมื่อแอนมินอนุมัติการเปลี่ยนเวร ข้อมูลจะซิงค์เข้า Google Calendar ทันที</span>
              </div>
              <div class="d-flex mb-2">
                <i class="bi bi-4-circle-fill text-primary me-2"></i>
                <span class="small">หากไฟล์ Word ข้อมูลไม่ตรง ให้ตรวจสอบการตั้งค่า "ข้อมูลหน่วยงาน"</span>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-5 text-center">
          <p class="text-muted small">หากพบปัญหาการใช้งานด้านเทคนิค โปรดติดต่อ <br>
            <span class="fw-bold text-dark">ศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hover-up {
  transition: all 0.3s ease;
}
.hover-up:hover {
  transform: translateY(-5px);
  border-color: var(--bs-primary) !important;
}
.icon-box {
  width: 45px;
  height: 45px;
  display: flex;
  align-items: center;
  justify-content: center;
}
</style>