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

    <div class="mt-5">
        <h4 class="fw-bold mb-4 text-dark border-bottom pb-2">
          <i class="bi bi-journal-bookmark-fill text-primary me-2"></i>ขั้นตอนการทำงาน (Step-by-Step)
        </h4>

        <div class="accordion shadow-sm" id="manualAccordion">

          <div class="accordion-item border-0 border-bottom mb-2">
            <h2 class="accordion-header" id="headingOne">
              <button class="accordion-button fw-bold bg-light rounded-top" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                <i class="bi bi-tools text-success me-2 mt-1"></i> 1. การเตรียมข้อมูลเวรก่อนเริ่มจัดตาราง (สำหรับแอดมิน)
              </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#manualAccordion">
              <div class="accordion-body text-secondary lh-lg pt-4 pb-4 bg-white">
                <p class="fw-bold text-dark">ก่อนที่จะเริ่มจัดตารางเวรในแต่ละเดือน แอดมินต้องทำการเตรียมข้อมูลพื้นฐานให้ครบถ้วนตามลำดับดังนี้:</p>
                <div class="d-flex mb-3">
                  <div class="me-3 fw-bold text-primary">ขั้นตอน 1</div>
                  <div><strong>ตรวจสอบรายชื่อเจ้าหน้าที่:</strong> ไปที่เมนู <code>จัดการผู้ใช้งาน</code> ตรวจสอบให้แน่ใจว่ามีรายชื่อครบถ้วน และได้ลากจัดลำดับอาวุโส (srt) เรียบร้อยแล้ว</div>
                </div>
                <div class="d-flex mb-3">
                  <div class="me-3 fw-bold text-primary">ขั้นตอน 2</div>
                  <div><strong>ตั้งค่าประเภทเวรหลัก:</strong> ไปที่เมนู <code>ตั้งค่าเวร</code> เพื่อกำหนดชื่อเวรหลัก เช่น เวรกลางวัน (08.30-16.30), เวรกลางคืน (16.30-08.30) หรือ nightCourt</div>
                </div>
                <div class="d-flex mb-3">
                  <div class="me-3 fw-bold text-primary">ขั้นตอน 3</div>
                  <div><strong>กำหนดหน้าที่ย่อยและค่าตอบแทน:</strong> ในหน้าตั้งค่าเวร ให้กดเข้าไปเพิ่ม "หน้าที่ย่อย" (เช่น ผู้พิพากษา, ผู้อำนวยการ, เจ้าหน้าที่รับฟ้อง) พร้อมกำหนดสีประจำตำแหน่งและราคาค่าตอบแทนให้เรียบร้อย</div>
                </div>
                <div class="d-flex mb-3">
                  <div class="me-3 fw-bold text-primary">ขั้นตอน 4</div>
                  <div><strong>สร้างคำสั่งจัดเวรประจำเดือน:</strong> ไปที่เมนู <code>งานอำนวยการ > คำสั่งจัดเวร</code> กดเพิ่มคำสั่งใหม่ กรอกเลขที่คำสั่ง วันที่ลงนาม และเลือกเดือนที่จะจัดตาราง</div>
                </div>
                <div class="d-flex mb-0">
                  <div class="me-3 fw-bold text-primary">ขั้นตอน 5</div>
                  <div><strong>เริ่มจัดเวร:</strong> เมื่อสร้างคำสั่งเสร็จแล้ว ให้คลิกปุ่ม <span class="badge bg-primary">จัดตารางเวร</span> ในรายการคำสั่งนั้น เพื่อเข้าสู่หน้าปฏิทิน และเริ่มลากวางรายชื่อได้เลย!</div>
                </div>
              </div>
            </div>
          </div>

          <div class="accordion-item border-0 border-bottom mb-2">
            <h2 class="accordion-header" id="headingTwo">
              <button class="accordion-button collapsed fw-bold bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                <i class="bi bi-arrow-left-right text-info me-2 mt-1"></i> 2. วิธีการขอยกเวรและการขอสลับเวร (สำหรับเจ้าหน้าที่)
              </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#manualAccordion">
              <div class="accordion-body text-secondary lh-lg pt-4 pb-4 bg-white">
                <ul>
                  <li class="mb-2"><strong>วิธียกเวร (โอนขาด):</strong> คลิกที่ชื่อของตนเองในปฏิทิน > เลือกปุ่ม "ยกเวรนี้ให้ผู้อื่น" > ค้นหาชื่อเพื่อนที่ต้องการให้มาเข้าเวรแทน > กดยืนยัน (เพื่อนคนนั้นจะได้รับเวรไปเลย)</li>
                  <li><strong>วิธีสลับเวร (แลกเปลี่ยน):</strong> คลิกที่ชื่อของตนเองในปฏิทิน > เลือกปุ่ม "ขอสลับเวร" > เลือกเวรของเพื่อนในเดือนเดียวกันที่ต้องการไปเข้าแทน > กดยืนยัน (เป็นการแลกวันเข้าเวรกัน)</li>
                </ul>
                <div class="alert alert-warning mt-3 mb-0 small border-0">
                  <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>หมายเหตุ:</strong> ระบบจะไม่อนุญาตให้แลกเปลี่ยนเวร หากพบว่าทำให้เกิดการทำงานติดต่อกัน 24 ชั่วโมง (เช่น ลงเวรดึก แล้วต่อเวรเช้าทันที)
                </div>
              </div>
            </div>
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