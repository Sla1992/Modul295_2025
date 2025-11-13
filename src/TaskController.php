<?php
class TaskController
{
    // Database connection instance
    private mysqli $conn;
    // Constructor to initialize the database connection
    public function __construct(mysqli $conn)
    {
        // Set the database connection instance
        $this->conn = $conn;
    }

    //Products------------------------------------------------------------------------
    //Get a List of all the products in the table
    public function listProducts(): void
    {
        // Query to select all products
        $sql = "SELECT * FROM product";
        $res = $this->conn->query($sql);
        $rows = [];
        if ($res) {
            // Fetch all products and store them in an array
            while ($r = $res->fetch_assoc())
                $rows[] = $r;
        }
        // Set the response header to indicate JSON content
        header('Content-Type: application/json; charset=UTF-8');
        // Output the list of products in JSON format
        echo json_encode($rows);
    }



    //Get Info from product by a ID
    public function getProduct(int $id): void
    {
        // Prepare a SQL statement to select a product by ID
        $stmt = $this->conn->prepare("SELECT * FROM product WHERE product_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        // Fetch the product data
        $res = $stmt->get_result()->fetch_assoc();
        header('Content-Type: application/json; charset=UTF-8');
        // Output the product data in JSON format
        if ($res)
            echo json_encode($res);
        else {
            // If the product is not found, return a 404 error
            http_response_code(404);
            // Return an error message in JSON format
            echo json_encode(['error' => 'Product not found']);
        }
    }


    //Create a new product (still don't know what "sku" is)
    public function createProduct(array $data): void
    {

        // Prepare a SQL statement to insert a new product
        $sql = "INSERT INTO product (sku, active, id_category, name, image, description, price, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        // Bind parameters from the input data
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
        // Execute the statement and check for success
        if ($stmt->execute()) {
            // If successful, return the insert ID with a 201 status code
            http_response_code(201);
            // Output the insert ID in JSON format
            echo json_encode(['insertId' => $stmt->insert_id]);
        } else {
            // If there was an error, return a 500 status code and the error message
            http_response_code(500);
            // Output the error message in JSON format
            echo json_encode(['error' => $stmt->error]);
        }
    }


    //Alter a Product chosen by ID
    public function updateProduct(int $id, array $data): void
    {
        // Prepare a SQL statement to update a product by ID
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
            // Output the number of affected rows in JSON format
            echo json_encode(['affected' => $stmt->affected_rows]);
        } else {
            // If there was an error, return a 500 status code and the error message
            http_response_code(500);
            // Output the error message in JSON format
            echo json_encode(['error' => $stmt->error]);
        }
    }


    //Delete a Product by ID
    public function deleteProduct(int $id): void
    {
        // Prepare a SQL statement to delete a product by ID
        $stmt = $this->conn->prepare("DELETE FROM product WHERE product_id = ?");
        $stmt->bind_param('i', $id);
        // Execute the statement and check for success
        if ($stmt->execute()) {
            // Output the number of deleted rows in JSON format
            echo json_encode(['deleted' => $stmt->affected_rows]);
        } else {
            // If there was an error, return a 500 status code and the error message
            http_response_code(500);
            // Output the error message in JSON format
            echo json_encode(['error' => $stmt->error]);
        }
    }
    //Category------------------------------------------------------------------------
    public function listCategories(): void
    {
        //Query to select all categories
        $sql = "SELECT * FROM category";
        $res = $this->conn->query($sql);
        $rows = [];
        //Fetch all categories and store them in an array
        if ($res)
            while ($r = $res->fetch_assoc())
                $rows[] = $r;
        //Set the response header to indicate JSON content
        header('Content-Type: application/json; charset=UTF-8');
        //Output the list of categories in JSON format
        echo json_encode($rows);
    }

    //Get Info from category by a ID
    public function getCategory(int $id): void
    {
        //Prepare a SQL statement to select a category by ID
        $stmt = $this->conn->prepare("SELECT * FROM category WHERE category_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        //Fetch the category data
        $res = $stmt->get_result()->fetch_assoc();
        header('Content-Type: application/json; charset=UTF-8');
        //Output the category data in JSON format
        if ($res)
            echo json_encode($res);
        else {
            //If the category is not found, return a 404 error
            http_response_code(404);
            //Return an error message in JSON format
            echo json_encode(['error' => 'Category not found']);
        }
    }

    //Create a new category
    public function createCategory(array $data): void
    {
        //Prepare a SQL statement to insert a new category
        $active = $data['active'] ?? 1;
        $name = $data['name'] ?? '';
        //Insert into category table
        $stmt = $this->conn->prepare("INSERT INTO category (active, name) VALUES (?, ?)");
        $stmt->bind_param('is', $active, $name);
        //Execute the statement and check for success
        if ($stmt->execute()) {
            //If successful, return the insert ID with a 201 status code
            http_response_code(201);
            //Output the insert ID in JSON format
            echo json_encode(['insertId' => $stmt->insert_id]);
        } else {
            //If there was an error, return a 500 status code and the error message
            http_response_code(500);
            //Output the error message in JSON format
            echo json_encode(['error' => $stmt->error]);
        }
    }

    //Alter a category chosen by ID
    public function updateCategory(int $id, array $data): void
    {
        //Prepare a SQL statement to update a category by ID
        $active = $data['active'] ?? 1;
        $name = $data['name'] ?? '';
        //Bind parameters and execute the statement
        $stmt = $this->conn->prepare("UPDATE category SET active=?, name=? WHERE category_id=?");
        $stmt->bind_param('isi', $active, $name, $id);
        //Execute the statement and check for success
        if ($stmt->execute())
            echo json_encode(['affected' => $stmt->affected_rows]);
        else {
            //If there was an error, return a 500 status code and the error message
            http_response_code(500);
            //Output the error message in JSON format
            echo json_encode(['error' => $stmt->error]);
        }
    }

    //Delete a category by ID
    public function deleteCategory(int $id): void
    {
        //Prepare a SQL statement to delete a category by ID
        $stmt = $this->conn->prepare("DELETE FROM category WHERE category_id=?");
        //Bind parameters and execute the statement
        $stmt->bind_param('i', $id);
        //Execute the statement and check for success
        if ($stmt->execute())
            echo json_encode(['deleted' => $stmt->affected_rows]);
        else {
            //If there was an error, return a 500 status code and the error message
            http_response_code(500);
            //Output the error message in JSON format
            echo json_encode(['error' => $stmt->error]);
        }
    }


}

