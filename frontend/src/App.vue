<template>
  <Navbar />
  <router-view></router-view>
</template>
<script setup>
import Navbar from './components/Navbar.vue'
</script>

<style>
/* ใส่ Global CSS ของคุณตรงนี้ได้ (ถ้ามี) */
body {
  background-color: #f8f9fa;
}
</style>