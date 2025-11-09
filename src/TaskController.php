<?php
class TaskController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }


    public function listProducts(): void
    {
        $sql = "SELECT * FROM product";
        $res = $this->conn->query($sql);
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) $rows[] = $r;
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($rows);
    }


    public function getProduct(int $id): void
    {
        $stmt = $this->conn->prepare("SELECT * FROM product WHERE product_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        header('Content-Type: application/json; charset=UTF-8');
        if ($res) echo json_encode($res); else {
            http_response_code(404);
            echo json_encode(['error'=>'Product not found']);
        }
    }

}

