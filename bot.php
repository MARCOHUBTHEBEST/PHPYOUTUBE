<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
$apiUrl = "https://api.telegram.org/bot" . $botToken;

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? "";

    if ($text == "/start") {
        sendMessage($chatId, "👋 أهلاً بك! البوت الآن يعمل بنظام الكوكيز لتخطي الحماية. أرسل الرابط.");
    } 
    elseif (preg_match('/(youtube\.com|youtu\.be)/', $text)) {
        sendMessage($chatId, "⏳ جاري التحميل باستخدام نظام الكوكيز...");
        
        $fileName = "vid_" . time() . ".mp4";
        $filePath = __DIR__ . "/" . $fileName;
        $cookiesPath = __DIR__ . "/cookies.txt"; // مسار ملف الكوكيز

        // أضفنا أمر --cookies لاستخدام الملف المرفوع
        $command = "/usr/local/bin/yt-dlp " .
                   "--cookies " . escapeshellarg($cookiesPath) . " " .
                   "--user-agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36\" " .
                   "--no-check-certificate " .
                   "-f 'bestvideo[ext=mp4][filesize<45M]+bestaudio[ext=m4a]/best[ext=mp4]' " .
                   "--no-playlist " .
                   "--merge-output-format mp4 " .
                   "-o " . escapeshellarg($filePath) . " " .
                   escapeshellarg($text) . " 2>&1";
        
        exec($command, $output, $return_var);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            sendVideo($chatId, $filePath);
            unlink($filePath);
        } else {
            error_log("❌ Cookies Mode Error: " . implode("\n", $output));
            sendMessage($chatId, "❌ فشل التحميل حتى مع الكوكيز. تأكد من أن الفيديو ليس طويلاً جداً.");
        }
    }
}

function sendMessage($chatId, $text) {
    global $apiUrl;
    file_get_contents($apiUrl . "/sendMessage?chat_id=$chatId&text=" . urlencode($text));
}

function sendVideo($chatId, $filePath) {
    global $apiUrl;
    $postFields = ['chat_id' => $chatId, 'video' => new CURLFile(realpath($filePath))];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "/sendVideo");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    curl_close($ch);
}
