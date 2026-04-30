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
        sendMessage($chatId, "👋 أهلاً بك! أرسل رابط فيديو قصير للتجربة.");
    } 
    elseif (preg_match('/(youtube\.com|youtu\.be)/', $text)) {
        sendMessage($chatId, "⏳ جاري التحميل... يرجى الانتظار.");
        
        $fileName = "video_" . time() . ".mp4";
        $filePath = __DIR__ . "/" . $fileName;

        // استخدمنا المسار الكامل لـ yt-dlp لضمان تشغيله
        // وأضفنا خيار --no-playlist لضمان عدم تحميل قائمة تشغيل بالخطأ
        $command = "/usr/local/bin/yt-dlp -f 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]' --no-playlist --merge-output-format mp4 -o " . escapeshellarg($filePath) . " " . escapeshellarg($text) . " 2>&1";
        
        exec($command, $output, $return_var);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            sendVideo($chatId, $filePath);
            unlink($filePath); 
        } else {
            // إرسال تفاصيل الخطأ للـ Logs في Railway لتعرف المشكلة
            error_log("YT-DLP Error: " . implode("\n", $output));
            sendMessage($chatId, "❌ فشل التحميل. يرجى مراجعة الـ Logs في Railway.");
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
