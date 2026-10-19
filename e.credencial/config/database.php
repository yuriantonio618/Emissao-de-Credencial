<?php
/**
 * SIGEUL — Ligação à base de dados
 * -------------------------------------------------------------
 * Ajuste as constantes abaixo consoante o seu ambiente (XAMPP,
 * WAMP, servidor de produção, etc). Por omissão está configurado
 * para um MySQL local sem senha de root, típico de XAMPP/WAMP.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'sigeul');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Nome da instituição / sistema — usado em vários pontos da interface
define('APP_NOME', 'SIGEUL');
define('APP_VERSAO', 'v1.0');
define('APP_INSTITUICAO', 'Universidade Licungo');

/**
 * BASE_URL — caminho base do sistema dentro do servidor web.
 * Calculado automaticamente para que o SIGEUL funcione tanto na raiz
 * do domínio (http://localhost/) como numa subpasta
 * (ex: http://localhost/sigeul/), sem qualquer configuração manual.
 */
if (!defined('BASE_URL')) {
    $raizAplicacao = str_replace('\\', '/', dirname(__DIR__));       // .../htdocs/sigeul
    $raizServidor  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\')); // .../htdocs

    $baseCalculada = '';
    if ($raizServidor !== '' && stripos($raizAplicacao, $raizServidor) === 0) {
        // Comparação insensível a maiúsculas/minúsculas (relevante no Windows/XAMPP,
        // onde o DOCUMENT_ROOT pode ter uma capitalização de unidade diferente).
        $baseCalculada = substr($raizAplicacao, strlen($raizServidor));
    }
    define('BASE_URL', rtrim($baseCalculada, '/'));
}


function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opcoes = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opcoes);
        } catch (PDOException $e) {
            http_response_code(500);
            die(
                '<div style="font-family:sans-serif;max-width:640px;margin:60px auto;padding:24px 28px;' .
                'border:1px solid #f3c6c2;background:#fdecea;border-radius:12px;color:#4a1512;">' .
                '<h2 style="margin-top:0;color:#b3261e;">Erro de ligação à base de dados</h2>' .
                '<p>Não foi possível ligar ao MySQL. Verifique se o servidor MySQL está em execução ' .
                'e se as credenciais em <code>config/database.php</code> estão correctas.</p>' .
                '<p style="font-size:13px;color:#8a3a34;">Detalhe técnico: ' . htmlspecialchars($e->getMessage()) . '</p>' .
                '</div>'
            );
        }
    }

    return $pdo;
}
