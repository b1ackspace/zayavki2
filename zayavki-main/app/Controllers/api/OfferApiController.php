<?php
require_once __DIR__ . '/../../Models/OfferModel.php';
class OfferApiController
{
    private OfferModel $model;
    public function __construct(PDO $pdo)
    {
        $this->model = new OfferModel($pdo);
    }
    private function response(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    public function index(): void
    {
        $type = isset($_GET['type']) ? trim($_GET['type']) : null;
        if ($type !== null && !in_array($type, ['offer', 'request'], true)) {
            $this->response(['error' => 'Некорректный параметр type. Допустимо: offer, request'], 400);
        }
        $page = isset($_GET['page']) ? (int)$_GET['page'] : null;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $offers = $this->model->getAllForApi($type, $page, $limit);
        $this->response($offers, 200);
    }
    public function show(int $id): void
    {
        if ($id <= 0) {
            $this->response(['error' => 'Некорректный ID публикации'], 400);
        }
        $offer = $this->model->getById($id);
        if (!$offer) {
            $this->response(['error' => 'Публикация не найдена'], 404);
        }
        $this->response($offer, 200);
    }
    public function store(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            $this->response(['error' => 'Ожидается заголовок Content-Type: application/json'], 400);
        }
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->response(['error' => 'Некорректный JSON'], 400);
        }
        $title = isset($data['title']) ? trim((string)$data['title']) : '';
        $description = isset($data['description']) ? trim((string)$data['description']) : '';
        $userId = $data['user_id'] ?? null;
        $type = isset($data['type']) ? trim((string)$data['type']) : '';
        if (
            $title === '' ||
            $description === '' ||
            mb_strlen($title) > 200 ||
            mb_strlen($description) > 5000 ||
            filter_var($userId, FILTER_VALIDATE_INT) === false ||
            (int)$userId <= 0 ||
            !in_array($type, ['offer', 'request'], true)
        ) {
            $this->response(['error' => 'Проверьте введённые данные'], 400);
        }
        $newId = $this->model->create($title, $description, (int)$userId, $type);
        $this->response([
            'message' => 'Публикация создана',
            'id' => $newId
        ], 201);
    }
    public function update(int $id): void
    {
        if ($id <= 0) {
            $this->response(['error' => 'Некорректный ID публикации'], 400);
        }
        $existing = $this->model->getById($id);
        if (!$existing) {
            $this->response(['error' => 'Публикация не найдена'], 404);
        }
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') === false) {
            $this->response(['error' => 'Ожидается заголовок Content-Type: application/json'], 400);
        }
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            $this->response(['error' => 'Некорректный JSON'], 400);
        }
        $title = isset($data['title']) ? trim((string)$data['title']) : '';
        $description = isset($data['description']) ? trim((string)$data['description']) : '';
        $type = isset($data['type']) ? trim((string)$data['type']) : ($existing['type'] ?? 'offer');
        $status = isset($data['status']) ? trim((string)$data['status']) : ($existing['status'] ?? 'Активно');
        if (
            $title === '' ||
            $description === '' ||
            mb_strlen($title) > 200 ||
            mb_strlen($description) > 5000 ||
            !in_array($type, ['offer', 'request'], true)
        ) {
            $this->response(['error' => 'Проверьте введённые данные'], 400);
        }
        $this->model->update($id, $title, $description, $type, $status);
        $this->response([
            'message' => 'Публикация обновлена',
            'id' => $id
        ], 200);
    }
    public function destroy(int $id): void
    {
        if ($id <= 0) {
            $this->response(['error' => 'Некорректный ID публикации'], 400);
        }

        $existing = $this->model->getById($id);
        if (!$existing) {
            $this->response(['error' => 'Публикация не найдена'], 404);
        }

        $this->model->delete($id);

        $this->response([
            'message' => 'Публикация удалена',
            'id' => $id
        ], 200);
    }
}
