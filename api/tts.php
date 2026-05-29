<?php
/**
 * tts.php — Voz natural (ElevenLabs).
 *
 * Recibe { text } del frontend, llama a ElevenLabs con la clave guardada en el
 * servidor y devuelve el audio MP3 para reproducirlo en la app. Si algo falla,
 * devuelve un JSON de error y el frontend cae automáticamente a la voz del
 * navegador (speechSynthesis).
 *
 * Subir a: /api/tts.php (en el public_html del subdominio)
 */
require_once __DIR__ . '/config.php';
validarToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);

if (strpos(ELEVENLABS_API_KEY, 'XXXX') !== false) {
    jsonError('Falta configurar ELEVENLABS_API_KEY en api/config.php del servidor.', 500);
}

$body = bodyJson();
$text = trim($body['text'] ?? '');
if ($text === '') jsonError('Falta el texto a leer.', 422);
if (mb_strlen($text) > 2500) $text = mb_substr($text, 0, 2500); // tope de seguridad

$payload = [
    'text'     => $text,
    'model_id' => ELEVENLABS_MODEL,
    'voice_settings' => [
        'stability'        => 0.55, // estable y serena (tono de acompañamiento)
        'similarity_boost' => 0.80,
        'style'            => 0.0,  // sin exageración = suave y natural
        'use_speaker_boost'=> true,
        'speed'            => 0.92, // un poco más pausada (terapeuta)
    ],
];

$url = 'https://api.elevenlabs.io/v1/text-to-speech/' . rawurlencode(ELEVENLABS_VOICE_ID);
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Accept: audio/mpeg',
        'xi-api-key: ' . ELEVENLABS_API_KEY,
    ],
    CURLOPT_POSTFIELDS     => json_encode($payload),
]);

$audio = curl_exec($ch);
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$ctype = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$err   = curl_error($ch);
curl_close($ch);

if ($audio === false) {
    jsonError('No se pudo contactar al servicio de voz: ' . $err, 502);
}

// Si ElevenLabs devolvió un error (JSON) en vez de audio, lo pasamos como error.
if ($code >= 400 || stripos((string)$ctype, 'audio') === false) {
    jsonError('El servicio de voz devolvió un error.', $code ?: 502);
}

header('Content-Type: audio/mpeg');
header('Content-Length: ' . strlen($audio));
header('Cache-Control: no-store');
echo $audio;
