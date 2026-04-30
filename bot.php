<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// 1. تحميل الإعدادات
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
$apiUrl = "https://api.telegram.org/bot" . $botToken;

set_time_limit(0); // للسماح بالتحميل الطويل

// 2. استقبال بيانات تيليجرام
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];

    // التحقق من الرابط
    if (preg_match('/(youtube\.com|youtu\.be)/', $text)) {
        sendMessage($chatId, "⏳ جاري معالجة الفيديو والتحميل... يرجى الانتظار.");

        $fileName = "vid_" . time() . ".mp4";
        $filePath = __DIR__ . "/" . $fileName;

        // أمر التحميل عبر yt-dlp (جودة متوسطة لضمان عدم تخطي 50 ميجا)
        $command = "yt-dlp -f 'best[ext=mp4][filesize<50M]/bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]' --merge-output-format mp4 -o " . escapeshellarg($filePath) . " " . escapeshellarg($text);
        
        exec($command);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            sendMessage($chatId, "✅ تم التحميل بنجاح! جاري إرسال الملف...");
            sendVideo($chatId, $filePath);
            unlink($filePath); // حذف الملف بعد الإرسال
        } else {
            sendMessage($chatId, "❌ فشل التحميل. قد يكون الفيديو أكبر من 50MB (حدود تيليجرام) أو الرابط محمي.");
        }
    } else {
        sendMessage($chatId, "مرحباً بك! أرسل لي رابط يوتيوب وسأقوم بتحميله لك.");
    }
}

// دالة إرسال الرسائل
function sendMessage($chatId, $text) {
    global $apiUrl;
    $url = $apiUrl . "/sendMessage?chat_id=$chatId&text=" . urlencode($text);
    file_get_contents($url);
}

// دالة إرسال الفيديو عبر CURL
function sendVideo($chatId, $filePath) {
    global $apiUrl;
    $postFields = [
        'chat_id' => $chatId,
        'video'   => new CURLFile(realpath($filePath))
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . "/sendVideo");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    curl_close($ch);
}
