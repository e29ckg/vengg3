# ติดตั้งและอัปเดตบน Windows ด้วย XAMPP

คู่มือนี้ใช้กับ `vengg3` ที่เปิดเว็บที่ `/vengg3/` และ API ที่ `/vengg3/api/` ตัวอย่างใช้ `C:\xampp`; หากติดตั้ง XAMPP คนละที่ให้เปลี่ยนพาธตามเครื่อง

> **ฐานข้อมูลที่มีข้อมูลอยู่แล้ว:** สำรองและทดสอบกู้คืนก่อนอัปเดต ห้ามนำ `database.sql` เข้าไปซ้ำ เพราะไฟล์นี้มี `DROP TABLE` สำหรับเริ่มฐานใหม่

## 1. เตรียมเครื่อง

- Windows และ XAMPP ที่มี Apache, PHP **8.2 ขึ้นไป**, MySQL/MariaDB; ควรเลือก runtime ที่ยังได้รับการสนับสนุนและทดลองย้ายฐานบน staging ก่อน
- Node.js **22.13 ขึ้นไป**, npm และ Composer อยู่ใน `PATH` ของ PowerShell; ติดตั้ง Git หากจะ clone หรือใช้ระบบอัปเดตจาก Git หากติดตั้งเพิ่ม ให้เปิด PowerShell ใหม่ และรีสตาร์ต Apache หากใช้ตัวติดตั้งหน้าเว็บ
- PHP extensions: `pdo_mysql`, `curl`, `mbstring`, `fileinfo`, `zip`
- Apache เปิด `mod_rewrite` และ `AllowOverride All` สำหรับ `htdocs` แล้วรีสตาร์ต Apache
- เปิด Apache และ MySQL ใน XAMPP Control Panel ตรวจพอร์ต Apache จาก `C:\xampp\apache\conf\httpd.conf` (`Listen`)
- หากแอปและฐานอยู่เครื่องเดียวกัน ให้ MySQL รับจาก `127.0.0.1`; ใช้บัญชีฐานข้อมูลแยกสำหรับแอปที่มีสิทธิ์เท่าที่จำเป็น และตั้งรหัสผ่าน `root`

## 2. เตรียมฐานข้อมูล

### ติดตั้งใหม่บนฐานว่าง

1. ดาวน์โหลดหรือ clone โปรเจกต์ให้ครบทั้ง `frontend`, `backend`, `deploy`, `database.sql`, `.htaccess` และ `deploy-xampp.ps1` วางสำเนาแรกไว้ที่ `C:\xampp\htdocs\vengg3`
2. บนเครื่อง XAMPP เปิด `http://localhost:<พอร์ต>/vengg3/install.php` กด **ตรวจสอบความพร้อม** แล้วเลือก **สร้าง/ตั้งค่าฐานข้อมูล → สร้างใหม่**
3. ใช้ชื่อฐานใหม่ที่ยังไม่มี กำหนดบัญชีแอปที่ไม่ใช่ `root` พร้อมรหัสผ่านอย่างน้อย 12 ตัวอักษร และรหัสผู้ดูแลแอป 12–72 bytes ใช้บัญชีผู้ดูแล MySQL เฉพาะตอนสร้างฐาน ตัวติดตั้งสร้าง schema, บัญชีแอปแบบ CRUD, admin เมื่อยังไม่มี และบันทึก `backend/src/config/database.local.php` เฉพาะเครื่อง
4. กด **เริ่มติดตั้ง** เมื่อทุกข้อผ่าน ตัวติดตั้งจะ build frontend, ติดตั้ง Composer dependencies และตรวจ HTTP หลังติดตั้ง

ตัวติดตั้งไม่สร้างฐานทับชื่อเดิม หากสร้างค้างกลางทาง ให้ตรวจฐานและบัญชีที่อาจถูกสร้างไว้ก่อนลองใหม่

### ใช้ฐานเดิมหรือฐานที่กู้คืน

1. สำรองฐานและไฟล์ตั้งค่าก่อนอัปเดต ตรวจว่าฐานเปิดปกติและบัญชีแอปอ่านตาราง `user` ได้
2. ใช้ตัวติดตั้งหน้าเว็บแบบ **เชื่อมฐานข้อมูลเดิม** หรือสร้าง `backend/src/config/database.local.php` ใน checkout ที่จะ deploy โดยใช้ค่าจริงของเครื่อง:

   ```php
   <?php
   return [
       'DB_HOST' => '127.0.0.1',
       'DB_PORT' => '3306',
       'DB_NAME' => 'vengg_db',
       'DB_USER' => 'vengg_app',
       'DB_PASS' => 'replace-with-a-unique-strong-password',
   ];
   ```

3. ตรวจว่ามี admin ที่เปิดใช้งานอยู่แล้ว หรือใช้ตัวติดตั้งเพื่อสร้างเมื่อยังไม่มี ห้ามนำ `database.sql` เข้าฐานเดิมและห้ามสลับ binary/data directory ของ MariaDB โดยไม่มีขั้นตอนย้ายรุ่นและ restore ที่ทดสอบแล้ว

`database.local.php` ถูก Git ignore; อย่า commit ไฟล์นี้, `.env`, credential ของ Google, token ของ Telegram, SQL dump, snapshot หรือรหัสถอดชุดสำรอง

## 3. Deploy ผ่าน PowerShell

หากใช้ตัวติดตั้งหน้าเว็บสำเร็จแล้ว ไม่ต้อง deploy ซ้ำ หากใช้ PowerShell ให้เปิดจาก checkout ซึ่งอยู่ **นอก** `htdocs` ได้ สคริปต์จะคัดลอกแอปไป `C:\xampp\htdocs\vengg3` และคง `database.local.php` กับไฟล์อัปโหลดเดิมในปลายทาง

```powershell
cd C:\path\to\vengg3
powershell -NoProfile -ExecutionPolicy Bypass -File .\deploy-xampp.ps1 -PreflightOnly
powershell -NoProfile -ExecutionPolicy Bypass -File .\deploy-xampp.ps1
```

ถ้า XAMPP ไม่ได้อยู่ที่ `C:\xampp` เพิ่ม `-XamppRoot 'D:\xampp'` ในทั้งสองคำสั่ง สคริปต์ต้องพบ Node.js, npm และ Composer ใน `PATH` และเชื่อมฐานข้อมูลได้ตั้งแต่ preflight ผล `Preflight passed` หมายถึงยังไม่ได้คัดลอกไฟล์แอป

สคริปต์สร้าง frontend ด้วย base path `/vengg3/` และ API path `/vengg3/api/`, ติดตั้ง PHP packages ตาม `backend/composer.lock`, คัดลอกไฟล์เข้า `htdocs\vengg3` แล้วตรวจเว็บ, JavaScript, API และการปฏิเสธไฟล์ส่วนตัว

## 4. ตรวจหลังติดตั้ง

แทน `<พอร์ต>` ด้วยค่า `Listen` ของ Apache (เครื่องที่ใช้ตรวจความปลอดภัยครั้งนี้คือ `8099`; เครื่องอื่นอาจต่างกัน)

| URL/รายการ | ผลที่ควรได้ |
|---|---|
| `http://127.0.0.1:<พอร์ต>/vengg3/` | หน้าเว็บ HTTP 200 |
| `http://127.0.0.1:<พอร์ต>/vengg3/api/?route=test` | JSON ที่มี `status: success` |
| `/vengg3/api/?route=auth/me` เมื่อยังไม่เข้าสู่ระบบ | HTTP 401 |
| `/vengg3/backend/src/config/database.php` | HTTP 403 |
| `/vengg3/.git/config` และ `/vengg3/database.sql` | HTTP 403 |

ให้ผู้ใช้ปิดแท็บเก่า เปิดหน้าเว็บใหม่และเข้าสู่ระบบใหม่หลังการอัปเดตความปลอดภัย ทดสอบงานจริงด้วยบัญชีที่ได้รับอนุญาตตาม role รวมถึงการอนุมัติ, รายงาน, เอกสาร และอัปโหลดก่อนเปิดให้ผู้ใช้ทั้งหน่วยงาน

## 5. สำรองและอัปเดตครั้งถัดไป

- สำรองฐานข้อมูล, `database.local.php`, Google credentials, รูปผู้ใช้ และเทมเพลดเอกสาร **นอก web root**; เข้ารหัสไฟล์สำรอง ตรวจ checksum และทดลองกู้คืนจากที่เก็บสำรอง
- ใช้ `-PreflightOnly` ก่อน deploy ทุกครั้ง ตรวจ PHP/Apache/MySQL และ schema ให้ผ่าน แล้วจึง deploy จาก commit ที่ต้องการ
- การ deploy นี้ **ไม่รัน database migration**; หากรุ่นใหม่เปลี่ยน schema ต้องมีแผน migration/rollback แยก และทดสอบกับสำเนาฐานก่อน
- GitHub เก็บโค้ดและคู่มือ ไม่ใช่ที่เก็บฐานข้อมูลจริงหรือรหัสผ่าน เครื่องที่คัดลอกแอปจากสคริปต์อาจไม่มี `.git` จึงไม่ควรสมมติว่าเมนูอัปเดตผ่าน Git ในแอปใช้ได้กับการติดตั้งแบบนี้
- ให้ผู้ดูแลตรวจงานด้านความปลอดภัยที่ยังค้าง เช่น การเปลี่ยน credentials เดิม, การใช้ runtime ที่ยังได้รับการสนับสนุน, HTTPS และการยืนยัน restore
