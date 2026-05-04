<template>
  <Navbar />
  <router-view></router-view>
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
</style>