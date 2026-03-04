<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === КОНФИГУРАЦИЯ СЕКРЕТОВ ===
$UPSTASH_REST_URL = "https://climbing-ringtail-53718.upstash.io";
$UPSTASH_TOKEN    = "AdHWAAIncDI0NDhiMjMzMjcxYzA0ZjgxOTNkZDZkMmQwY2JjYWZjM3AyNTM3MTg";
$NEWS_BOT_TOKEN   = "";
$NEWS_CHANNEL_ID  = "";
$RECORDS_BOT_TOKEN = "";
$RECORDS_CHAT_ID  = "";
$ADMIN_PASS       = "VECTA";
// =============================

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true) ?: [];

function fetchUpstash($cmd) {
    global $UPSTASH_REST_URL, $UPSTASH_TOKEN;
    $ch = curl_init($UPSTASH_REST_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $UPSTASH_TOKEN",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cmd));
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function sendTelegram($bot, $chat, $text) {
    if (!$bot || !$chat) return json_encode(['ok' => true, 'description' => 'Bots disabled by admin']);
    $url = "https://api.telegram.org/bot$bot/sendMessage";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        "chat_id" => $chat,
        "text" => $text,
        "parse_mode" => "HTML"
    ]));
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// ROUTES
if ($action === 'db_get') {
    echo fetchUpstash(['GET', 'vectocrate']);
    exit;
}

if ($action === 'admin_login') {
    if (($data['password'] ?? '') === $ADMIN_PASS) {
        echo json_encode(['success' => true, 'token' => 'admin_session_active_123']);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid password']);
    }
    exit;
}

if ($action === 'db_set') {
    if (($data['token'] ?? '') !== 'admin_session_active_123') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    echo fetchUpstash(['SET', 'vectocrate', json_encode($data['db_data'])]);
    exit;
}

if ($action === 'admin_news') {
    if (($data['token'] ?? '') !== 'admin_session_active_123') {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    echo sendTelegram($NEWS_BOT_TOKEN, $NEWS_CHANNEL_ID, $data['message'] ?? '');
    exit;
}

if ($action === 'submit') {
    if (!isset($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No message']);
        exit;
    }
    echo sendTelegram($RECORDS_BOT_TOKEN, $RECORDS_CHAT_ID, $data['message']);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
