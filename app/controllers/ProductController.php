<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/BaseApiController.php';

class ProductController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct();
        $this->api->require_jwt();            // every product action needs a valid access token
        $this->call->model('ProductModel');
    }

    /** GET /api/products */
    public function index()
    {
        $this->api->require_method('GET');
        $rows = $this->ProductModel->order_by('id', 'DESC') ?: [];
        $this->ok(array_map([$this, 'present'], $rows), 'Products loaded');
    }

    /** GET /api/products/{id} */
    public function show($id)
    {
        $this->api->require_method('GET');
        $this->ok($this->present($this->find_or_404($id)), 'Product loaded');
    }

    /** POST /api/products */
    public function store()
    {
        $this->api->require_method('POST');
        $data = $this->validated($this->json_input());

        $id = $this->ProductModel->insert($data);
        $this->ok($this->present($this->find_or_404($id)), 'Product added', 201);
    }

    /** PUT/PATCH /api/products/{id} */
    public function update($id)
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true)) {
            $this->api->respond_error('Method Not Allowed', 405);
        }
        $this->find_or_404($id);
        $data = $this->validated($this->json_input());

        $this->ProductModel->update((int) $id, $data);
        $this->ok($this->present($this->find_or_404($id)), 'Product updated');
    }

    /** DELETE /api/products/{id} */
    public function destroy($id)
    {
        $this->api->require_method('DELETE');
        $this->find_or_404($id);
        $this->ProductModel->delete((int) $id);
        $this->ok(null, 'Product deleted');
    }

    // ---------------------------------------------------------------

    private function find_or_404($id): array
    {
        if (!ctype_digit((string) $id)) {
            $this->api->respond_error('Product not found.', 404);
        }
        $row = $this->ProductModel->find((int) $id);
        if (!$row) {
            $this->api->respond_error('Product not found.', 404);
        }
        return $row;
    }

    private function validated(array $in): array
    {
        $name  = trim((string) ($in['product_name'] ?? ''));
        $desc  = isset($in['description']) ? trim((string) $in['description']) : '';
        $price = $in['price'] ?? null;
        $qty   = $in['quantity'] ?? null;
        $errors = [];

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['product_name'] = 'Enter a product name (up to 100 characters).';
        }
        if (!is_numeric($price) || (float) $price < 0 || (float) $price > 99999999.99) {
            $errors['price'] = 'Enter a price of 0 or more.';
        }
        if (is_bool($qty) || filter_var($qty, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 2147483647]]) === false) {
            $errors['quantity'] = 'Enter a whole number of 0 or more.';
        }
        if ($errors) {
            $this->fail_validation($errors);
        }

        return [
            'product_name' => $name,
            'description'  => $desc === '' ? null : $desc,
            'price'        => number_format((float) $price, 2, '.', ''),
            'quantity'     => (int) $qty,
        ];
    }

    private function present(array $r): array
    {
        return [
            'id'           => (int) $r['id'],
            'product_name' => $r['product_name'],
            'description'  => $r['description'],
            'price'        => (float) $r['price'],
            'quantity'     => (int) $r['quantity'],
            'created_at'   => $r['created_at'],
        ];
    }
}
