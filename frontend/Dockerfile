# ใช้ Node.js เวอร์ชันล่าสุด
FROM node:lts-alpine

# ตั้งค่าโฟลเดอร์ทำงานภายใน Container
WORKDIR /app

# คัดลอก package.json เพื่อติดตั้ง dependencies ก่อน (ช่วยให้ build เร็วขึ้นถ้าไม่มีการแก้ไฟล์นี้)
COPY package*.json ./

# ติดตั้ง dependencies
RUN npm install

# คัดลอกโค้ดทั้งหมดของ Frontend เข้าไป
COPY . .

# เปิดพอร์ต 5173 (พอร์ตเริ่มต้นของ Vite/Vue)
EXPOSE 5173

# สั่งรันคำสั่ง npm run dev โดยเพิ่ม --host เพื่อให้เข้าถึงจากภายนอก Container ได้
CMD ["npm", "run", "dev", "--", "--host"]