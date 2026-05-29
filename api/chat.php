<?php
/**
 * chat.php — Proxy al "cerebro" (OpenAI · GPT-4o).
 *
 * El frontend NUNCA habla directo con OpenAI (eso expondría la clave).
 * Manda aquí { system, messages, max_tokens } y este archivo agrega la
 * OPENAI_API_KEY (guardada en el servidor) y reenvía la petición.
 *
 * Devolvemos la respuesta en el MISMO formato que el frontend ya entiende
 * ({ content: [ { type:"text", text:"..." } ] }), así no hay que tocar la app.
 *
 * Subir a: /api/chat.php (en el public_html del sitio)
 */
require_once __DIR__ . '/config.php';
validarToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);

if (strpos(OPENAI_API_KEY, 'XXXX') !== false) {
    jsonError('Falta configurar OPENAI_API_KEY en api/config.php del servidor.', 500);
}

$body = bodyJson();
$messages = $body['messages'] ?? null;
$system   = $body['system'] ?? '';
$maxTokens = isset($body['max_tokens']) ? (int)$body['max_tokens'] : 1000;
if ($maxTokens < 1 || $maxTokens > 4096) $maxTokens = 1000;

if (!is_array($messages) || count($messages) === 0) {
    jsonError('Faltan los mensajes de la conversación.', 422);
}

// OpenAI lleva el "system" como primer mensaje con rol "system".
$oaMessages = [];
if ($system !== '') $oaMessages[] = ['role' => 'system', 'content' => $system];
foreach ($messages as $m) {
    $role = (($m['role'] ?? 'user') === 'assistant') ? 'assistant' : 'user';
    $oaMessages[] = ['role' => $role, 'content' => (string)($m['content'] ?? '')];
}

// El modelo lo decide el servidor (no el navegador), para controlar costo/uso.
$payload = [
    'model'       => TD_MODEL,
    'max_tokens'  => $maxTokens,
    'temperature' => 0.8, // calidez y variación natural
    'messages'    => $oaMessages,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY,
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);

$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false) {
    jsonError('No se pudo contactar al servicio de IA: ' . $err, 502);
}

$data = json_decode($resp, true);

// Si OpenAI devolvió un error, lo pasamos en el formato que el frontend espera.
if ($code >= 400 || isset($data['error'])) {
    $msg = $data['error']['message'] ?? 'Error del servicio de IA';
    jsonError($msg, $code ?: 502);
}

$reply = $data['choices'][0]['message']['content'] ?? '';

http_response_code(200);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['content' => [['type' => 'text', 'text' => $reply]]]);
