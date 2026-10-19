<?php
/**
 * SIGEUL — Utilitários de upload de documentos
 */

const UPLOAD_DIR_RELATIVO = '/uploads/documentos/';
const UPLOAD_TAMANHO_MAX  = 5 * 1024 * 1024; // 5 MB
const UPLOAD_TIPOS_PERMITIDOS = [
    'application/pdf' => 'pdf',
    'image/jpeg'       => 'jpg',
    'image/png'        => 'png',
];

/**
 * Processa um upload vindo de $_FILES e devolve [sucesso(bool), mensagemOuNomeFicheiro].
 */
function processarUploadDocumento(array $ficheiro, int $estudanteId): array
{
    if (!isset($ficheiro['error']) || is_array($ficheiro['error'])) {
        return [false, 'Pedido de ficheiro inválido.'];
    }
    if ($ficheiro['error'] === UPLOAD_ERR_NO_FILE) {
        return [false, 'Nenhum ficheiro foi seleccionado.'];
    }
    if ($ficheiro['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Ocorreu um erro ao carregar o ficheiro.'];
    }
    if ($ficheiro['size'] > UPLOAD_TAMANHO_MAX) {
        return [false, 'O ficheiro excede o tamanho máximo permitido (5 MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($ficheiro['tmp_name']);

    if (!isset(UPLOAD_TIPOS_PERMITIDOS[$mime])) {
        return [false, 'Tipo de ficheiro não permitido. Envie apenas PDF, JPG ou PNG.'];
    }

    $extensao = UPLOAD_TIPOS_PERMITIDOS[$mime];
    $nomeFicheiro = 'doc_' . $estudanteId . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
    $destino = __DIR__ . '/../uploads/documentos/' . $nomeFicheiro;

    if (!move_uploaded_file($ficheiro['tmp_name'], $destino)) {
        return [false, 'Não foi possível guardar o ficheiro no servidor.'];
    }

    return [true, $nomeFicheiro];
}

function caminhoPublicoDocumento(string $nomeFicheiro): string
{
    return BASE_URL . UPLOAD_DIR_RELATIVO . $nomeFicheiro;
}

function formatarTamanhoFicheiro(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    return number_format($bytes / (1024 * 1024), 1) . ' MB';
}
