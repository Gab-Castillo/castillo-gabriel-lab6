<?php
class Create_products_table {
    private $_lava;

    public function __construct()
    {
        $this->_lava = lava_instance();
        $this->_lava->call->dbforge();
    }

    public function up()
    {
        if ($this->_lava->dbforge->table_exists('products')) {
            return;
        }

        // Plain SQL keeps the exact column types the lab requires
        // (DECIMAL(10,2) and a TIMESTAMP that defaults to now).
        $this->_lava->db->raw("
            CREATE TABLE `products` (
                `id`           INT           NOT NULL AUTO_INCREMENT,
                `product_name` VARCHAR(100)  NOT NULL,
                `description`  TEXT          NULL,
                `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `quantity`     INT           NOT NULL DEFAULT 0,
                `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down()
    {
        $this->_lava->dbforge->drop_table('products');
    }
}
