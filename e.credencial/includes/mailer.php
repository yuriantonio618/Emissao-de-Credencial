<?php
/**
 * SIGEUL — Envio de e-mail (abstracção)
 * -------------------------------------------------------------
 * Tenta enviar através da função mail() nativa do PHP (que requer
 * um servidor SMTP configurado em php.ini — sendmail_path / SMTP).
 * Independentemente do resultado, regista sempre uma cópia na tabela
 * emails_log para que o administrador consiga consultar e testar o
 * fluxo mesmo sem um servidor de e-mail real configurado (modo
 * desenvolvimento) em admin/emails.php.
 *
 * Para produção: substitua o corpo de enviarEmail() por uma
 * integração SMTP (PHPMailer, Symfony Mailer, etc.) mantendo a
 * mesma assinatura da função.
 */

function enviarEmail(string $destinatario, string $assunto, string $corpoHtml): bool
{
    $pdo = getPDO();
    $enviado = false;

    if (function_exists('mail')) {
        $cabecalhos = "MIME-Version: 1.0\r\n" .
                      "Content-Type: text/html; charset=UTF-8\r\n" .
                      "From: " . APP_NOME . ' <naoresponder@' . (parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? 'sigeul.local'), PHP_URL_HOST)) . ">\r\n";
        // @ para suprimir avisos quando não existe agente de transporte configurado no ambiente local
        $enviado = @mail($destinatario, $assunto, $corpoHtml, $cabecalhos);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO emails_log (destinatario, assunto, corpo, estado) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$destinatario, $assunto, $corpoHtml, $enviado ? 'Enviado' : 'Simulado']);

    return true; // O pedido é sempre "aceite" do ponto de vista do utilizador (nunca revela se o e-mail existe)
}

function gerarTokenSeguro(): string
{
    return bin2hex(random_bytes(32));
}
