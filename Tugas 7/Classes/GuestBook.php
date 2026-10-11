<?php
declare(strict_types=1);

class GuestBook
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }


    public static function connect(string $host, string $db, string $user, string $pass): PDO
    {
        $dsn = "mysql:host=localhost;dbname=perpustakaan_db;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        return new PDO($dsn, $user, $pass, $options);
    }

    public static function validate(string $nama, string $email, string $pesan): array
    {
        $errors = [];

        $nama  = trim($nama);
        $email = trim($email);
        $pesan = trim($pesan);

        if ($nama === '') {
            $errors['nama'] = 'Nama tidak boleh kosong.';
        } elseif (mb_strlen($nama) > 100) {
            $errors['nama'] = 'Nama maksimal 100 karakter.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        } elseif (mb_strlen($email) > 150) {
            $errors['email'] = 'Email maksimal 150 karakter.';
        }

        if (mb_strlen($pesan) < 5) {
            $errors['pesan'] = 'Pesan minimal 5 karakter.';
        } elseif (mb_strlen($pesan) > 2000) {
            $errors['pesan'] = 'Pesan maksimal 2000 karakter.';
        }

        return $errors;
    }

    public function simpan(string $nama, string $email, string $pesan): bool
    {
        $sql = 'INSERT INTO buku_tamu (nama, email, pesan, tanggal_kirim)
                VALUE (:nama, :email, :pesan, NOW())';
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':nama' => trim($nama),
            ':email'=> trim($email),
            ':pesan'=> trim($pesan),
        ]);
    }

    public function ambilSemua(int $limit = 50): array
    {
        $sql = 'SELECT id, nama, email, pesan, tanggal_kirim
                FROM buku_tamu
                ORDER BY tanggal_kirim DESC, id DESC
                LIMIT :limit';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}