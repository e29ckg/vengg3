// frontend/src/services/api.js
import axios from 'axios';
import router from '../router'; // เพิ่มบรรทัดนี้ เพื่อใช้สั่งเปลี่ยนหน้าไปที่ login

// ดึงค่า URL มาจากไฟล์ .env
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: import.meta.env.VITE_API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json'
  }
});

// สร้าง Interceptor เพื่อแนบ Token ให้อัตโนมัติทุกครั้งที่ยิง API
apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// --- เพิ่ม Response Interceptor สำหรับดักจับ Token หมดอายุ ตรงนี้ครับ ---
apiClient.interceptors.response.use(
  (response) => {
    // ถ้า Request สำเร็จ (Status 200) ปล่อยผ่านไปตามปกติ
    return response;
  },
  (error) => {
    if (error.response) {
      const status = error.response.status;
      const errorMessage = error.response.data?.error; 

      // เช็คว่า Status เป็น 401 หรือ ข้อความ Error ตรงกับที่ตั้งไว้
      if (status === 401 || errorMessage === "Access denied. Invalid or expired token.") {
        
        // 1. ล้างค่า Token เดิมทิ้ง
        localStorage.removeItem('token'); 
        localStorage.removeItem('user');
        
        // (ทางเลือก) คุณสามารถใส่แจ้งเตือนตรงนี้ได้ เช่น alert หรือ Swal.fire
        // alert('เซสชันของคุณหมดอายุ กรุณาเข้าสู่ระบบใหม่อีกครั้ง');

        // 2. เด้งกลับไปหน้า Login
        router.push('/login'); 
      }
    }
    // ส่ง Error กลับไปให้ component เผื่อมีการจัดการ try-catch ต่อ
    return Promise.reject(error);
  }
);
// -----------------------------------------------------------------

export default apiClient;