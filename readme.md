# 🗓️ Vengg3 - ระบบจัดการเวรนอกเวลาทำการ

ระบบบริหารจัดการการอยู่เวรนอกเวลาราชการ พัฒนาขึ้นเพื่อช่วยอำนวยความสะดวกในการจัดตารางเวร การขอสลับเวร การแจ้งเตือน และการออกรายงานเอกสารคำสั่ง โดยใช้โครงสร้างแบบแยกส่วน (Decoupled Architecture) เพื่อให้ระบบมีความเสถียรและง่ายต่อการพัฒนาต่อยอด

## 🚀 เทคโนโลยีที่ใช้ (Tech Stack)

* **Frontend:** Vue 3, Bootstrap 5, Vite (รอการติดตั้ง)
* **Backend:** PHP (PDO, RESTful API Concept)
* **Database:** MySQL
* **Integrations:** LINE Notify, PHPWord

## 📁 โครงสร้างโปรเจกต์ (Project Structure)

โปรเจกต์นี้แบ่งการทำงานออกเป็น 2 ส่วนหลักอย่างชัดเจน:

```text
vengg3/
├── backend/                 # ⚙️ ฝั่งประมวลผลและ API (PHP)
│   ├── config/              # ตั้งค่าระบบ เช่น การเชื่อมต่อฐานข้อมูล
│   ├── src/                 # ตรรกะการทำงาน (Controllers, Models)
│   └── public/              # จุดเข้าถึง API (index.php)
├── frontend/                # 🎨 ฝั่งแสดงผล (Vue 3 + Bootstrap 5) [กำลังพัฒนา]
└── database/                # 💾 สคริปต์โครงสร้างฐานข้อมูล
```

## 🛠️ การติดตั้งและการใช้งานเบื้องต้น (Setup & Installation)

### 1. การตั้งค่า Backend (ฐานข้อมูล)
1. นำเข้าไฟล์ฐานข้อมูล SQL ล่าสุดเข้าไปใน MySQL
2. คัดลอกไฟล์ `backend/config/database.example.php` (ถ้ามี) แล้วเปลี่ยนชื่อเป็น `backend/config/database.php`
3. แก้ไขข้อมูลการเชื่อมต่อฐานข้อมูลใน `database.php` ให้ตรงกับเซิร์ฟเวอร์ของคุณ:
   ```php
   private $host = "127.0.0.1";
   private $db_name = "vengg";
   private $username = "root";
   private $password = "your_password";
   ```
4. ทดสอบ API โดยเข้าผ่านเบราว์เซอร์ไปที่: `http://localhost/vengg3/backend/public/index.php?route=test`

## 📌 สถานะการพัฒนา (Roadmap)

- [x] อัปเดตและปรับปรุงประสิทธิภาพโครงสร้างฐานข้อมูล (Optimize Data Types, Normalization)
- [x] วางโครงสร้างโปรเจกต์แยก Backend / Frontend
- [x] สร้างจุดเชื่อมต่อ API พื้นฐาน (PDO Connection & CORS setup)
- [ ] พัฒนา Backend API สำหรับดึงข้อมูลปฏิทินและผู้ใช้งาน
- [ ] ติดตั้งและตั้งค่า Frontend ด้วย Vue 3 + Vite
- [ ] ปรับปรุงหน้าตา UI (Modern UI Redesign)
- [ ] เชื่อมต่อระบบแจ้งเตือน LINE Notify
