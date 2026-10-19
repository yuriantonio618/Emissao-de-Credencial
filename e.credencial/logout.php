<?php
require_once __DIR__ . '/includes/auth.php';

if (utilizadorAutenticado()) {
    registarEvento($_SESSION['user_type'], (int) $_SESSION['user_id'], 'Sessão terminada pelo utilizador');
}

session_unset();
session_destroy();
redirecionar('/index.php');
