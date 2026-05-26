<?php
/**
 * BBR Fragrance — Instalador de Sistema
 * -------------------------------------------------------
 * Ejecutar UNA SOLA VEZ en producción desde el navegador:
 *   https://tudominio.com/install.php
 *
 * IMPORTANTE: Eliminar este archivo después de instalar.
 * -------------------------------------------------------
 */

define('LOCK_FILE',      __DIR__ . '/install.lock');
define('DB_CONFIG',      __DIR__ . '/api/config/database.php');
define('CONSTANTS_FILE', __DIR__ . '/api/config/constants.php');
define('API_INDEX',      __DIR__ . '/api/index.php');
define('ADMIN_CORE',     __DIR__ . '/js/admin/core.js');
define('MAIN_CORE',      __DIR__ . '/js/main/core.js');

define('SQL_FILES', [
    'database/schema.sql'            => 'Estructura de tablas base',
    'database/seed.sql'              => 'Datos iniciales (admin, categorías, productos)',
    'database/migration_fase1.sql'   => 'Roles y permisos',
    'database/migration_ncf.sql'     => 'Comprobantes fiscales NCF',
    'database/migration_cardnet.sql' => 'Pasarela de pago Cardnet',
    'database/migration_checkout.sql'=> 'Configuración de checkout',
    'database/migration_promo.sql'   => 'Sección de promociones',
    'database/migration_web_sales.sql'=> 'Integración de ventas web',
]);

// ── Bloqueo de seguridad ──────────────────────────────────────────────────────
if (file_exists(LOCK_FILE)) {
    $installedAt = trim(file_get_contents(LOCK_FILE));
    renderAlreadyInstalled($installedAt);
    exit;
}

session_start();

// ── Helpers ───────────────────────────────────────────────────────────────────

function checkRequirements(): array {
    return [
        'PHP ≥ 8.0'               => version_compare(PHP_VERSION, '8.0.0', '>='),
        'Extensión pdo_mysql'      => extension_loaded('pdo_mysql'),
        'Extensión cURL'           => extension_loaded('curl'),
        'Extensión OpenSSL'        => extension_loaded('openssl'),
        'Extensión mbstring'       => extension_loaded('mbstring'),
        'Extensión json'           => extension_loaded('json'),
        'Carpeta api/config/ escribible' => is_writable(dirname(DB_CONFIG)),
        'Carpeta js/admin/ escribible'   => is_writable(dirname(ADMIN_CORE)),
        'Carpeta js/main/ escribible'    => is_writable(dirname(MAIN_CORE)),
        'Archivo api/index.php escribible' => is_writable(API_INDEX),
    ];
}

function testDbConnection(string $host, string $port, string $dbname, string $user, string $pass): array {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT                  => 8,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]);
        return ['ok' => true, 'pdo' => $pdo];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}

function executeSqlFile(PDO $pdo, string $filePath, string $dbName): array {
    if (!file_exists($filePath)) {
        return ['ok' => false, 'message' => 'Archivo no encontrado', 'errors' => 1, 'warnings' => 0, 'details' => []];
    }

    $sql = file_get_contents($filePath);

    // Reemplazar nombre de BD hardcodeado en INFORMATION_SCHEMA
    $safe = addslashes($dbName);
    foreach (["'BBR Fragrance'", "'bbr_fragance'", "'BBR_FRAGANCE'", "'bbr_fragrance'"] as $old) {
        $sql = str_replace($old, "'$safe'", $sql);
    }

    // Eliminar CREATE DATABASE y USE (no aplican — la BD ya existe)
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b[^;]*;/im', '', $sql);
    $sql = preg_replace('/^\s*USE\s+[^;]*;/im', '', $sql);

    $statements = explode(';', $sql);
    $errorCount   = 0;
    $warningCount = 0;
    $details      = [];

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt)) continue;

        // Ignorar bloques que solo contienen comentarios
        $stripped = preg_replace('/--[^\n]*/m', '', $stmt);
        $stripped = preg_replace('/\/\*.*?\*\//s', '', $stripped);
        if (empty(trim($stripped))) continue;

        try {
            $result = $pdo->query($stmt);
            if ($result instanceof PDOStatement) {
                $result->closeCursor();
            }
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            $ignorable = (bool) preg_match(
                '/already exists|duplicate|can\'t drop|multiple primary|Duplicate column/i',
                $msg
            );
            if ($ignorable) {
                $warningCount++;
                $details[] = ['type' => 'warn', 'text' => $msg];
            } else {
                $errorCount++;
                $details[] = ['type' => 'error', 'text' => $msg, 'stmt' => mb_substr(trim($stmt), 0, 100)];
            }
        }
    }

    $ok = $errorCount === 0;
    return [
        'ok'       => $ok,
        'errors'   => $errorCount,
        'warnings' => $warningCount,
        'details'  => $details,
        'message'  => $ok
            ? ($warningCount > 0 ? "OK ({$warningCount} advertencia(s) ignoradas)" : 'OK')
            : "FALLÓ — {$errorCount} error(es) crítico(s)",
    ];
}

function writeDbConfig(string $host, string $port, string $dbname, string $user, string $pass): bool {
    $h = var_export($host,   true);
    $p = var_export((int)$port, true);
    $d = var_export($dbname, true);
    $u = var_export($user,   true);
    $w = var_export($pass,   true);
    $ts = date('Y-m-d H:i:s');

    $content = <<<PHP
<?php
/**
 * BBR Fragrance - Database Connection
 * Generado por install.php el $ts
 */

class Database {
    private static \$instance = null;
    private \$connection;

    private \$host     = $h;
    private \$port     = $p;
    private \$dbname   = $d;
    private \$username = $u;
    private \$password = $w;
    private \$charset  = 'utf8mb4';

    private function __construct() {
        \$dsn = "mysql:host={\$this->host};port={\$this->port};dbname={\$this->dbname};charset={\$this->charset}";
        \$options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            \$this->connection = new PDO(\$dsn, \$this->username, \$this->password, \$options);
        } catch (PDOException \$e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error de conexion a la base de datos']);
            exit;
        }
    }

    public static function getInstance() {
        if (self::\$instance === null) {
            self::\$instance = new self();
        }
        return self::\$instance;
    }

    public function getConnection() {
        return \$this->connection;
    }

    private function __clone() {}
}

function getDB() {
    return Database::getInstance()->getConnection();
}
PHP;
    return file_put_contents(DB_CONFIG, $content) !== false;
}

function updateConstants(string $baseUrl): bool {
    $content = file_get_contents(CONSTANTS_FILE);
    if ($content === false) return false;
    $escaped = addslashes($baseUrl);
    $content = preg_replace(
        "/define\('BASE_URL',\s*'[^']*'\)/",
        "define('BASE_URL', '{$escaped}')",
        $content
    );
    return file_put_contents(CONSTANTS_FILE, $content) !== false;
}

function updateApiIndex(string $basePath, string $origin, bool $forceHttps): bool {
    $content = file_get_contents(API_INDEX);
    if ($content === false) return false;

    // Actualizar $basePath
    $content = preg_replace(
        '/\$basePath\s*=\s*\'[^\']*\';/',
        "\$basePath = '" . addslashes($basePath) . "';",
        $content
    );

    // Restringir CORS al dominio
    $allowOrigin = $forceHttps ? "https://{$origin}" : "http://{$origin}";
    $content = preg_replace(
        "/header\('Access-Control-Allow-Origin:\s*[^']*'\);/",
        "header('Access-Control-Allow-Origin: {$allowOrigin}');",
        $content
    );

    // Añadir secure => true a la cookie de sesión (solo si no está ya)
    if (strpos($content, "'secure'") === false) {
        $content = str_replace(
            "'samesite' => 'Strict'\n]);",
            "'samesite' => 'Strict'," . "\n    'secure'   => true\n]);" ,
            $content
        );
        // fallback con tab indentation
        $content = str_replace(
            "'samesite' => 'Strict'\r\n]);",
            "'samesite' => 'Strict'," . "\r\n    'secure'   => true\r\n]);",
            $content
        );
    }

    return file_put_contents(API_INDEX, $content) !== false;
}

function updateJsApiPath(string $apiPath): bool {
    $ok = true;
    $escaped = addslashes($apiPath);

    $admin = file_get_contents(ADMIN_CORE);
    if ($admin !== false) {
        $admin = preg_replace("/const\s+API\s*=\s*'[^']*';/", "const API = '{$escaped}';", $admin);
        $ok = $ok && (file_put_contents(ADMIN_CORE, $admin) !== false);
    }

    $main = file_get_contents(MAIN_CORE);
    if ($main !== false) {
        $main = preg_replace("/const\s+API_BASE\s*=\s*'[^']*';/", "const API_BASE = '{$escaped}';", $main);
        $ok = $ok && (file_put_contents(MAIN_CORE, $main) !== false);
    }

    return $ok;
}

function createUploadDirs(): array {
    $dirs = [
        __DIR__ . '/uploads',
        __DIR__ . '/uploads/products',
        __DIR__ . '/uploads/promo',
    ];
    $results = [];
    foreach ($dirs as $dir) {
        $rel = str_replace(__DIR__ . '/', '', $dir);
        if (is_dir($dir)) {
            $results[] = ['path' => $rel, 'status' => 'exists', 'ok' => true];
        } else {
            $created = @mkdir($dir, 0755, true);
            $results[] = ['path' => $rel, 'status' => $created ? 'created' : 'error', 'ok' => $created];
        }
    }
    return $results;
}

// ── Procesamiento de pasos ────────────────────────────────────────────────────

$step       = (int)($_POST['step'] ?? $_GET['step'] ?? 1);
$formErrors = [];
$installResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Paso 2: Validar y guardar credenciales BD ─────────────────────────────
    if ($step === 2) {
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbPort = trim($_POST['db_port'] ?? '3306');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';

        if (!$dbName || !$dbUser) {
            $formErrors[] = 'El nombre de la base de datos y el usuario son obligatorios.';
        } else {
            $test = testDbConnection($dbHost, $dbPort, $dbName, $dbUser, $dbPass);
            if (!$test['ok']) {
                $formErrors[] = 'No se pudo conectar: ' . $test['error'];
            } else {
                $_SESSION['bbr_install_db'] = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass');
                $step = 3;
            }
        }
    }

    // ── Paso 3: Guardar configuración del sitio ───────────────────────────────
    elseif ($step === 3) {
        $baseUrl    = rtrim(trim($_POST['base_url'] ?? ''), '/');
        $domain     = trim($_POST['domain'] ?? $_SERVER['HTTP_HOST'] ?? '');
        $forceHttps = !empty($_POST['force_https']);

        if (!$domain) {
            $formErrors[] = 'El dominio es obligatorio.';
        } else {
            $_SESSION['bbr_install_site'] = compact('baseUrl', 'domain', 'forceHttps');
            $step = 4;
        }
    }

    // ── Paso 4: Ejecutar instalación ─────────────────────────────────────────
    elseif ($step === 4) {
        $db   = $_SESSION['bbr_install_db']   ?? null;
        $site = $_SESSION['bbr_install_site'] ?? null;

        if (!$db || !$site) {
            $formErrors[] = 'Sesión expirada. Por favor empieza de nuevo.';
            $step = 1;
        } else {
            $test = testDbConnection($db['dbHost'], $db['dbPort'], $db['dbName'], $db['dbUser'], $db['dbPass']);
            if (!$test['ok']) {
                $formErrors[] = 'Error de conexión: ' . $test['error'];
                $step = 2;
            } else {
                $pdo = $test['pdo'];
                $installResults = ['sql' => [], 'config' => [], 'dirs' => [], 'success' => false];

                // 1. Ejecutar archivos SQL
                foreach (SQL_FILES as $file => $label) {
                    $r = executeSqlFile($pdo, __DIR__ . '/' . $file, $db['dbName']);
                    $installResults['sql'][] = array_merge($r, ['label' => $label, 'file' => $file]);
                }

                // 2. Escribir api/config/database.php
                $ok = writeDbConfig($db['dbHost'], $db['dbPort'], $db['dbName'], $db['dbUser'], $db['dbPass']);
                $installResults['config'][] = ['label' => 'api/config/database.php — Credenciales BD', 'ok' => $ok];

                // 3. Actualizar BASE_URL en constants.php
                $baseUrl = $site['baseUrl'] ?: '';
                $ok = updateConstants($baseUrl ?: '/');
                $installResults['config'][] = ['label' => 'api/config/constants.php — BASE_URL', 'ok' => $ok];

                // 4. Actualizar api/index.php (basePath, CORS, session secure)
                $basePath = $baseUrl . '/api';
                $ok = updateApiIndex($basePath, $site['domain'], $site['forceHttps']);
                $installResults['config'][] = ['label' => 'api/index.php — basePath, CORS, cookie segura', 'ok' => $ok];

                // 5. Actualizar paths en JS
                $ok = updateJsApiPath($basePath);
                $installResults['config'][] = ['label' => 'js/admin/core.js + js/main/core.js — API path', 'ok' => $ok];

                // 6. Crear directorios de uploads
                $installResults['dirs'] = createUploadDirs();

                // 7. ¿Todo OK?
                $sqlFailed  = array_filter($installResults['sql'],    fn($r) => !$r['ok']);
                $cfgFailed  = array_filter($installResults['config'], fn($r) => !$r['ok']);
                $dirsFailed = array_filter($installResults['dirs'],   fn($r) => !$r['ok']);

                $allOk = empty($sqlFailed) && empty($cfgFailed);
                $installResults['success'] = $allOk;

                if ($allOk) {
                    file_put_contents(LOCK_FILE, date('Y-m-d H:i:s') . ' - BBR Fragrance instalado correctamente');
                    session_destroy();
                    $step = 5;
                }
                // Si falla, $step permanece en 4 y se muestran los resultados
            }
        }
    }
}

// Redirigir atrás si la sesión perdió datos
if ($step > 2 && empty($_SESSION['bbr_install_db']))   { $step = 1; }
if ($step > 3 && empty($_SESSION['bbr_install_site'])) { $step = 2; }

$reqs = checkRequirements();
$reqsOk = !in_array(false, $reqs, true);

// ── Funciones de renderizado ─────────────────────────────────────────────────

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function renderAlreadyInstalled(string $installedAt): void { ?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BBR Fragrance — Ya instalado</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-gray-950 text-white min-h-screen flex items-center justify-center p-6">
<div class="max-w-md w-full bg-gray-800 rounded-2xl p-8 text-center border border-amber-500/30">
    <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-amber-400 mb-2">Sistema ya instalado</h1>
    <p class="text-gray-400 mb-4">Instalación realizada el <strong class="text-white"><?= h($installedAt) ?></strong></p>
    <div class="bg-red-900/30 border border-red-500/40 rounded-lg p-4 text-left text-sm">
        <p class="text-red-400 font-semibold mb-1">⚠ Acción requerida</p>
        <p class="text-gray-300">Por seguridad, <strong>elimina el archivo <code>install.php</code></strong> del servidor inmediatamente.</p>
    </div>
</div></body></html>
<?php } ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BBR Fragrance — Instalador</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #0f172a; }
        .step-active { background: #f59e0b; color: #000; }
        .step-done   { background: #22c55e; color: #000; }
        .step-pend   { background: #374151; color: #9ca3af; }
    </style>
</head>
<body class="text-white min-h-screen py-10 px-4">

<div class="max-w-2xl mx-auto">

    <!-- Header -->
    <div class="text-center mb-8">
        <h1 class="text-3xl font-serif text-amber-400 mb-1">BBR Fragrance</h1>
        <p class="text-gray-400">Asistente de instalación</p>
    </div>

    <!-- Barra de pasos -->
    <div class="flex items-center justify-center gap-2 mb-8">
        <?php
        $stepLabels = ['Requisitos', 'Base de datos', 'Sitio', 'Instalar', 'Listo'];
        foreach ($stepLabels as $i => $label):
            $n = $i + 1;
            if ($n < $step)       $cls = 'step-done';
            elseif ($n === $step) $cls = 'step-active';
            else                  $cls = 'step-pend';
        ?>
        <div class="flex items-center <?= $n > 1 ? 'gap-2' : '' ?>">
            <?php if ($n > 1): ?>
            <div class="w-8 h-px <?= $n <= $step ? 'bg-amber-500' : 'bg-gray-700' ?>"></div>
            <?php endif; ?>
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold <?= $cls ?>"><?= $n < $step ? '✓' : $n ?></div>
                <span class="text-xs mt-1 <?= $n === $step ? 'text-amber-400' : 'text-gray-500' ?> hidden sm:block"><?= $label ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tarjeta principal -->
    <div class="bg-gray-800/70 border border-gray-700 rounded-2xl p-8">

    <?php if (!empty($formErrors)): ?>
    <div class="mb-6 bg-red-900/40 border border-red-500/50 rounded-lg p-4">
        <?php foreach ($formErrors as $err): ?>
        <p class="text-red-300 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?= h($err) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ════════════════════════════════════════════════════════════
         PASO 1 — Requisitos
    ═══════════════════════════════════════════════════════════════ -->
    <?php if ($step === 1): ?>
    <h2 class="text-xl font-bold text-white mb-1">Verificación de requisitos</h2>
    <p class="text-gray-400 text-sm mb-6">Comprueba que el servidor cumple con los requisitos mínimos antes de continuar.</p>

    <div class="space-y-2 mb-6">
        <?php foreach ($reqs as $name => $ok): ?>
        <div class="flex items-center justify-between bg-gray-900/60 rounded-lg px-4 py-3">
            <span class="text-sm text-gray-300"><?= h($name) ?></span>
            <?php if ($ok): ?>
            <span class="text-green-400 text-sm font-semibold"><i class="fas fa-check-circle mr-1"></i>OK</span>
            <?php else: ?>
            <span class="text-red-400 text-sm font-semibold"><i class="fas fa-times-circle mr-1"></i>Falta</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$reqsOk): ?>
    <div class="bg-red-900/30 border border-red-500/40 rounded-lg p-4 mb-6 text-sm text-red-300">
        <strong>Hay requisitos no cumplidos.</strong> Contacta al soporte de tu hosting para habilitarlos antes de continuar.
    </div>
    <?php endif; ?>

    <div class="bg-amber-900/20 border border-amber-500/30 rounded-lg p-4 mb-6 text-sm text-amber-200">
        <i class="fas fa-info-circle mr-2 text-amber-400"></i>
        <strong>Antes de continuar:</strong> La base de datos debe estar <strong>creada manualmente</strong> en cPanel (vacía, sin tablas). El instalador creará las tablas automáticamente.
    </div>

    <form method="POST">
        <input type="hidden" name="step" value="2">
        <button type="submit" <?= !$reqsOk ? 'disabled' : '' ?>
            class="w-full py-3 rounded-xl font-bold text-black transition
                   <?= $reqsOk ? 'bg-amber-500 hover:bg-amber-400 cursor-pointer' : 'bg-gray-700 text-gray-500 cursor-not-allowed' ?>">
            Continuar <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </form>

    <!-- ════════════════════════════════════════════════════════════
         PASO 2 — Configuración de Base de Datos
    ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($step === 2): ?>
    <h2 class="text-xl font-bold text-white mb-1">Conexión a la base de datos</h2>
    <p class="text-gray-400 text-sm mb-6">Ingresa los datos de la BD que creaste en cPanel. El instalador probará la conexión antes de continuar.</p>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="step" value="2">

        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label class="block text-sm font-medium text-gray-300 mb-1">Host</label>
                <input type="text" name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Puerto</label>
                <input type="text" name="db_port" value="<?= h($_POST['db_port'] ?? '3306') ?>"
                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">
                Nombre de la base de datos <span class="text-red-400">*</span>
            </label>
            <input type="text" name="db_name" value="<?= h($_POST['db_name'] ?? '') ?>"
                placeholder="ej: rafaelt_bbr"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
            <p class="text-xs text-gray-500 mt-1">En cPanel el nombre lleva el prefijo del usuario: <code>usuario_nombre</code></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">
                Usuario de BD <span class="text-red-400">*</span>
            </label>
            <input type="text" name="db_user" value="<?= h($_POST['db_user'] ?? '') ?>"
                placeholder="ej: rafaelt_bbruser"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">Contraseña</label>
            <input type="password" name="db_pass" value=""
                placeholder="Contraseña del usuario de BD"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl font-bold text-black bg-amber-500 hover:bg-amber-400 transition">
            <i class="fas fa-plug mr-2"></i>Probar conexión y continuar
        </button>
    </form>

    <!-- ════════════════════════════════════════════════════════════
         PASO 3 — Configuración del sitio
    ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($step === 3): ?>
    <h2 class="text-xl font-bold text-white mb-1">Configuración del sitio</h2>
    <p class="text-gray-400 text-sm mb-6">Indica dónde estará alojado el sistema para configurar las rutas internas.</p>

    <?php
    $detectedDomain = $_SERVER['HTTP_HOST'] ?? '';
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    ?>

    <form method="POST" class="space-y-5">
        <input type="hidden" name="step" value="3">

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">
                Ruta base del sistema
            </label>
            <input type="text" name="base_url" value="<?= h($_POST['base_url'] ?? '') ?>"
                placeholder="Dejar vacío si está en la raíz del dominio (ej: /tienda si está en un subdirectorio)"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
            <p class="text-xs text-gray-500 mt-1">
                Si el sitio está en <code>tudominio.com</code> → dejar vacío.<br>
                Si está en <code>tudominio.com/tienda</code> → escribir <code>/tienda</code>
            </p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-300 mb-1">
                Dominio <span class="text-red-400">*</span>
            </label>
            <input type="text" name="domain" value="<?= h($_POST['domain'] ?? $detectedDomain) ?>"
                placeholder="ej: tudominio.com"
                class="w-full bg-gray-900 border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
            <p class="text-xs text-gray-500 mt-1">Sin <code>http://</code> ni <code>/</code> al final. Solo el dominio.</p>
        </div>

        <div class="flex items-center justify-between bg-gray-900/60 rounded-lg px-4 py-3">
            <div>
                <p class="text-sm font-medium text-gray-300">Forzar HTTPS</p>
                <p class="text-xs text-gray-500">Restringe las cookies y CORS a conexiones seguras</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="force_https" value="1" class="sr-only peer"
                    <?= ($isHttps || !empty($_POST['force_https'])) ? 'checked' : '' ?>>
                <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-amber-500 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
            </label>
        </div>

        <div class="bg-blue-900/20 border border-blue-500/30 rounded-lg p-4 text-sm text-blue-200">
            <i class="fas fa-info-circle mr-2 text-blue-400"></i>
            El instalador actualizará automáticamente:<br>
            <code class="text-xs">api/config/database.php</code> ·
            <code class="text-xs">api/config/constants.php</code> ·
            <code class="text-xs">api/index.php</code> ·
            <code class="text-xs">js/admin/core.js</code> ·
            <code class="text-xs">js/main/core.js</code>
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl font-bold text-black bg-amber-500 hover:bg-amber-400 transition">
            Continuar <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </form>

    <!-- ════════════════════════════════════════════════════════════
         PASO 4 — Instalación
    ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($step === 4): ?>
    <?php if ($installResults === null): // Formulario de confirmación ?>

    <h2 class="text-xl font-bold text-white mb-1">Listo para instalar</h2>
    <p class="text-gray-400 text-sm mb-6">Revisa el resumen antes de ejecutar la instalación. Este proceso <strong>no puede deshacerse</strong>.</p>

    <?php $db = $_SESSION['bbr_install_db']; $site = $_SESSION['bbr_install_site']; ?>
    <div class="space-y-3 mb-6">
        <div class="bg-gray-900/60 rounded-lg px-4 py-3 grid grid-cols-2 gap-2 text-sm">
            <span class="text-gray-400">Base de datos</span><span class="text-white font-mono"><?= h($db['dbName']) ?></span>
            <span class="text-gray-400">Host</span><span class="text-white font-mono"><?= h($db['dbHost']) ?>:<?= h($db['dbPort']) ?></span>
            <span class="text-gray-400">Usuario</span><span class="text-white font-mono"><?= h($db['dbUser']) ?></span>
        </div>
        <div class="bg-gray-900/60 rounded-lg px-4 py-3 grid grid-cols-2 gap-2 text-sm">
            <span class="text-gray-400">Dominio</span><span class="text-white font-mono"><?= h($site['domain']) ?></span>
            <span class="text-gray-400">Ruta base</span><span class="text-white font-mono"><?= h($site['baseUrl'] ?: '/') ?></span>
            <span class="text-gray-400">HTTPS</span><span class="text-white"><?= $site['forceHttps'] ? '✓ Activado' : '— No forzado' ?></span>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="step" value="4">
        <button type="submit"
            class="w-full py-3 rounded-xl font-bold text-black bg-green-500 hover:bg-green-400 transition">
            <i class="fas fa-rocket mr-2"></i>Instalar ahora
        </button>
    </form>

    <?php else: // Mostrar resultados de la instalación ?>

    <h2 class="text-xl font-bold text-white mb-1">
        <?= $installResults['success'] ? '✓ Instalación completada' : '⚠ Instalación con errores' ?>
    </h2>
    <p class="text-sm mb-5 <?= $installResults['success'] ? 'text-green-400' : 'text-red-400' ?>">
        <?= $installResults['success']
            ? 'Todas las tablas y archivos de configuración fueron creados correctamente.'
            : 'Hubo errores críticos. Revisa los detalles y corrige antes de continuar.' ?>
    </p>

    <!-- SQL Results -->
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Base de datos</p>
    <div class="space-y-1 mb-4">
        <?php foreach ($installResults['sql'] as $r): ?>
        <div class="flex items-center justify-between bg-gray-900/60 rounded-lg px-3 py-2 text-sm">
            <span class="text-gray-300"><?= h($r['label']) ?></span>
            <span class="font-semibold <?= $r['ok'] ? 'text-green-400' : 'text-red-400' ?>">
                <?= h($r['message']) ?>
            </span>
        </div>
        <?php if (!$r['ok'] && !empty($r['details'])): ?>
            <?php foreach ($r['details'] as $d): if ($d['type'] === 'error'): ?>
            <div class="ml-4 bg-red-900/30 border border-red-500/30 rounded px-3 py-1.5 text-xs text-red-300">
                <strong>Error:</strong> <?= h($d['text']) ?>
                <?php if (!empty($d['stmt'])): ?><br><code class="text-gray-400"><?= h($d['stmt']) ?></code><?php endif; ?>
            </div>
            <?php endif; endforeach; ?>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <!-- Config Results -->
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Archivos de configuración</p>
    <div class="space-y-1 mb-4">
        <?php foreach ($installResults['config'] as $r): ?>
        <div class="flex items-center justify-between bg-gray-900/60 rounded-lg px-3 py-2 text-sm">
            <span class="text-gray-300 font-mono text-xs"><?= h($r['label']) ?></span>
            <span class="font-semibold <?= $r['ok'] ? 'text-green-400' : 'text-red-400' ?>">
                <?= $r['ok'] ? 'OK' : 'FALLÓ' ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Dirs Results -->
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Directorios de uploads</p>
    <div class="space-y-1 mb-6">
        <?php foreach ($installResults['dirs'] as $r): ?>
        <div class="flex items-center justify-between bg-gray-900/60 rounded-lg px-3 py-2 text-sm">
            <span class="text-gray-300 font-mono text-xs"><?= h($r['path']) ?></span>
            <span class="font-semibold <?= $r['ok'] ? 'text-green-400' : 'text-red-400' ?>">
                <?= h($r['status']) ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$installResults['success']): ?>
    <form method="POST">
        <input type="hidden" name="step" value="4">
        <button type="submit" class="w-full py-3 rounded-xl font-bold text-black bg-amber-500 hover:bg-amber-400 transition">
            <i class="fas fa-redo mr-2"></i>Reintentar instalación
        </button>
    </form>
    <?php endif; ?>

    <?php endif; ?>

    <!-- ════════════════════════════════════════════════════════════
         PASO 5 — Éxito
    ═══════════════════════════════════════════════════════════════ -->
    <?php elseif ($step === 5): ?>

    <div class="text-center mb-6">
        <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check-circle text-green-400 text-4xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-green-400 mb-2">¡Sistema instalado!</h2>
        <p class="text-gray-400 text-sm">BBR Fragrance está listo. Completa los pasos a continuación antes de abrir al público.</p>
    </div>

    <div class="space-y-3 mb-6">

        <div class="bg-red-900/40 border border-red-500/50 rounded-xl p-4">
            <p class="text-red-300 font-bold text-sm mb-1"><i class="fas fa-trash mr-2"></i>1. Eliminar este instalador</p>
            <p class="text-gray-300 text-xs">Borra el archivo <code class="bg-gray-800 px-1 rounded">install.php</code> del servidor ahora. Dejarlo accesible es un riesgo de seguridad.</p>
        </div>

        <div class="bg-gray-900/60 border border-gray-700 rounded-xl p-4">
            <p class="text-amber-400 font-semibold text-sm mb-1"><i class="fas fa-key mr-2"></i>2. Cambiar contraseña del admin</p>
            <p class="text-gray-400 text-xs">Usuario por defecto: <code class="text-white">admin</code> / Contraseña: <code class="text-white">admin123</code><br>
            Ir a <strong>Panel Admin → Usuarios → admin → Cambiar contraseña</strong></p>
        </div>

        <div class="bg-gray-900/60 border border-gray-700 rounded-xl p-4">
            <p class="text-amber-400 font-semibold text-sm mb-1"><i class="fas fa-envelope mr-2"></i>3. Configurar SMTP</p>
            <p class="text-gray-400 text-xs">Panel Admin → Configuración → Email SMTP<br>
            Host: <code class="text-white">smtp.hostinger.com</code> · Puerto: <code class="text-white">465</code></p>
        </div>

        <div class="bg-gray-900/60 border border-gray-700 rounded-xl p-4">
            <p class="text-amber-400 font-semibold text-sm mb-1"><i class="fas fa-clock mr-2"></i>4. Configurar cron job</p>
            <p class="text-gray-400 text-xs">cPanel → Cron Jobs → cada 30 min:<br>
            <code class="text-white text-xs">php /home/usuario/public_html/api/cron/cancel-expired-orders.php</code></p>
        </div>

    </div>

    <a href="pages/admin-login.html"
        class="block w-full py-3 text-center rounded-xl font-bold text-black bg-amber-500 hover:bg-amber-400 transition">
        <i class="fas fa-sign-in-alt mr-2"></i>Ir al panel administrativo
    </a>

    <?php endif; ?>

    </div><!-- /tarjeta -->

    <p class="text-center text-gray-600 text-xs mt-6">BBR Fragrance v1.0 — Instalador</p>
</div>

</body>
</html>
