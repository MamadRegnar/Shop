<?php

header('Content-Type: application/json; charset=utf-8');

$host = 'regnardb-yvs-service';
$dbname = 'regnardbpmo_db';
$dbuser = 'root';
$dbpass = 'dWqbnmG6H9tLcAacJM5u';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);

    echo json_encode([
        'ok' => false,
        'message' => 'Database connection failed'
    ]);

    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'status':

        echo json_encode([
            'ok' => true,
            'app' => 'REGNAR CHAT',
            'status' => 'online'
        ]);

        break;

    case 'register':

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);

            echo json_encode([
                'ok' => false,
                'message' => 'POST required'
            ]);

            break;
        }

        $username = trim($_POST['user'] ?? '');
        $password = $_POST['pass'] ?? '';
        $name = trim($_POST['name'] ?? '');

        if (
            !preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username) ||
            !is_string($password) ||
            strlen($password) < 10 ||
            strlen($password) > 72 ||
            !is_string($name) ||
            $name === '' ||
            mb_strlen($name) > 64
        ) {
            http_response_code(422);

            echo json_encode([
                'ok' => false,
                'message' => 'Invalid input'
            ]);

            break;
        }

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users
                (username, password_hash, display_name)
                VALUES (?, ?, ?)'
            );

            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                $name
            ]);

            http_response_code(201);

            echo json_encode([
                'ok' => true,
                'message' => 'Account created'
            ]);

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                http_response_code(409);

                echo json_encode([
                    'ok' => false,
                    'message' => 'Username already exists'
                ]);
            } else {
                error_log($e->getMessage());

                http_response_code(500);

                echo json_encode([
                    'ok' => false,
                    'message' => 'Server error'
                ]);
            }
        }

        break;

    default:

        http_response_code(400);

        echo json_encode([
            'ok' => false,
            'message' => 'Unknown action'
        ]);

        break;
}
