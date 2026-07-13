# ==========================================
# Stage 1: Build Stage (ใช้ Node.js)
# ==========================================
FROM node:20-alpine as build-stage

# กำหนดโฟลเดอร์ทำงานใน Container
WORKDIR /app

# คัดลอก package.json และ package-lock.json (ถ้ามี)
COPY package*.json ./

# ติดตั้ง Dependencies
RUN npm install

# คัดลอกไฟล์โปรเจกต์ทั้งหมด
COPY . .

# สั่ง Build โปรเจกต์ (Vite จะสร้างโฟลเดอร์ /dist)
RUN npm run build

# ==========================================
# Stage 2: Production Stage (ใช้ Nginx)
# ==========================================
FROM nginx:alpine as production-stage

# คัดลอกไฟล์ที่ Build เสร็จแล้วจาก Stage 1 ไปไว้ใน Nginx
COPY --from=build-stage /app/dist /usr/share/nginx/html

# คัดลอกไฟล์ตั้งค่า Nginx (เราจะสร้างในขั้นตอนต่อไป)
COPY nginx.conf /etc/nginx/conf.d/default.conf

# เปิดพอร์ต 80
EXPOSE 80

# สั่งรัน Nginx
CMD ["nginx", "-g", "daemon off;"]