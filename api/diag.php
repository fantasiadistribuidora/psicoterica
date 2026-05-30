<?php
/**
 * diag.php — Diagnóstico TEMPORAL robusto. Abrir en navegador (GET). BORRAR luego.
 * Muestra errores aunque PHP esté en modo silencioso.
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

$dir = __DIR__;
echo "=== diag v2 ===\n";
echo "PHP " . PHP_VERSION . "\n";
echo "secrets.php existe: " . (is_file("$dir/secrets.php") ? "SI" : "NO (¿se borró?)") . "\n";
echo "config.php existe:  " . (is_file("$dir/config.php") ? "SI" : "NO") . "\n\n";

echo "--- 1) Probar secrets.php solo ---\n";
try {
    if (is_file("$dir/secrets.php")) { include "$dir/secrets.php"; echo "secrets.php: incluido OK\n"; }
    else echo "secrets.php: NO existe\n";
} catch (\Throwable $e) {
    echo "secrets.php: ERROR -> " . $e->getMessage() . " (línea " . $e->getLine() . ")\n";
}
echo "OPENAI_API_KEY definida:     " . (defined('OPENAI_API_KEY') ? ("SI, largo " . strlen(OPENAI_API_KEY)) : "NO") . "\n";
echo "ELEVENLABS_API_KEY definida: " . (defined('ELEVENLABS_API_KEY') ? "SI" : "NO") . "\n\n";

echo "--- 2) Probar config.php ---\n";
try { require "$dir/config.php"; echo "config.php: incluido OK\n"; }
catch (\Throwable $e) { echo "config.php: ERROR -> " . $e->getMessage() . " (línea " . $e->getLine() . ")\n"; }
echo "\n";

echo "--- 3) Probar OpenAI ---\n";
if (defined('OPENAI_API_KEY') && strpos(OPENAI_API_KEY, 'XXXX') === false) {
    $model = defined('TD_MODEL') ? TD_MODEL : 'gpt-4o';
    $payload = ['model' => $model, 'max_tokens' => 10, 'messages' => [['role' => 'user', 'content' => 'di hola']]];
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . OPENAI_API_KEY],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $r = curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); $er = curl_error($ch); curl_close($ch);
    echo "HTTP de OpenAI: $c\n";
    echo "curl: " . ($er ?: "(ok)") . "\n";
    echo "Respuesta:\n" . $r . "\n";
} else {
    echo "No hay clave de OpenAI válida (placeholder o no definida).\n";
    echo ">>> Esto explicaría el fallo del guía: la clave no se está cargando.\n";
}
