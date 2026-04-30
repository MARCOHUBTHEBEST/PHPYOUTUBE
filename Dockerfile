FROM php:8.2-apache

# 1. تثبيت التحديثات والأدوات الأساسية
RUN apt-get update && apt-get install -y \
    ffmpeg \
    curl \
    python3 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 2. حل مشكلة Apache MPM (إيقاف الوحدات الزائدة وتفعيل prefork)
RUN a2dismod mpm_event || true && \
    a2dismod mpm_worker || true && \
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

# 7. إعطاء صلاحيات للمجلد
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
