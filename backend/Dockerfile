# ใช้ PHP เวอร์ชัน 8.2 พร้อม Apache
FROM php:8.2-apache

# อัปเดตระบบและติดตั้งส่วนเสริม PDO MySQL
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install pdo pdo_mysql

# เปิดการใช้งาน mod_rewrite ของ Apache (จำเป็นสำหรับ API Routing)
RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    curl \
    && docker-php-ext-install zip

# 2. ติดตั้ง Composer ลงใน Container (ถ้ายังไม่มี)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. ก๊อปปี้ไฟล์โปรเจกต์เข้าไป
# ตั้งค่าโฟลเดอร์ทำงานเริ่มต้น
WORKDIR /var/www/html
COPY . .

# 🌟 4. สั่งรัน composer install เพื่อโหลด vendor ทั้งหมดอัตโนมัติ
# RUN composer install --no-dev --optimize-autoloader
RUN composer update --no-dev --optimize-autoloader