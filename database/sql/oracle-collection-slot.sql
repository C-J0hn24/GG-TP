-- GroceryGO: collection slot pickup location (Oracle / NEPSA)
-- Run once as schema owner. Skip any statement if column already exists (ORA-01430).
-- PHP equivalent: php scripts/apply-oracle-updates.php --skip-prices

ALTER TABLE COLLECTION_SLOT ADD (
    PICKUP_LOCATION VARCHAR2(120)
);

COMMIT;
