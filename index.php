<?php
$token = getenv("BOT_TOKEN");
define('API_KEY',$token);

$url = getenv("WEBHOOK_URL");

echo file_get_contents("https://api.telegram.org/bot".API_KEY."/setwebhook?url=".$url);

function bot($method,$datas=[]){
$url = "https://api.telegram.org/bot".API_KEY."/".$method;
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL,$url);
curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt($ch,CURLOPT_POSTFIELDS,$datas);
$res = curl_exec($ch);
if(curl_error($ch)){
var_dump(curl_error($ch));
}else{
return json_decode($res);
}
}

$update = json_decode(file_get_contents('php://input'));

/* ================= FIX NULL UPDATE ================= */
if(!$update){
exit;
}
/* =================================================== */

$message = $update->message ?? null;
$callback = $update->callback_query ?? null;

$id = $message->from->id ?? null;
$rep = $message->message_id ?? null;
$m = $rep + 1;

$chat_id = $message->chat->id ?? null;
$from_id = $message->from->id ?? null;

$admin = "5057151278";

$text = $message->text ?? null;

$namee = $callback->from->first_name ?? null;
$user = $message->from->username ?? null;

if(isset($update->callback_query)){
if(!isset($callback->message)) exit;

$chat_id = $callback->message->chat->id;
$message_id = $callback->message->message_id;
$data = $callback->data;
$user = $callback->from->username;
}

if($message && $from_id != $admin){
bot('forwardMessage',[
'chat_id'=>$admin,
'from_chat_id'=>$chat_id,
'message_id'=>$rep,
'text'=>$text,
]);
}

/* ================= FIX EMPTY CHAT ID ================= */
if(empty($chat_id)){
exit;
}
/* ====================================================== */

$base = file_get_contents("$chat_id");

if($text == "/start" && $base == ''){
bot('sendmessage',[
'chat_id'=>$chat_id,
'text'=>"اختر كيف تريد استخدام بحث اليوتيوب ",
'reply_to_message_id'=>$rep,
'parse_mode'=>"MARKDOWN",
'reply_markup'=>json_encode([
'inline_keyboard'=>[
[['text'=>'تحميل صوت', 'callback_data'=>"mp3"],['text'=>'تحميل فيديو', 'callback_data'=>"mp4"]],
]
])
]);
}

if($data == 'mp3'){
file_put_contents("$chat_id","mp3");
bot('EditMessageText',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
'text'=>"ارسل الكلمه للبحث عنها",
'parse_mode'=>"MARKDOWN",
]);
}

if($data == 'mp4'){
file_put_contents("$chat_id","mp4");
bot('EditMessageText',[
'chat_id'=>$chat_id,
'message_id'=>$message_id,
'text'=>"ارسل الكلمه للبحث عنها",
'parse_mode'=>"MARKDOWN",
]);
}

$yy = file_get_contents("$chat_id");

if($text && $yy == 'mp3'){
file_put_contents("$chat_id","");
file_get_contents("https://api.telegram.org/bot$token/sendAnimation?chat_id=$chat_id&animation=https://t.me/youtube7odabot/7951&reply_to_message_id=$rep");

$xx = "http://api.medooo.ml/leomedo/yt?text=$text?token=$token&chat_id=$chat_id&msg_id=$rep&type=mp3";

$ch = curl_init($xx);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch);
curl_close($ch);

file_get_contents("https://api.telegram.org/bot$token/deleteMessage?chat_id=$chat_id&message_id=$m");
}

if($text && $yy == 'mp4'){
file_put_contents("$chat_id","");
file_get_contents("https://api.telegram.org/bot$token/sendAnimation?chat_id=$chat_id&animation=https://t.me/youtube7odabot/7951&reply_to_message_id=$rep");

$ytt = "http://api.medooo.ml/leomedo/yt?text=$text?token=$token&chat_id=$chat_id&msg_id=$rep&type=mp4";

$ch = curl_init($ytt);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch);
curl_close($ch);

file_get_contents("https://api.telegram.org/bot$token/deleteMessage?chat_id=$chat_id&message_id=$m");
}

if($text == "وش بيقول" or $text == "بيقول اي" or $text == "??" or $text == "؟؟" && isset($callback->message->reply_to_message->voice)){
$idd = $callback->message->reply_to_message->voice->file_id ;
$ytt = "https://api.medooo.ml/leomedo/voiceRecognise?token=$token&chat_id=$chat_id&file_id=$idd&msg_id=$rep";

$ch = curl_init($ytt);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$data = curl_exec($ch);
curl_close($ch);
}
