<?php
class OfferModel
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function getActive(string $searchQuery = '', int $categoryId = 0, string $date = ''): array
    {
        $sql = 'SELECT offers.*, users.name AS author_name, categories.name AS category_name 
                FROM offers 
                JOIN users ON offers.user_id = users.id 
                JOIN categories ON offers.category_id = categories.id 
                WHERE offers.status = "Активно" ';
        $params = [];
        if ($searchQuery !== '') {
            $sql .= 'AND (offers.title LIKE ? OR offers.description LIKE ?) ';
            $params[] = '%' . $searchQuery . '%';
            $params[] = '%' . $searchQuery . '%';
        }
        if ($categoryId > 0) {
            $sql .= 'AND offers.category_id = ? ';
            $params[] = $categoryId;
        }
        if ($date !== '') {
            $sql .= 'AND DATE(offers.created_at) = ? ';
            $params[] = $date;
        }
        $sql .= 'ORDER BY offers.created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function getCategories(): array
    {
        return $this->pdo->query('SELECT * FROM categories')->fetchAll();
    }
    public function getUserRespondedOfferIds(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT offer_id FROM responses WHERE sender_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    private function ensureTypeColumn(): void
    {
        $columns = $this->pdo->query('PRAGMA table_info(offers)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('type', $columns, true)) {
            $this->pdo->exec("ALTER TABLE offers ADD COLUMN type TEXT DEFAULT 'offer'");
        }
    }
    public function getAllForApi(?string $type = null, ?int $page = null, ?int $limit = null): array
    {
        $this->ensureTypeColumn();
        $sql = 'SELECT offers.*, users.name AS author_name, categories.name AS category_name 
                FROM offers 
                LEFT JOIN users ON offers.user_id = users.id 
                LEFT JOIN categories ON offers.category_id = categories.id 
                WHERE 1=1 ';
        $params = [];
        if ($type !== null && in_array($type, ['offer', 'request'], true)) {
            $sql .= 'AND offers.type = ? ';
            $params[] = $type;
        }
        $sql .= 'ORDER BY offers.id DESC';
        if ($page !== null && $limit !== null && $page > 0 && $limit > 0) {
            $offset = ($page - 1) * $limit;
            $sql .= ' LIMIT ? OFFSET ?';
            $params[] = $limit;
            $params[] = $offset;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    public function getById(int $id): ?array
    {
        $this->ensureTypeColumn();

        $sql = 'SELECT offers.*, users.name AS author_name, categories.name AS category_name 
                FROM offers 
                LEFT JOIN users ON offers.user_id = users.id 
                LEFT JOIN categories ON offers.category_id = categories.id 
                WHERE offers.id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $offer = $stmt->fetch();

        return $offer ?: null;
    }
    public function create(string $title, string $description, int $userId, string $type): int
    {
        $this->ensureTypeColumn();
        $defaultCategoryId = (int)$this->pdo->query('SELECT id FROM categories ORDER BY id ASC LIMIT 1')->fetchColumn();
        if ($defaultCategoryId <= 0) {
            $defaultCategoryId = 1;
        }

        $sql = 'INSERT INTO offers (user_id, category_id, title, description, contact_link, status, type) 
                VALUES (?, ?, ?, ?, "", "Активно", ?)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId, $defaultCategoryId, $title, $description, $type]);

        return (int)$this->pdo->lastInsertId();
    }
    public function update(int $id, string $title, string $description, string $type, string $status = 'Активно'): bool
    {
        $this->ensureTypeColumn();

        $sql = 'UPDATE offers SET title = ?, description = ?, type = ?, status = ? WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$title, $description, $type, $status, $id]);

        return $stmt->rowCount() > 0;
    }
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM offers WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->rowCount() > 0;
    }
}
