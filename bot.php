<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// 1. إعدادات البيئة
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$botToken = $_ENV['TELEGRAM_BOT_TOKEN'] ?? getenv('TELEGRAM_BOT_TOKEN');
$apiUrl = "https://api.telegram.org/bot" . $botToken;

// 2. استقبال البيانات من تيليجرام
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"] ?? "";

    // أمر البداية
    if ($text == "/start") {
        sendMessage($chatId, "👋 أهلاً بك يا بطل! أنا بوت تحميل يوتيوب المتطور.\n\nفقط أرسل رابط الفيديو وسأحاول تحميله لك بأفضل جودة (أقل من 50MB).");
    } 
    // اكتشاف روابط يوتيوب
    elseif (preg_match('/(youtube\.com|youtu\.be)/', $text)) {
        sendMessage($chatId, "⏳ جاري محاولة تخطي حماية يوتيوب والتحميل... يرجى الانتظار ثواني.");
        
        $fileName = "vid_" . time() . ".mp4";
        $filePath = __DIR__ . "/" . $fileName;

        /**
         * شرح المتغيرات المضافة للأمر:
         * --user-agent: للتمويه بأن الطلب قادم من متصفح ويندوز وليس سيرفر.
         * --extractor-args: لإخبار يوتيوب أننا نستخدم مشغل أندرويد (لتجاوز حظر الـ Bots).
         * --no-check-certificate: لتجنب مشاكل شهادات الأمان في السيرفر.
         */
        $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36";
        $extractorArgs = "youtube:player_client=android,web";
        
        $command = "/usr/local/bin/yt-dlp " .
                   "--user-agent " . escapeshellarg($userAgent) . " " .
                   "--extractor-args " . escapeshellarg($extractorArgs) . " " .
                   "--no-check-certificate " .
                   "-f 'bestvideo[ext=mp4][filesize<45M]+bestaudio[ext=m4a]/best[ext=mp4]' " .
                   "--no-playlist " .
                   "--merge-output-format mp4 " .
                   "-o " . escapeshellarg($filePath) . " " .
                   escapeshellarg($text) . " 2>&1";
        
        exec($command, $output, $return_var);

        if (file_exists($filePath) && filesize($filePath) > 0) {
            sendMessage($chatId, "✅ تم التحميل! جاري الرفع إلى تيليجرام...");
            sendVideo($chatId, $filePath);
            unlink($filePath); // حذف الفيديو من السيرفر بعد الإرسال
        } else {
            // تسجيل الخطأ في Railway Logs
            $errorLog = implode("\n", $output);
            error_log("❌ YT-DLP Error: " . $errorLog);
            
            if (strpos($errorLog, 'Sign in') !== false) {
                sendMessage($chatId, "❌ عذراً، يوتيوب حظر السيرفر حالياً ويطلب تسجيل دخول. حاول مرة أخرى لاحقاً أو جرب فيديو آخر.");
            } else {
                sendMessage($chatId, "❌ فشل التحميل. قد يكون الفيديو طويلاً جداً أو محمياً.");
            }
        }
    }
}

/**
 * دالة إرسال الرسائل النصية
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // وقت انتظار الاتصال
    curl_exec($ch);
    curl_close($ch);
}
