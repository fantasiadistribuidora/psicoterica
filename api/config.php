<?php
/**
 * Psicotérica — Configuración del backend.
 *
 * 🔑 Las CLAVES (secretos) NO van aquí ni a Git: van en  secrets.php
 *    (creado SOLO en el servidor: /public_html/api/secrets.php).
 *    Este config.php sí se despliega por Git; secrets.php está en .gitignore,
 *    así que los deploys automáticos NUNCA borran tus claves.
 */

// 1) Cargar secretos del servidor (si existen).
//    Buscamos PRIMERO fuera de public_html: ahí los deploys de Git NO lo borran.
$__secretFiles = [
    __DIR__ . '/../../secrets.php', // RECOMENDADO: junto a public_html (sobrevive deploys)
    __DIR__ . '/secrets.php',        // alterno: dentro de api (un deploy puede borrarlo)
];
foreach ($__secretFiles as $__f) {
    if (is_file($__f)) { require_once $__f; break; }
}
// Alternativa: variables de entorno (si el hosting las soporta)
if (!defined('OPENAI_API_KEY') && getenv('OPENAI_API_KEY'))         define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
if (!defined('ELEVENLABS_API_KEY') && getenv('ELEVENLABS_API_KEY')) define('ELEVENLABS_API_KEY', getenv('ELEVENLABS_API_KEY'));

// 2) Defaults si falta algún secreto (placeholder => el backend avisa "falta configurar")
if (!defined('OPENAI_API_KEY'))     define('OPENAI_API_KEY', 'sk-XXXX');
if (!defined('ELEVENLABS_API_KEY')) define('ELEVENLABS_API_KEY', 'XXXX');
if (!defined('TD_API_TOKEN'))       define('TD_API_TOKEN', '');

// 3) Valores NO secretos
if (!defined('TD_MODEL'))            define('TD_MODEL', 'gpt-4o');
if (!defined('ELEVENLABS_VOICE_ID')) define('ELEVENLABS_VOICE_ID', 'iDEmt5MnqUotdwCIVplo');
if (!defined('ELEVENLABS_MODEL'))    define('ELEVENLABS_MODEL', 'eleven_multilingual_v2');

// ── CORS y errores ────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Td-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ── Helpers ───────────────────────────────────────────────────
function jsonError($msg, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $msg]);
    exit;
}

function bodyJson() {
    $input = file_get_contents('php://input');
    return json_decode($input, true) ?: [];
}

function validarToken() {
    if (TD_API_TOKEN === '') return;
    $token = $_SERVER['HTTP_X_TD_TOKEN'] ?? '';
    if (!hash_equals(TD_API_TOKEN, $token)) {
        jsonError('Token inválido', 401);
    }
}
