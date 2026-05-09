<template>
  <div class="container mt-5 pt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 text-center">
        <div class="mb-4">
          <i class="bi bi-exclamation-triangle text-warning" style="font-size: 6rem; opacity: 0.8;"></i>
        </div>
        
        <h1 class="display-1 fw-bold text-dark mb-2" style="letter-spacing: 2px;">404</h1>
        <h3 class="fw-bold text-secondary mb-3">ไม่พบหน้าเว็บ (Page Not Found)</h3>
        
        <p class="text-muted mb-5 lh-lg">
          ขออภัย หน้าเว็บที่คุณกำลังค้นหาไม่มีอยู่ในระบบ<br>
          คุณอาจพิมพ์ URL ผิด หรือหน้านี้อาจถูกย้าย/ลบไปแล้ว
        </p>
        
        <router-link to="/home" class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm transition-hover">
          <i class="bi bi-house-door-fill me-2"></i>กลับสู่หน้าหลัก
        </router-link>
        
        <div class="mt-4 pt-3 text-muted small">
          <a href="#" @click.prevent="$router.go(-1)" class="text-decoration-none text-secondary">
            <i class="bi bi-arrow-left me-1"></i> ย้อนกลับไปหน้าก่อนหน้า
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// ไม่จำเป็นต้องใช้ Script พิเศษสำหรับหน้านี้
</script>

<style scoped>
.transition-hover {
  transition: all 0.3s ease-in-out;
}
.transition-hover:hover {
  transform: translateY(-2px);
  box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>