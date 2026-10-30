<?php
// ============================================
//  تنظیمات اصلی (این ۳ خط رو پر کن)
// ============================================
$apiBoxKey = "sk-4fIQJbREqWtKwtAqwlTgV4F1ThBquQGyXpCTcQO5XjjrrEFJ"; // کلید APIBox
$botToken  = "CIBCGG0YLAPBHNRHYIQIQVJZPXGOTVPFNTPFMPRHKGWNHMNIFAXEPZMFAKEMJKXX"; // توکن ربات روبیکا (از BotFather)
$modelName = "gpt-6-sol"; // مدل درخواستی
// ============================================

// ۱. دریافت پیام از روبیکا (Webhook)
$input = file_get_contents('php://input');
$update = json_decode($input, true);

// اگه پیام جدیدی بود پردازش میکنیم
if (isset($update['update']['message'])) {
    $message = $update['update']['message'];
    $chatId  = $message['chat_id'] ?? null;
    $userText = $message['text'] ?? '';

    if ($chatId && $userText) {
        // ۲. گرفتن جواب از هوش مصنوعی
        $aiResponse = askAI($userText, $apiBoxKey, $modelName);
        
        // ۳. ارسال جواب به کاربر توی روبیکا
        sendRubikaMessage($botToken, $chatId, $aiResponse);
    }
}

// همیشه به روبیکا بگو پیام رو دریافت کردی (تا ارور نده)
http_response_code(200);
echo "OK";
exit;


// ============================================
// توابع ارتباط با هوش مصنوعی و روبیکا
// ============================================

function askAI($text, $key, $model) {
    $url = "https://api.apibox.cc/v1/chat/completions";
    $data = [
        "model" => $model,
        "messages" => [
            ["role" => "system", "content" => "شما یک دستیار مودب و فارسی زبان هستید."],
            ["role" => "user", "content" => $text]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $key,
        "Content-Type: application/json"
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($response, true);
    if (isset($json['choices'][0]['message']['content'])) {
        return $json['choices'][0]['message']['content'];
    }
    
    // اگه خطا داد (مثلا مدل اشتباه بود)
    return "خطا در ارتباط با هوش مصنوعی. لطفا بعدا تلاش کنید.";
}

function sendRubikaMessage($token, $chatId, $text) {
    $url = "https://botapi.rubika.ir/v3/" . $token . "/sendMessage";
    
    $data = [
        "chat_id" => $chatId,
        "text" => $text
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    
    curl_exec($ch);
    curl_close($ch);
}
