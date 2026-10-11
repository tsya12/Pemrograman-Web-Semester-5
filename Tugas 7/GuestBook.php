<?php
declare(strict_types=1);

session_start();

cconst DB_HOST = 'localhost';
const DB_NAME = 'perpustakaan_db';
const DB_USER = 'root';
const DB_PASS = '';

require_once __DIR__ . 'Classes\GuestBook.php';