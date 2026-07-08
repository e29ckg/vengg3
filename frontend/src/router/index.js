import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { 
      path: '/', 
      redirect: '/login' 
    },
    { 
      path: '/login', 
      name: 'login', 
      component: LoginView ,
      meta: { guestOnly: true }
    },
    { 
      path: '/home', 
      name: 'home', 
      component: () => import('../views/HomeView.vue') 
    },
    { 
      path: '/admin/users', 
      name: 'admin-users', 
      component: () => import('../views/UserManagementView.vue'),
      meta: { requiresAuth: true, roles: [9] } // 🌟 แก้เป็น roles (มี s)
    },
    {
      path: '/admin/settings/system',
      name: 'SystemSettings',
      component: () => import('../views/admin/SystemSettingsView.vue'),
      meta: { requiresAuth: true, roles: [9] } // 🌟 แก้เป็น roles (มี s)
    },
    {
      path: '/admin/settings/agency',
      name: 'AgencySettings',
      component: () => import('../views/admin/AgencySettingsView.vue'),
      meta: { requiresAuth: true, roles: [9] } // 🌟 แก้เป็น roles (มี s)
    },
    {
      path: '/admin/settings/google-calendar',
      name: 'GoogleCalendarSettings',
      component: () => import('../views/admin/GoogleCalendarSettingsView.vue'),
      meta: { requiresAuth: true, roles: [9] } // 🌟 แก้เป็น roles (มี s)
    },
    {
      path: '/admin/settings/telegram',
      name: 'TelegramSettings',
      component: () => import('../views/admin/TelegramSettingsView.vue'),
      meta: { requiresAuth: true, roles: [9] } // 🌟 แก้เป็น roles (มี s)
    },
    {
      path: '/admin/logs',
      name: 'system-logs',
      component: () => import('../views/admin/SystemLogsView.vue'),
      meta: { requiresAuth: true, roles: [9] } // ให้เฉพาะแอดมินเข้าได้
    },
    { 
      path: '/director/ven-settings', 
      name: 'ven-settings', 
      component: () => import('../views/VenSettingView.vue') ,
      meta: { requiresAuth: true, roles: [2, 9] } // 🌟 เติมสิทธิ์
    },
    { 
      path: '/director/commands', 
      name: 'commands', 
      component: () => import('../views/VenCommandView.vue'), 
      meta: { requiresAuth: true, roles: [2, 9] } // 🌟 เติมสิทธิ์
    },
    { 
      path: '/director/schedule', 
      name: 'schedule', 
      component: () => import('../views/VenScheduleView.vue'), 
      meta: { requiresAuth: true , hideFooter: true, roles: [2, 9]} // 🌟 เติมสิทธิ์
    },
    {
      path: '/director/schedule-list',
      name: 'ven-schedule-list',
      component: () => import('../views/VenScheduleListView.vue'),
      meta: { requiresAuth: true, roles: [2, 9] } // 🌟 เติมสิทธิ์
    },
    {
      path: '/report/personal',
      name: 'personal-report',
      component: () => import('../views/PersonalReportView.vue'),
      meta: { requiresAuth: true, roles: [2, 9] }
    },
    {
      path: '/director/approvals',
      name: 'ven-approvals',
      component: () => import('../views/VenApproveView.vue'),
      meta: { requiresAuth: true, roles: [2, 9] }
    },
    {
        path: '/staff/swap',
        name: 'staff-swap',
        component: () => import('../views/VenSwapView.vue'),
        meta: { requiresAuth: true } // พนักงานทั่วไปเข้าได้
    },
    {
      path: '/finance/report',
      name: 'finance-report',
      component: () => import('../views/FinanceReportView.vue'),
      meta: { requiresAuth: true , roles: [3, 9] }
    },
    {
      path: '/user/history',
      name: 'ven-history',
      component: () => import('../views/VenChangeHistoryView.vue'),
      meta: { requiresAuth: true } // พนักงานทั่วไปเข้าได้
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true } // พนักงานทั่วไปเข้าได้
    },
    {
      path: '/admin/options',
      name: 'options',
      component: () => import('../views/AdminOptionsView.vue'),
      meta: { requiresAuth: true, roles: [9] } // 🌟 เติมสิทธิ์ (เดิมลืมใส่)
    },
    {
      path: '/manual',
      name: 'manual',
      component: () => import('../views/UserManualView.vue')
    },
    {
      path: '/:pathMatch(.*)*',
      name: 'not-found',
      component: () => import('../views/NotFoundView.vue')
    }
  ]
})

// 🌟 โค้ดดักจับก่อนเปลี่ยนหน้า (เหมือนเดิม ทำงานถูกต้องแล้วเมื่อคีย์ตรงกัน)
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token'); 
  const role = localStorage.getItem('role'); 
  let userRole = null;

  if (role) {
    userRole = Number(role); 
  }

  // 1. ถ้าหน้าที่จะไป ต้องล็อกอิน แต่ไม่มี Token
  if (to.meta.requiresAuth && !token) {
    return next('/login');
  }

  // 2. ถ้ามี Token แล้ว แต่พยายามเข้าหน้า Login อีก
  if (to.meta.guestOnly && token) {
    return next('/home');
  }

  // 3. 🌟 เช็คสิทธิ์ (Roles) ว่าเข้าหน้านี้ได้ไหม
  if (to.meta.roles && to.meta.roles.length > 0) {
    if (!to.meta.roles.includes(userRole)) {
      // ถ้าสิทธิ์ไม่ตรง (เช่น user ปกติมาเข้าหน้า admin) ให้เด้งกลับไปหน้า Home
      return next('/home'); 
    }
  }

  // ผ่านทุกเงื่อนไข ให้ไปต่อได้
  next();
});

export default router