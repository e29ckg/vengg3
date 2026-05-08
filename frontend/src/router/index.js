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
      component: LoginView 
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
      meta: { requiresAuth: true, role: 9 }
    },
    {
      path: '/admin/settings/system',
      name: 'SystemSettings',
      component: () => import('../views/admin/SystemSettingsView.vue'),
      meta: { requiresAuth: true, role: 9 }
    },
    {
      path: '/admin/settings/agency',
      name: 'AgencySettings',
      component: () => import('../views/admin/AgencySettingsView.vue'),
      meta: { requiresAuth: true, role: 9 }
    },

    // 🌟 เพิ่ม Route สำหรับตั้งค่า Google Calendar ตรงนี้ครับ
    {
      path: '/admin/settings/google-calendar',
      name: 'GoogleCalendarSettings',
      component: () => import('../views/admin/GoogleCalendarSettingsView.vue'),
      meta: { requiresAuth: true, role: 9 } // เฉพาะ Admin เท่านั้น
    },

    {
      path: '/admin/settings/telegram',
      name: 'TelegramSettings',
      component: () => import('../views/admin/TelegramSettingsView.vue'),
      meta: { requiresAuth: true, role: 9 }
    },
    { 
      path: '/director/ven-settings', 
      name: 'ven-settings', 
      component: () => import('../views/VenSettingView.vue') ,
      meta: { requiresAuth: true }
    },
    { 
      path: '/director/commands', 
      name: 'commands', 
      component: () => import('../views/VenCommandView.vue'), 
      meta: { requiresAuth: true }
    },
    { 
      path: '/director/schedule', 
      name: 'schedule', 
      component: () => import('../views/VenScheduleView.vue'), 
      meta: { requiresAuth: true }
    },
    {
      path: '/director/schedule-list',
      name: 'ven-schedule-list',
      component: () => import('../views/VenScheduleListView.vue'),
      meta: { requiresAuth: true }
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
        meta: { requiresAuth: true }
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
      meta: { requiresAuth: true }
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/admin/options',
      name: 'options',
      component: () => import('../views/AdminOptionsView.vue'),
      meta: { requiresAuth: true }
    },
  ]
})

export default router