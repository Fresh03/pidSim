<?php
// Afișăm erorile în caz că mai apare ceva
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }
session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (strlen($username) < 3 || strlen($username) > 50) jsonResponse(['error' => 'Username intre 3 si 50 caractere.']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Email invalid.']);
        if (strlen($password) < 6) jsonResponse(['error' => 'Parola minim 6 caractere.']);

        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) jsonResponse(['error' => 'Username sau email exista deja.']);

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);

        $userId = $db->lastInsertId();
        $_SESSION['user_id'] = $userId; $_SESSION['username'] = $username;
        jsonResponse(['success' => true, 'username' => $username, 'user_id' => $userId]);
        break;

    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($username) || empty($password)) jsonResponse(['error' => 'Completeaza campurile.']);

        $db = getDB();
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) jsonResponse(['error' => 'Credențiale incorecte.']);

        $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
        $_SESSION['user_id'] = $user['id']; $_SESSION['username'] = $user['username'];
        jsonResponse(['success' => true, 'username' => $user['username'], 'user_id' => $user['id']]);
        break;

    case 'logout':
        session_destroy();
        jsonResponse(['success' => true]);
        break;

    case 'check':
        if (!empty($_SESSION['user_id'])) jsonResponse(['logged_in' => true, 'username' => $_SESSION['username'], 'user_id' => $_SESSION['user_id']]);
        else jsonResponse(['logged_in' => false]);
        break;

    default:
        jsonResponse(['error' => 'Actiune necunoscuta.'], 400);
}