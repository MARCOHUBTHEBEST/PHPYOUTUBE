FROM php:8.2-apache

# 1. تثبيت التحديثات والأدوات الأساسية
RUN apt-get update && apt-get install -y \
    ffmpeg \
    curl \
    python3 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 2. الحل الجذري لمشكلة MPM: حذف ملفات الموديولات المتعارضة يدوياً
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf && \
    rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf && \
    a2enmod mpm_prefork

# 3. تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. تثبيت أداة yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

# 5. ضبط مسار العمل ونسخ الملفات
WORKDIR /var/www/html
COPY . .

# 6. تثبيت مكتبات PHP
RUN composer install --no-dev --optimize-autoloader

# 7. إعطاء صلاحيات للمجلد والملفات
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# 8. تعديل إعدادات Apache لتجنب التحميل المتعدد للموديولات عند بدء التشغيل
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

# تشغيل Apache في الواجهة الأمامية
CMD ["apache2-foreground"]
