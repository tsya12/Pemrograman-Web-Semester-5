<?php
declare(strict_types=1);

session_start();

cconst DB_HOST = 'localhost';
const DB_NAME = 'perpustakaan_db';
const DB_USER = 'root';
const DB_PASS = '';

require_once __DIR__ . 'Classes\GuestBook.php';

function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors  = [];
$old     = ['nama' => '', 'email' => '', 'pesan' => ''];
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

try {
    $pdo       = GuestBook::connect(DB_HOST, DB_NAME, DB_USER, DB_PASS);
    $guestbook = new GuestBook($pdo);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    http_response_code(500);
    exit('Tidak dapat terhubung ke basis data.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Token CSRF tidak valid.');
    }

    $old['nama']  = trim((string)($_POST['nama']  ?? ''));
    $old['email'] = trim((string)($_POST['email'] ?? ''));
    $old['pesan'] = trim((string)($_POST['pesan'] ?? ''));

    $errors = GuestBook::validate($old['nama'], $old['email'], $old['pesan']);

    if (!$errors) {
        try {
            $guestbook->simpan($old['nama'], $old['email'], $old['pesan']);
            // Perbarui token & hindari kirim ulang saat refresh (Post/Redirect/Get)
            $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
            $_SESSION['flash_success'] = 'Terima kasih, pesan Anda berhasil dikirim.';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $errors['umum'] = 'Terjadi kesalahan saat menyimpan pesan.';
        }
    }
}

try {
    $daftarPesan = $guestbook->ambilSemua();
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    $daftarPesan = [];
    $errors['umum'] = 'Gagal memuat daftar pesan.';
}
?>