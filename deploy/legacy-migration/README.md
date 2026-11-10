# ย้ายข้อมูล vengg รุ่นเดิมเข้า vengg_db

สคริปต์หลัก: `deploy/migrate-legacy.php` ต้องรันด้วย PHP CLI ที่มี PDO MySQL และ PHP 8.1 ขึ้นไป ใช้ฐานต้นทางและปลายทางบน MySQL/MariaDB server เดียวกัน โครงสร้างปลายทางต้องสร้างตาม `database.sql` เวอร์ชันนี้ไว้แล้ว ตารางที่ใช้ต้องเป็น InnoDB

## ขอบเขต

- นำเข้าผู้ใช้และ bcrypt hash เดิม โปรไฟล์ ประเภทเวร หน้าที่ย่อย สมาชิกเวร คำสั่ง ตารางเวร และใบเปลี่ยนเวรที่อนุมัติ/รออนุมัติ
- สร้าง user ID แบบ deterministic 32 ตัวอักษรจากชื่อฐานต้นทางและ ID เดิม ส่วน ID ตัวเลขใหม่จัดต่อจากค่าสูงสุดของแต่ละตารางปลายทาง
- เก็บ mapping และต้นฉบับทุกตารางที่อ่านไว้ใน `_legacy_migration_map` และ `_legacy_migration_archive` รวมทั้งเวรที่ถูกแทนที่/ยกเลิก ข้อมูลบัตรประชาชน/ธนาคารใน archive ต้องจำกัดสิทธิ์เช่นเดียวกับข้อมูลจริง ไม่สำเนา password hash/session/reset token/LINE token เข้า archive
- รวมสายการเปลี่ยนเวรเป็นเวรปัจจุบันหนึ่งแถวต่อหน้าที่ ใช้ `ven_com_idb` เชื่อมคำสั่ง และแปลงสถานะใบเปลี่ยนเวรเดิม 2 → 0
- ไม่อัปเดตผู้ใช้/เวรเดิมในปลายทาง รวมรายการตัวเลือกกับ `system_settings.user_options` ส่วนผู้ลงนามเปลี่ยนเฉพาะเมื่อระบุ `agency_settings` ใน config
- **ไฟล์รูป/เอกสารไม่ได้คัดลอกอัตโนมัติ** ค่า avatar และ command file ใหม่เป็น NULL โดยรูปกำหนดผ่าน `avatar_overrides` หลังคัดลอกไฟล์ได้ ส่วนชื่อไฟล์ต้นฉบับยังอยู่ใน archive ต้องย้ายและเชื่อมเอกสารแยกก่อนตรวจรับ
- ผู้ลงนามเดิมอยู่ใน archive; ต้องกรอกหน้าองค์กรหรือ config ตาม JSON ของหน้าจอใหม่ บริการ Telegram/Google ต้องตั้งใหม่ โดยไม่คัดลอก event ID มาใช้เอง
- ประวัติที่ยกเลิกเก็บใน archive ไม่แสดงในหน้าประวัติปกติ หากต้องการเปิดเอกสาร snapshot เดิมจาก UI ต้องเพิ่มหน้าจออ่าน archive แยก
- เป็น **one-time import** หลังสำเร็จจะบันทึก `_legacy_migration_runs` และการรันซ้ำสำหรับ source เดิมจะตอบ `already_applied` โดยไม่แก้ข้อมูล แม้ config/source เปลี่ยน จึงไม่ใช่ระบบ sync หรือ incremental migration

## 1. เตรียม config

ทดลองกับฐาน staging ที่สร้างใหม่ก่อน ห้ามทดลอง `--apply` กับฐานจริง สร้าง staging ด้วยชื่ออื่นและ import `database.sql` **เฉพาะฐานว่าง** เพราะไฟล์นี้มี DROP TABLE

คัดลอก `config.example.php` ไปไฟล์ส่วนตัวนอก web root เช่น `C:\xampp\private\vengg-migration.local.php` และเปลี่ยน `target_database` เป็น staging ให้ถูกต้อง ไฟล์นี้คืน array ด้วย PHP จึงควรใช้ไฟล์ที่ผู้ดูแลตรวจแล้วเท่านั้น

บัญชีอ่าน/เขียนใช้ environment `MIGRATION_DB_USER` และ `MIGRATION_DB_PASS` หากไม่ได้กำหนด จะอ่านบัญชีจาก `backend/src/config/database.local.php` ใน checkout ที่รัน ห้ามใส่รหัสผ่านใน command argument หรือ commit ลง Git

ตัวอย่าง PowerShell ใช้ `Read-Host` เพื่อไม่เก็บรหัสผ่านไว้ในคำสั่ง:

```powershell
$env:MIGRATION_DB_USER = Read-Host 'MySQL migration user'
$migrationPassword = Read-Host 'MySQL password' -AsSecureString
$env:MIGRATION_DB_PASS = [System.Net.NetworkCredential]::new('', $migrationPassword).Password
```

สำหรับ dry-run บัญชีต้องอ่านตารางต้นทาง/ปลายทางและ metadata ได้ สำหรับ apply ต้องอ่านต้นทาง, SELECT/INSERT ปลายทาง, UPDATE ตารางตั้งค่า และ CREATE ตารางบันทึกการย้าย ไม่ต้องให้สิทธิ์ DROP ฐานจริง แนะนำใช้บัญชีสำหรับ migration แยกจากบัญชีแอป

## 2. ตรวจอย่างเดียว

รันจากโฟลเดอร์โปรเจกต์:

```powershell
& C:\xampp\php\php.exe deploy/migrate-legacy.php --config=C:\xampp\private\vengg-migration.local.php
```

ค่าเริ่มต้นเป็น dry-run: อ่านผ่าน transaction และ rollback โดยไม่สร้างตารางและไม่แก้ข้อมูล ส่งผล JSON ผ่าน stdout และข้อความ runtime error ผ่าน stderr

- Exit 0: `ready` หรือ `already_applied`
- Exit 2: `blocked` ต้องแก้ config/ข้อมูลที่ตรวจพบก่อน
- Exit 1: เชื่อมต่อ/โครงสร้าง/การตั้งค่า/คำสั่งฐานข้อมูลผิดพลาด หากเริ่ม transaction แล้วจะ rollback

บันทึกผลได้ด้วย `| Set-Content -Encoding utf8 C:\xampp\private\migration-report.json` รายงานระบุ source IDs แต่ไม่แสดงชื่อผู้ใช้ รหัสผ่าน หรือข้อมูลส่วนบุคคลทั้งแถว

## 3. แก้ข้อผิดพลาดโดยไม่เปลี่ยนฐานเก่า

- `username_overrides`: key เป็น user.id **เดิม**, value เป็น username ใหม่ที่ตรวจแล้ว ไม่รวมบัญชีเดิมกับ admin ใหม่อัตโนมัติ
- `restore_subs`: เพิ่มหน้าที่ย่อยที่กู้จาก backup หรือทบทวน snapshot แล้ว ต้องมี id, ven_name_id, name, price, color, srt, st ครบ ห้ามเดาอัตราค่าตอบแทน
- `row_overrides`: แก้ค่าเฉพาะสำเนาที่ใช้วางแผน ไม่แก้ฐานต้นทาง และ archive ยังคงค่าต้นฉบับ เช่น แก้วันที่ศูนย์เป็น NULL หลังตรวจแล้ว:

```php
'username_overrides' => [
    'EXAMPLE_USER_ID' => 'legacy_admin', // ใช้เฉพาะเมื่อทบทวนแล้วว่าเป็นคนละบัญชี
],
'row_overrides' => [
    'user' => [
        // เติมคอลัมน์ที่รายงานแจ้งจริง: created_at หรือ updated_at
        // 'EXAMPLE_USER_ID' => ['created_at' => null],
    ],
    'ven_change' => [
        // 'CH...' => ['ven_id1' => VERIFIED_ID, 'ven_id2' => VERIFIED_ID],
    ],
],
```

สคริปต์ไม่ยอมแก้ primary key หรือ password/token ผ่าน overrides ถ้าแก้เวร/ประวัติ ต้องตรวจข้อมูลครบทั้งใบเก่า ใบใหม่ วันที่ หน้าที่ และเจ้าของทั้งสองฝั่งก่อน กรณีใบที่อนุมัติแล้วอ้างสลับฝั่ง ต้องแก้ reference ให้ตรงกับ slot จริง ไม่สลับเพียงเพื่อให้ผ่าน validation

`preserve_locked_pending` เป็นตัวเลือกสำหรับเวรเดิมสถานะ 2 ที่ไม่มีใบคำขอ โดย key เป็น `ven.id` เดิมและ value เป็นเหตุผลที่ตรวจแล้ว สคริปต์จะคงเวรเป็นสถานะ 2 ที่ล็อกการเปลี่ยนเวร พร้อม warning และบันทึก config ใน archive ไม่สร้างคำขอหรืออนุมัติย้อนหลังให้เอง ค่าเริ่มต้นยังหยุดเมื่อพบกรณีนี้ และจะหยุดหากระบุ ID ที่ไม่ตรงกับเวรค้างจริง

ตรวจ `shift_periods` ให้ตรงเวลาหน่วยงาน และตรวจรายการ `ven_com_days` ที่อนุมานจากวันที่มีเวรจริง โดยวันที่เปิดให้จัดแต่ยังไม่มีเวรจะไม่สามารถอนุมานได้

## 4. ย้ายจริงหลัง staging ผ่าน

1. สำรองฐานทั้งสองและไฟล์ uploads พร้อมทดสอบว่าสามารถ restore ได้
2. หยุดการเขียนของ **ทั้งสองแอป** รวม cron และบริการ sync; สวิตช์ `--maintenance-confirmed` เป็นคำยืนยันของผู้รัน ไม่ได้หยุดแอปให้อัตโนมัติ
3. ตั้ง target เป็นฐานจริง รัน dry-run อีกครั้ง ต้องได้ `ready`
4. สั่ง apply:

```powershell
& C:\xampp\php\php.exe deploy/migrate-legacy.php --config=C:\xampp\private\vengg-migration.local.php --apply --maintenance-confirmed
```

สคริปต์ใช้ advisory lock กัน migration ซ้อน, ล็อกแถวปลายทางขณะวางแผน, strict SQL และ transaction เดียวสำหรับข้อมูลแอป/mapping/archive/run marker หากล้มเหลวจะ rollback ทั้งชุด ไม่มีการเขียนฐานต้นทาง

**ข้อจำกัด DDL:** MariaDB/MySQL ทำ implicit commit เมื่อ CREATE TABLE จึงสร้างตาราง `_legacy_*` ว่างก่อน transaction หาก apply ไม่ผ่านอาจเหลือตารางบันทึกว่างไว้ แต่ข้อมูลแอปต้องไม่ถูกเพิ่มบางส่วน Dry-run ไม่สร้างตารางเหล่านี้

5. ตรวจนับเวร/ประวัติแยกสถานะ กระทบยอดค่าตอบแทนรายเดือนและรายบุคคล ทดลอง login, สิทธิ์, พิมพ์, การแลกเวรต่อเนื่อง และอนุมัติ/ยกเลิกคำขอค้าง จัดการไฟล์และผู้ลงนามให้ครบก่อนเปิดใช้งาน
6. ปลด environment รหัสผ่านหลังงานด้วย `Remove-Item Env:MIGRATION_DB_PASS` และเปิดการเขียนเมื่อผ่านตรวจรับ

ไม่มีคำสั่งลบข้อมูล migration ย้อนกลับอัตโนมัติ เพราะหลังเปิดแอปอาจมีข้อมูลใหม่อ้างถึง ID ที่นำเข้า หากตรวจรับไม่ผ่าน ให้ใช้ backup restore ปลายทางขณะยังหยุดการเขียน ไม่ลบ run marker เพื่อบังคับรันซ้ำบนข้อมูลที่นำเข้าแล้ว

## ทดสอบสคริปต์

```powershell
& C:\xampp\php\php.exe backend/tests/legacy-migration-test.php
```

ทดสอบ planner แบบ offline: แลกสองฝั่ง, ยกเวรต่อ, ใบรอ/ยกเลิก, หน้าที่ย่อยขาด, timestamp ผิด, owner/reference ผิด, ค่าตอบแทนไม่ตรง, username ชน และการกู้ผ่าน config

Integration test ใช้บัญชีที่สร้าง/ลบฐานทดสอบได้บน 127.0.0.1:3306 และใช้ `vengg` เป็นแหล่งโครงสร้าง **CREATE TABLE LIKE เท่านั้น ไม่คัดลอกข้อมูลจริง**:

```powershell
$env:MIGRATION_TEST_ALLOW_CREATE_DB = '1'
& C:\xampp\php\php.exe backend/tests/legacy-migration-mysql-test.php
Remove-Item Env:MIGRATION_TEST_ALLOW_CREATE_DB
```

ทดสอบ dry-run ไม่เขียน, maintenance guard, บังคับ error หลังเริ่ม insert แล้วตรวจ rollback, apply สำเร็จ, archive และการรันซ้ำ สร้างฐานชื่อสุ่ม `vengg_migration_test_src_*` / `vengg_migration_test_dst_*` และลบเฉพาะฐานเหล่านี้ใน finally
