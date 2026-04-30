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
      // โหลดหน้า Home แบบ Lazy load (โหลดเมื่อใช้งาน) เพื่อให้เว็บเร็วขึ้น
      component: () => import('../views/HomeView.vue') 
    },
    { 
      path: '/admin/users', 
      name: 'admin-users', 
      component: () => import('../views/UserManagementView.vue') 
    },
    { 
      path: '/admin/settings', 
      name: 'admin-settings', 
      component: () => import('../views/SystemSettingsView.vue') 
    },
    { 
      path: '/admin/agency-config', 
      name: 'agency-config', 
      component: () => import('../views/AgencyConfigView.vue') 
    },
    { 
      path: '/director/ven-settings', 
      name: 'ven-settings', 
      component: () => import('../views/VenSettingView.vue') 
    },
    
    { 
      path: '/director/commands', 
      name: 'commands', 
      component: () => import('../views/VenCommandView.vue') 
    },
    { 
      path: '/director/schedule', 
      name: 'schedule', 
      component: () => import('../views/VenScheduleView.vue') 
    },
    {
      path: '/director/approvals',
      name: 'ven-approvals',
      component: () => import('../views/VenApproveView.vue'),
      meta: { requiresAuth: true, roles: [2, 9] } // อนุญาตเฉพาะสิทธิ์ 2 (อำนวยการ) และ 9 (แอดมิน)
    },
    {
        path: '/staff/swap',
        name: 'staff-swap',
        component: () => import('../views/VenSwapView.vue')
    },
    {
      path: '/director/schedule-list',
      name: 'ven-schedule-list',
      component: () => import('../views/VenScheduleListView.vue')
    },
    {
      path: '/finance/report',
      name: 'finance-report',
      component: () => import('../views/FinanceReportView.vue'),
      meta: { requiresAuth: true , roles: [3, 9] } // อนุญาตเฉพาะสิทธิ์ 3 (การเงิน)
    }

  ]
})

export default router