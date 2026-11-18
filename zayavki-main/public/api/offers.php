<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/Controllers/Api/OfferApiController.php';
try {
    $pdo = getDatabaseConnection();
    $controller = new OfferApiController($pdo);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $controller->show($id);
        } else {
            $controller->index();
        }
    } elseif ($method === 'POST') {
        $controller->store();
    } elseif ($method === 'PUT') {
        $controller->update($id);
    } elseif ($method === 'DELETE') {
        $controller->destroy($id);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    error_log('API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Внутренняя ошибка сервера'], JSON_UNESCAPED_UNICODE);
}