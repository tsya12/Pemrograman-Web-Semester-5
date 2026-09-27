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

//Token CSRF disimpan di sesi
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

//Fungsi bantu
function e(string $value): string 
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function rupiah(float $value): string 
{
    return 'Rp ' . number_format($value, 2, ',', '.');
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

//pemrosesan formulir
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];

    //1. Validasi token CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Permintaan ditolak: token CSRF tidak valid.');
    }

    //2. Validasi jenis transaksi dengan match
    $rawType = $_POST['type'] ?? '';
    $type = match (is_string($rawType) ? $rawType : '') {
        'deposit' => 'deposit',
        'withdraw' => 'withdraw',
        default => null,
    };
    if ($type === null) {
        $errors[] = 'Jenis transaksi tidak valid.';
    }

    //3. Validasi jumlah: desimal positif
    $rawAmount = trim((string) ($_POST['amount'] ?? ''));
    $amount = 0.0;
    if ($rawAmount === '' || !preg_match('/^\d+(\.\d{1,2})?$/', $rawAmount)) {
        $errors[] = 'Jumlah harus berupa angka desimal positif, contoh: 150000 atau 150000.50.';
    } else {
        $amount = (float) $rawAmount;
        if ($amount <= 0) {
            $errors[] = 'Jumlah harus lebih besar dari 0.';
        }
    }

    //4. Proses transaksi bila tidak ada galat
    if ($errors === []) {
        try {
            $transaction = new Transaction(bin2hex(random_bytes(4)), $type, $amount);
            $transaction->process();
            $_SESSION['history'][] = $transaction->toArray();
            $_SESSION['flash'] = ['ok', 'Transaksi berhasil diproses.'];
        } catch (RuntimeException | InvalidArgumentException $ex) {
            $_SESSION['flash'] = ['error', $ex->getMessage()];
        }
    } else {
        $_SESSION['flash'] = ['error', implode(' ', $errors)];
    }

    //Perbarui token setelah setiap pengiriman formulir, lalu redirect
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

$balance = (float) $_SESSION['balance'];
$history = array_reverse($_SESSION['history']);
?>