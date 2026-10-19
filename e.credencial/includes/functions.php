<?php
/**
 * SIGEUL — Funções auxiliares
 */

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function formatarMoeda(float $valor): string
{
    return number_format($valor, 2, ',', '.') . ' MT';
}

function formatarDataPt(?string $data): string
{
    if (!$data) return '—';
    $ts = strtotime($data);
    if (!$ts) return '—';
    return date('d/m/Y', $ts);
}

function badgeEstadoNota(float $nota): string
{
    return $nota >= 10 ? 'ok' : 'bad';
}

function textoEstadoNota(float $nota): string
{
    return $nota >= 10 ? 'Aprovado' : 'Reprovado';
}

/** Regista um evento no histórico de sessões do utilizador actual */
function registarEvento(string $tipo, int $id, string $evento): void
{
    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO sessoes_log (utilizador_tipo, utilizador_id, evento, ip) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$tipo, $id, $evento, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) {
        // Falha silenciosa: o registo de log nunca deve interromper o sistema
    }
}

/** Redirecciona para outra página do sistema (caminho relativo à raiz do SIGEUL, ex: '/index.php') */
function redirecionar(string $caminho): never
{
    header('Location: ' . BASE_URL . $caminho);
    exit;
}

/** Gera as iniciais de um nome para usar em avatares */
function iniciais(string $nome): string
{
    $partes = preg_split('/\s+/', trim($nome));
    $ini = '';
    if (!empty($partes[0])) $ini .= mb_substr($partes[0], 0, 1);
    if (count($partes) > 1) $ini .= mb_substr(end($partes), 0, 1);
    return mb_strtoupper($ini);
}
