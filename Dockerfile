FROM php:8.2-cli

# تثبيت الأدوات الأساسية مع Node.js (ضروري جداً لحل شيفرات يوتيوب)
RUN apt-get update && apt-get install -y \
    ffmpeg \
    curl \
    python3 \
    unzip \
    gnupg \
    && curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تثبيت أحدث نسخة من yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

WORKDIR /var/www/html
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chmod -R 777 /var/www/html

CMD php -S 0.0.0.0:${PORT:-80} bot.php
