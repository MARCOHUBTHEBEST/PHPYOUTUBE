FROM php:8.2-cli

# 1. تثبيت التحديثات والأدوات الأساسية
RUN apt-get update && apt-get install -y \
    ffmpeg \
    curl \
    python3 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# 2. تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. تثبيت أداة yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

# 4. ضبط مسار العمل ونسخ الملفات
WORKDIR /var/www/html
COPY . .

# 5. تثبيت مكتبات PHP
RUN composer install --no-dev --optimize-autoloader

# 6. إعطاء صلاحيات للمجلد
RUN chmod -R 777 /var/www/html

# تفعيل المنفذ الذي يطلبه Railway
EXPOSE 80

# تشغيل سيرفر PHP المدمج مباشرة على المنفذ 80
CMD ["php", "-S", "0.0.0.0:80", "bot.php"]
