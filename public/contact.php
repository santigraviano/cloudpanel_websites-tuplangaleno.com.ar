<?php
/**
 * Contact form mailer — Tu plan Galeno
 * Uses Brevo (formerly Sendinblue) transactional email API.
 */

// ── Load environment config ───────────────────────────────────────────────────
$config_file = '/home/tuplangaleno/config.env.php';
if (file_exists($config_file)) {
    require_once $config_file;
}

// ── Configuration ────────────────────────────────────────────────────────────

define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: 'YOUR_BREVO_API_KEY_HERE');
define('FROM_EMAIL',    'noreply@tuplangaleno.com.ar');
define('FROM_NAME',     'Web Tu plan Galeno');

// Parse comma-separated TO and CC emails into Brevo recipient arrays
function parse_emails(string $env_var, string $fallback = ''): array {
    $raw = getenv($env_var) ?: $fallback;
    if (empty($raw)) return [];
    return array_values(array_filter(array_map(function($email) {
        $email = trim($email);
        return filter_var($email, FILTER_VALIDATE_EMAIL)
            ? ['email' => $email]
            : null;
    }, explode(',', $raw))));
}

$to_recipients = parse_emails('CONTACT_TO_EMAIL', 'info@tuplangaleno.com.ar');
$cc_recipients = parse_emails('CONTACT_CC_EMAIL');

// ── CORS / headers ───────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://tuplangaleno.com.ar');
header('Access-Control-Allow-Methods: POST');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// ── Honeypot anti-spam ───────────────────────────────────────────────────────

if (!empty($_POST['_honey'])) {
    echo json_encode(['success' => true]);
    exit;
}

// ── Cloudflare Turnstile verification ────────────────────────────────────────

$turnstile_secret = getenv('TURNSTILE_SECRET_KEY') ?: '';

if (empty($turnstile_secret)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuración de seguridad incompleta.']);
    exit;
}

$turnstile_token = $_POST['cf-turnstile-response'] ?? '';

if (empty($turnstile_token)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Por favor completá la verificación de seguridad.']);
    exit;
}

$verify = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
curl_setopt_array($verify, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'secret'   => $turnstile_secret,
        'response' => $turnstile_token,
        'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]),
    CURLOPT_TIMEOUT => 10,
]);

$verify_result = json_decode(curl_exec($verify), true);
curl_close($verify);

if (empty($verify_result['success'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Verificación de seguridad fallida. Por favor intentá de nuevo.']);
    exit;
}

// ── Sanitize & validate inputs ───────────────────────────────────────────────

function clean(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

$nombre   = clean($_POST['nombre']   ?? '');
$telefono = clean($_POST['telefono'] ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$mensaje  = clean($_POST['mensaje']  ?? '');

if (empty($nombre)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido.']);
    exit;
}

// ── Build email body ─────────────────────────────────────────────────────────

$subject = 'Nueva consulta web — ' . $nombre;

$htmlBody = '
<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px;">
  <h2 style="color: #00559B; margin-bottom: 24px;">Nueva consulta desde la web</h2>
  <table style="width:100%; border-collapse: collapse;">
    <tr>
      <td style="padding: 8px 12px; background: #f5f5f5; font-weight: bold; width: 140px;">Nombre</td>
      <td style="padding: 8px 12px;">' . $nombre . '</td>
    </tr>
    <tr>
      <td style="padding: 8px 12px; font-weight: bold;">Teléfono</td>
      <td style="padding: 8px 12px;">' . ($telefono ?: '—') . '</td>
    </tr>
    <tr>
      <td style="padding: 8px 12px; background: #f5f5f5; font-weight: bold;">Email</td>
      <td style="padding: 8px 12px; background: #f5f5f5;"><a href="mailto:' . $email . '">' . $email . '</a></td>
    </tr>
    <tr>
      <td style="padding: 8px 12px; font-weight: bold; vertical-align: top;">Mensaje</td>
      <td style="padding: 8px 12px;">' . nl2br($mensaje ?: '—') . '</td>
    </tr>
  </table>
</div>';

$textBody = "Nueva consulta desde la web\n\n"
    . "Nombre: $nombre\n"
    . "Teléfono: " . ($telefono ?: '—') . "\n"
    . "Email: $email\n"
    . "Mensaje: " . ($mensaje ?: '—') . "\n";

// ── Send via Brevo API ───────────────────────────────────────────────────────

$payload_data = [
    'sender'      => ['name' => FROM_NAME, 'email' => FROM_EMAIL],
    'to'          => $to_recipients,
    'replyTo'     => ['email' => $email, 'name' => $nombre],
    'subject'     => $subject,
    'htmlContent' => $htmlBody,
    'textContent' => $textBody,
];

if (!empty($cc_recipients)) {
    $payload_data['cc'] = $cc_recipients;
}

$payload = json_encode($payload_data);

$ch = curl_init('https://api.brevo.com/v3/smtp/email');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'accept: application/json',
        'api-key: ' . BREVO_API_KEY,
        'content-type: application/json',
    ],
    CURLOPT_TIMEOUT        => 10,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión con el servidor de email.']);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    $brevoResponse = json_decode($response, true);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo enviar el email. Por favor intentá de nuevo.',
        'debug'   => $brevoResponse['message'] ?? null, // remove in production
    ]);
}
