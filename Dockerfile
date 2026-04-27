# ใช้ PHP เวอร์ชัน 8.2 พร้อม Apache
FROM php:8.2-apache

# อัปเดตระบบและติดตั้งส่วนเสริม PDO MySQL
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install pdo pdo_mysql

# เปิดการใช้งาน mod_rewrite ของ Apache (จำเป็นสำหรับ API Routing)
RUN a2enmod rewrite

# ตั้งค่าโฟลเดอร์ทำงานเริ่มต้น
WORKDIR /var/www/html