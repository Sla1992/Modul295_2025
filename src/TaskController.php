<?php
class TaskController
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

//Get a List of all the products in the table
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



//Get Info from product by a ID
    public function getProduct(int $id): void
    {
        //To Do: Check my Code for SQL Injection possibilities
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


    //Create a new product (still don't know what "sku" is)
    public function createProduct(array $data): void
    {

        //To Do: Check my Code for SQL Injection possibilities
        $sql = "INSERT INTO product (sku, active, id_category, name, image, description, price, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);

        $sku = $data['sku'] ?? '';
        $active = $data['active'] ?? 1;
        $id_category = $data['id_category'] ?? null;
        $name = $data['name'] ?? '';
        $image = $data['image'] ?? '';
        $description = $data['description'] ?? '';
        $price = $data['price'] ?? 0;
        $stock = $data['stock'] ?? 0;
        // "siisssdii" = s-string, i-integer, d-double (Bind Parameter for my Table)
        $stmt->bind_param('siisssdi', $sku, $active, $id_category, $name, $image, $description, $price, $stock);
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(['insertId' => $stmt->insert_id]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $stmt->error]);
        }
    }


//Alter a Product chosen by ID
    public function updateProduct(int $id, array $data): void
    {
        //To Do: Check my Code for SQL Injection possibilities
        $sql = "UPDATE product SET sku=?, active=?, id_category=?, name=?, image=?, description=?, price=?, stock=? WHERE product_id=?";
        $stmt = $this->conn->prepare($sql);
        $sku = $data['sku'] ?? '';
        $active = $data['active'] ?? 1;
        $id_category = $data['id_category'] ?? null;
        $name = $data['name'] ?? '';
        $image = $data['image'] ?? '';
        $description = $data['description'] ?? '';
        $price = $data['price'] ?? 0;
        $stock = $data['stock'] ?? 0;
        // "siisssdii" = s-string, i-integer, d-double (Bind Parameter for my Table)
        $stmt->bind_param('siisssdii', $sku, $active, $id_category, $name, $image, $description, $price, $stock, $id);
        if ($stmt->execute()) {
            echo json_encode(['affected' => $stmt->affected_rows]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $stmt->error]);
        }
    }


    //Delete a Product by ID
    public function deleteProduct(int $id): void
    {
        //To Do: Check my Code for SQL Injection possibilities
        $stmt = $this->conn->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['deleted' => $stmt->affected_rows]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $stmt->error]);
        }
    }


}

