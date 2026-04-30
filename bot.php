<?php
/**
 * بوت تحميل فيديوهات يوتيوب لتيليجرام
 * المبرمج: Michael (SA | ALONE)
 */

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// 1. تحميل إعدادات البيئة (التوكن)
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
$apiUrl = "https://api.telegram.org/bot" . $botToken;

// 2. استقبال طلبات تيليجرام
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? "";

    // أمر البداية
    if ($text == "/start") {
        sendMessage($chatId, "👋 أهلاً بك يا مايكل!\nالبوت الآن مدعوم بمحرك Node.js ونظام الكوكيز لتخطي حماية يوتيوب.\n\nأرسل رابط الفيديو وابشر بسعدك.");
    } 
    
    // التحقق من روابط يوتيوب
    elseif (preg_match('/(youtube\.com|youtu\.be)/', $text)) {
        sendMessage($chatId, "⏳ جاري فك تشفير الرابط والتحميل... يرجى الانتظار.");
        
        $fileName = "video_" . time() . ".mp4";
        $filePath = __DIR__ . "/" . $fileName;
        $cookiesPath = __DIR__ . "/cookies.txt";

        // إعداد أمر التحميل مع كافة التحسينات
        // أضفنا --no-warnings لتقليل زحمة الـ Logs
        $command = "/usr/local/bin/yt-dlp " .
                   "--cookies " . escapeshellarg($cookiesPath) . " " .
                   "--user-agent \"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36\" " .
                   "--no-check-certificate " .
                   "--no-warnings " .
                   "-f \"best[ext=mp4][filesize<48M]/best\" " . 
                   "--no-playlist " .
                   "-o " . escapeshellarg($filePath) . " " .
                   escapeshellarg($text) . " 2>&1";
        
        exec($command, $output, $return_var);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            sendMessage($chatId, "✅ تم التحميل بنجاح! جاري إرسال الفيديو...");
            sendVideo($chatId, $filePath);
            unlink($filePath); // حذف الملف لتوفير مساحة السيرفر
        } else {
            // تسجيل الخطأ التفصيلي في Railway Logs
            $fullError = implode("\n", $output);
            error_log("❌ Error Details: " . $fullError);
            
            if (strpos($fullError, 'format is not available') !== false) {
                sendMessage($chatId, "❌ الفيديو المطلوب حجمه كبير جداً (أكثر من 50MB) أو صيغته غير مدعومة.");
            } else {
                sendMessage($chatId, "❌ حدث خطأ تقني. تم تسجيل التفاصيل في السيرفر.");
            }
        }
    }
}

/**
 * دالة إرسال الرسائل
 */
function sendMessage($chatId, $text) {
    global $apiUrl;
    $url = $apiUrl . "/sendMessage?chat_id=$chatId&text=" . urlencode($text);
    file_get_contents($url);
}

/**
 * دالة إرسال الفيديو باستخدام CURL
 */
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20); 
    curl_exec($ch);
    curl_close($ch);
}
