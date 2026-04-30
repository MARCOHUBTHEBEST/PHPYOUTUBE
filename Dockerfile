FROM php:8.2-apache

# تثبيت التحديثات والأدوات الأساسية
RUN apt-get update && apt-get install -y \
    ffmpeg \
    curl \
    python3 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer لإدارة مكتبات PHP
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تثبيت أداة yt-dlp للتحميل من يوتيوب
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

# ضبط مسار العمل ونسخ الملفات
WORKDIR /var/www/html
COPY . .

# تثبيت مكتبة phpdotenv وحذف ملفات الكاش
RUN composer install --no-dev --optimize-autoloader

# إعطاء صلاحيات للمجلد لكتابة الفيديوهات المؤقتة
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
