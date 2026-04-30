<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// تحميل الإعدادات
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// قراءة التوكن من البيئة
$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
$apiUrl = "https://api.telegram.org/bot" . $botToken;

// استقبال البيانات - تعديل مهم هنا لضمان عملها مع سيرفر PHP المدمج
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// تسجيل الطلبات في الـ Logs لمساعدتك في معرفة ما إذا كانت الرسائل تصل
if ($content) {
    error_log("Received Update: " . $content);
}

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? "";

    if ($text == "/start") {
        sendMessage($chatId, "👋 أهلاً بك! أنا بوت تحميل يوتيوب. أرسل لي أي رابط وسأقوم بتحميله.");
    } 
    elseif (preg_match('/(youtube\.com|youtu\.be)/', $text)) {
        sendMessage($chatId, "⏳ جاري المعالجة... يرجى الانتظار.");
        
        $fileName = "vid_" . time() . ".mp4";
        $filePath = __DIR__ . "/" . $fileName;

        // أمر التحميل
        $command = "yt-dlp -f 'best[ext=mp4][filesize<50M]/best[ext=mp4]' --merge-output-format mp4 -o " . escapeshellarg($filePath) . " " . escapeshellarg($text) . " 2>&1";
        
        exec($command, $output, $return_var);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            sendVideo($chatId, $filePath);
            unlink($filePath);
        } else {
            sendMessage($chatId, "❌ حدث خطأ أو الفيديو كبير جداً. تأكد من الرابط.");
            error_log("yt-dlp error: " . implode("\n", $output));
        }
    }
}

// الدوال المساعدة (نفسها السابقة)
function sendMessage($chatId, $text) {
    global $apiUrl;
    $url = $apiUrl . "/sendMessage?chat_id=$chatId&text=" . urlencode($text);
    file_get_contents($url);
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
