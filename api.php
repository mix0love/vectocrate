<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// === КОНФИГУРАЦИЯ СЕКРЕТОВ ===
$UPSTASH_URL = 'https://climbing-ringtail-53718.upstash.io';
$UPSTASH_TOKEN = 'AdHWAAIncDI0NDhiMjMzMjcxYzA0ZjgxOTNkZDZkMmQwY2JjYWZjM3AyNTM3MTg';
$NEWS_BOT_TOKEN = '8159288191:AAFbE7abXBp4PiVB8HFPmXo8RllpORjYZVo';
$NEWS_CHANNEL_ID = '-1003594549900';
$RECORDS_BOT_TOKEN = '8509244045:AAHF5UjdLnUyYbEW-SGLiSX44W55LMj6dVs';
$RECORDS_CHAT_ID = '-1003565793982';
$ADMIN_PASS = 'VECTA';
// =============================

// Получаем и парсим JSON от фронтенда
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, TRUE);

// Определяем, к какому "эндпоинту" обращается фронтенд (передадим параметр action)
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- HELPER ФУНКЦИИ ---
function fetchUpstash($cmd) {
    global $UPSTASH_URL, $UPSTASH_TOKEN;
    
    $ch = curl_init($UPSTASH_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cmd));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $UPSTASH_TOKEN,
        'Content-Type: application/json'
    ));
    
    $response = curl_exec($ch);
    if(curl_errno($ch)){
        return json_encode(['error' => curl_error($ch)]);
    }
    curl_close($ch);
    return $response;
}

function sendTelegram($botToken, $chatId, $text) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $payload = json_encode([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML'
    ]);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// --- РОУТИНГ ---

if ($action === 'db_get') {
    // 1. ЧТЕНИЕ БАЗЫ (Публично)
    $res = fetchUpstash(['GET', 'vectocrate']);
    echo $res;

} elseif ($action === 'admin_login') {
    // 2. ЛОГИН АДМИНА
    if (isset($data['password']) && $data['password'] === $ADMIN_PASS) {
        // Создаем токен (простейший вариант для примера)
        echo json_encode(['success' => true, 'token' => 'admin_session_active_123']);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }

} elseif ($action === 'db_set') {
    // 3. ЗАПИСЬ В БАЗУ (Только Админ)
    if (!isset($data['token']) || $data['token'] !== 'admin_session_active_123') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $res = fetchUpstash(['SET', 'vectocrate', json_encode($data['db_data'])]);
    echo $res;

} elseif ($action === 'admin_news') {
    // 4. НОВОСТИ В TELEGRAM (Только Админ)
    if (!isset($data['token']) || $data['token'] !== 'admin_session_active_123') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    $res = sendTelegram($NEWS_BOT_TOKEN, $NEWS_CHANNEL_ID, $data['message']);
    echo $res;

} elseif ($action === 'submit') {
    // 5. ОТПРАВКА ОЧКОВ (Публично)
    if (!isset($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No message provided']);
        exit;
    }
    $res = sendTelegram($RECORDS_BOT_TOKEN, $RECORDS_CHAT_ID, $data['message']);
    echo $res;

} else {
    // НЕИЗВЕСТНЫЙ ЭНДПОИНТ
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>
