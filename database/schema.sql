-- ============================================================
-- Shop Inventory & Sales System - Database Schema (3NF)
-- Target: MySQL 8 / MariaDB 10.4+ (XAMPP)
-- Import: phpMyAdmin > Import > choose this file > Go
-- ============================================================

DROP DATABASE IF EXISTS shop_inventory;
CREATE DATABASE shop_inventory
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE shop_inventory;

-- ------------------------------------------------------------
-- 1. users
-- Deactivate instead of delete so old sales keep their cashier.
-- ------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  email         VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','cashier') NOT NULL DEFAULT 'cashier',
  is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
  created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. categories
-- ------------------------------------------------------------
CREATE TABLE categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  description TEXT NULL,
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. products
-- stock_quantity is a running total kept in step with
-- stock_movements (always update both inside one transaction).
-- ------------------------------------------------------------
CREATE TABLE products (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  category_id    INT UNSIGNED  NOT NULL,
  name           VARCHAR(100)  NOT NULL,
  sku            VARCHAR(50)   NOT NULL,
  price          DECIMAL(10,2) NOT NULL,
  cost_price     DECIMAL(10,2) NULL,
  stock_quantity INT           NOT NULL DEFAULT 0,
  reorder_level  INT           NOT NULL DEFAULT 5,
  is_active      BOOLEAN       NOT NULL DEFAULT TRUE,
  created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_products_sku (sku),
  KEY idx_products_name (name),
  KEY idx_products_category (category_id),
  CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_products_price    CHECK (price >= 0),
  CONSTRAINT chk_products_cost     CHECK (cost_price IS NULL OR cost_price >= 0),
  CONSTRAINT chk_products_stock    CHECK (stock_quantity >= 0),
  CONSTRAINT chk_products_reorder  CHECK (reorder_level >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. sales (transaction header)
-- total_amount is stored on purpose: a receipt must never
-- change after the sale, even if prices change later.
-- Void a sale by setting status = 'voided', never delete it.
-- ------------------------------------------------------------
CREATE TABLE sales (
  id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id        INT UNSIGNED  NOT NULL,
  total_amount   DECIMAL(10,2) NOT NULL,
  payment_method ENUM('cash','card','mobile_money') NOT NULL DEFAULT 'cash',
  status         ENUM('completed','voided') NOT NULL DEFAULT 'completed',
  created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sales_created_at (created_at),
  KEY idx_sales_user (user_id),
  CONSTRAINT fk_sales_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_sales_total CHECK (total_amount >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. sale_items (junction: sales <-> products)
-- unit_price is copied from products.price at the time of sale.
-- Line subtotal = quantity * unit_price (calculated, not stored).
-- ------------------------------------------------------------
CREATE TABLE sale_items (
  id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  sale_id    INT UNSIGNED  NOT NULL,
  product_id INT UNSIGNED  NOT NULL,
  quantity   INT           NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_sale_product (sale_id, product_id),
  KEY idx_sale_items_product (product_id),
  CONSTRAINT fk_sale_items_sale
    FOREIGN KEY (sale_id) REFERENCES sales (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_sale_items_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_sale_items_qty   CHECK (quantity > 0),
  CONSTRAINT chk_sale_items_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. stock_movements (inventory audit log)
-- change_quantity: positive = stock in, negative = stock out.
-- sale_id is filled only for 'sale' and 'void' movements.
-- ------------------------------------------------------------
CREATE TABLE stock_movements (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  product_id      INT UNSIGNED NOT NULL,
  user_id         INT UNSIGNED NOT NULL,
  sale_id         INT UNSIGNED NULL,
  change_quantity INT          NOT NULL,
  movement_type   ENUM('sale','restock','adjustment','void') NOT NULL,
  note            VARCHAR(255) NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_movements_product_date (product_id, created_at),
  KEY idx_movements_sale (sale_id),
  CONSTRAINT fk_movements_product
    FOREIGN KEY (product_id) REFERENCES products (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_movements_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_movements_sale
    FOREIGN KEY (sale_id) REFERENCES sales (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT chk_movements_qty  CHECK (change_quantity <> 0),
  CONSTRAINT chk_movements_sale CHECK (
    movement_type NOT IN ('sale','void') OR sale_id IS NOT NULL
  )
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Views (handy for the low-stock page and daily report)
-- ------------------------------------------------------------
CREATE VIEW v_low_stock AS
SELECT p.id, p.name, p.sku, c.name AS category,
       p.stock_quantity, p.reorder_level
FROM products p
JOIN categories c ON c.id = p.category_id
WHERE p.is_active = TRUE
  AND p.stock_quantity <= p.reorder_level;

CREATE VIEW v_daily_sales AS
SELECT DATE(created_at) AS sale_date,
       COUNT(*)          AS transactions,
       SUM(total_amount) AS revenue
FROM sales
WHERE status = 'completed'
GROUP BY DATE(created_at);

-- ------------------------------------------------------------
-- Starter categories (optional, delete if you prefer)
-- Users are NOT seeded: create the first admin from PHP using
-- password_hash() so no plain passwords ever sit in this file.
-- ------------------------------------------------------------
INSERT INTO categories (name, description) VALUES
  ('Beverages', 'Drinks and juices'),
  ('Groceries', 'Everyday food items'),
  ('Toiletries', 'Personal care and cleaning');
