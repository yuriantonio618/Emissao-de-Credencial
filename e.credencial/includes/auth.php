<?php
/**
 * SIGEUL — Autenticação e controlo de sessão
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

const SESSAO_INACTIVIDADE_SEGUNDOS = 25 * 60; // logout automático (lado servidor) após 25 min

function utilizadorAutenticado(): bool
{
    return isset($_SESSION['user_type'], $_SESSION['user_id']);
}

function exigirEstudante(): void
{
    verificarInactividade();
    if (!utilizadorAutenticado() || $_SESSION['user_type'] !== 'estudante') {
        redirecionar('/index.php');
    }
}

function exigirAdmin(): void
{
    verificarInactividade();
    if (!utilizadorAutenticado() || $_SESSION['user_type'] !== 'admin') {
        redirecionar('/index.php');
    }
}

function verificarInactividade(): void
{
    if (utilizadorAutenticado()) {
        $ultima = $_SESSION['ultimo_acesso'] ?? time();
        if ((time() - $ultima) > SESSAO_INACTIVIDADE_SEGUNDOS) {
            $tipo = $_SESSION['user_type'];
            $id   = $_SESSION['user_id'] ?? 0;
            registarEvento($tipo, (int) $id, 'Sessão terminada automaticamente (inactividade)');
            session_unset();
            session_destroy();
            redirecionar('/index.php?expirada=1');
        }
    }
    $_SESSION['ultimo_acesso'] = time();
}

function estudanteActual(): ?array
{
    if (!utilizadorAutenticado() || $_SESSION['user_type'] !== 'estudante') {
        return null;
    }
    static $cache = null;
    if ($cache !== null) return $cache;

    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT e.*, c.nome AS curso_nome, c.faculdade, c.departamento
         FROM estudantes e JOIN cursos c ON c.id = e.curso_id
         WHERE e.id = ?'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

function adminActual(): ?array
{
    if (!utilizadorAutenticado() || $_SESSION['user_type'] !== 'admin') {
        return null;
    }
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM administradores WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}
