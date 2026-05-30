<?php
/**
 * diag.php — Diagnóstico TEMPORAL. Abrir en el navegador (GET) para ver qué pasa
 * con OpenAI. BORRAR después de depurar (no debe quedar en producción).
 */
require_once __DIR__ . '/config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== Configuracion ===\n";
echo "OPENAI key cargada: " . (strpos(OPENAI_API_KEY, 'XXXX') === false ? ("SI (" . substr(OPENAI_API_KEY, 0, 8) . "..., largo " . strlen(OPENAI_API_KEY) . ")") : "NO (placeholder)") . "\n";
echo "Modelo: " . TD_MODEL . "\n";
echo "ELEVENLABS key cargada: " . (strpos(ELEVENLABS_API_KEY, 'XXXX') === false ? "SI" : "NO") . "\n";
echo "Voice ID: " . ELEVENLABS_VOICE_ID . "\n";
echo "secrets.php existe: " . (is_file(__DIR__ . '/secrets.php') ? "SI" : "NO") . "\n\n";

echo "=== Prueba a OpenAI ===\n";
$payload = ['model' => TD_MODEL, 'max_tokens' => 10, 'messages' => [['role' => 'user', 'content' => 'di hola']]];
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "HTTP de OpenAI: " . $code . "\n";
echo "Error de conexion: " . ($err ?: "(ninguno)") . "\n";
echo "Respuesta de OpenAI:\n" . $resp . "\n";
