<?php

declare(strict_types=1);

require_once __DIR__ . '/Transaction.php';

//Sesi & header keamanan
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'unsafe-inline'");

$_SESSION['balance'] ??= 0.0;
$_SESSION['history'] ??= [];