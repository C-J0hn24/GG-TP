-- Optional Oracle DDL snippets for GroceryGo trader portal (run as schema owner).
-- Table/column names must match your physical ERD.

-- Quote reserved-word identifiers when creating tables:
-- CREATE TABLE "USER" (...);

-- Recommended: proper sequences instead of MAX(id)+1 in PHP.
/*
CREATE SEQUENCE user_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE trader_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE shop_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE product_seq START WITH 1 INCREMENT BY 1 NOCACHE;
*/

-- Optional PRODUCT extensions (uncomment if you want first-class columns instead of encoding in DESCRIPTION).
/*
ALTER TABLE product ADD (
  unit VARCHAR2(40),
  max_per_order NUMBER(10) DEFAULT 10,
  listing_status VARCHAR2(20) DEFAULT 'draft',
  availability VARCHAR2(20) DEFAULT 'both'
);
*/
