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

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buku Tamu Perpustakaan</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; color: #222; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 16px; }
        h1, h2 { color: #1f3a5f; }
        .card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.1); margin-bottom: 24px; }
        label { display: block; margin-top: 12px; font-weight: bold; }
        input[type=text], input[type=email], textarea { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; border: 1px solid #bbb; border-radius: 4px; font: inherit; }
        textarea { min-height: 100px; resize: vertical; }
        button { margin-top: 16px; padding: 10px 20px; background: #1f3a5f; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
        button:hover { background: #2c5282; }
        .error { color: #b00020; font-size: .9em; margin-top: 4px; }
        .alert-success { background: #e6f4ea; color: #1e6b34; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        .alert-error { background: #fdecea; color: #b00020; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; vertical-align: top; }
        th { background: #eef2f7; }
        .pesan { white-space: pre-wrap; word-break: break-word; }
    </style>
</head>
<body>
<div class="container">
    <h1>Buku Tamu Perpustakaan</h1>

    <div class="card">
        <h2>Tulis Pesan</h2>

        <?php if ($success): ?>
            <div class="alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if (isset($errors['umum'])): ?>
            <div class="alert-error"><?= e($errors['umum']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="nama">Nama</label>
            <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($old['nama']) ?>">
            <?php if (isset($errors['nama'])): ?><div class="error"><?= e($errors['nama']) ?></div><?php endif; ?>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="150" value="<?= e($old['email']) ?>">
            <?php if (isset($errors['email'])): ?><div class="error"><?= e($errors['email']) ?></div><?php endif; ?>

            <label for="pesan">Pesan</label>
            <textarea id="pesan" name="pesan" maxlength="2000"><?= e($old['pesan']) ?></textarea>
            <?php if (isset($errors['pesan'])): ?><div class="error"><?= e($errors['pesan']) ?></div><?php endif; ?>

            <button type="submit">Kirim Pesan</button>
        </form>
    </div>

    <div class="card">
        <h2>Daftar Pesan</h2>
        <table>
            <thead>
                <tr><th>No</th><th>Nama</th><th>Email</th><th>Pesan</th><th>Tanggal</th></tr>
            </thead>
            <tbody>
            <?php if (!$daftarPesan): ?>
                <tr><td colspan="5">Belum ada pesan.</td></tr>
            <?php else: ?>
                <?php foreach ($daftarPesan as $i => $row): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= e($row['nama']) ?></td>
                        <td><?= e($row['email']) ?></td>
                        <td class="pesan"><?= e($row['pesan']) ?></td>
                        <td><?= e($row['tanggal_kirim']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
