// frontend/src/services/api.js
import axios from 'axios';

// ดึงค่า URL มาจากไฟล์ .env
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  timeout: import.meta.env.VITE_API_TIMEOUT,
  headers: {
    'Content-Type': 'application/json'
  }
});

// สร้าง Interceptor เพื่อแนบ Token ให้อัตโนมัติทุกครั้งที่ยิง API (เราจะได้ไม่ต้องพิมพ์บรรทัด Header ซ้ำๆ)
apiClient.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default apiClient;