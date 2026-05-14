<template>
  <div id="app" class="d-flex flex-column min-vh-100">
    <Navbar v-if="!$route.meta.hideNavbar" />

    <main class="flex-grow-1" :style="{ paddingBottom: $route.meta.hideFooter ? '0' : '60px' }">
      <router-view />
    </main>

    <footer v-if="!$route.meta.hideFooter" class="footer-fixed bg-white border-top py-2 shadow-sm d-print-none">
      <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
          <div class="text-muted small">
            <span class="fw-bold text-primary"><i class="bi bi-calendar2-check"></i> ระบบบริหารจัดการเวรนอกเวลาทำการ</span> 
            <span class="ms-2 opacity-50">|</span>
            <span class="ms-2">Version 3.0.1</span>
            <router-link to="/manual" class="ms-2 text-decoration-none text-secondary">
              <i class="bi bi-question-circle me-1"></i>คู่มือการใช้งาน
            </router-link>
          </div>
          <div class="developer-credit small">
            <span class="text-muted">Developed with ❤️ by</span>
            <span class="fw-bold text-dark ms-1">ศาลเยาวชนและครอบครัวจังหวัดประจวบคีรีขันธ์</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>


<script setup>
import Navbar from './components/Navbar.vue'
</script>

<style>
/* 🌟 กำหนดให้ Bootstrap และระบบทั้งหมดใช้ฟอนต์ Sarabun */
:root {
  --bs-font-sans-serif: 'Sarabun', sans-serif;
  --bs-body-font-family: 'Sarabun', sans-serif;
}

body {
  font-family: 'Sarabun', sans-serif !important;
  background-color: #f8f9fa;
}

/* 🌟 ปรับขนาดตัวอักษรให้เหมาะสม (ฟอนต์ Sarabun จะดูเล็กกว่าฟอนต์ปกติเล็กน้อย) */
p, span, div, td, th, input, select, button {
  font-size: 1rem; 
}

h1, h2, h3, h4, h5, h6 {
  font-family: 'Sarabun', sans-serif !important;
}

/* 🌟 สไตล์สำหรับ Footer ให้ติดอยู่ด้านล่างตลอดเวลา */
.footer-fixed {
  position: fixed;
  bottom: 0;
  width: 100%;
  z-index: 1030; /* ให้อยู่เหนือเนื้อหาอื่นๆ */
}

/* ปรับแต่งความสวยงามของตัวอักษร */
.developer-credit span {
  font-size: 0.75rem;
}

@media (max-width: 576px) {
  .footer-fixed .d-flex {
    flex-direction: column;
    text-align: center;
    gap: 5px;
  }
}
</style>