FROM php:8.2-cli

# تثبيت الأدوات الأساسية
RUN apt-get update && apt-get install -y \
    ffmpeg \
    curl \
    python3 \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تثبيت yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

WORKDIR /var/www/html
COPY . .

# تثبيت المكتبات
RUN composer install --no-dev --optimize-autoloader

# إعطاء صلاحيات كاملة للمجلد لضمان كتابة الفيديوهات
RUN chmod -R 777 /var/www/html

# Railway يعطينا منفذ متغير عبر متغير البيئة $PORT
# سنقوم بتشغيل السيرفر عليه
CMD php -S 0.0.0.0:${PORT:-80} bot.php
