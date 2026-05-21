-- GroceryGo: trader sign-in approval (Oracle / NEPSA)
-- Run once: sqlplus NEPSA/password@192.168.1.64:1521/XEPDB1 @database/sql/oracle-trader-approval.sql

ALTER TABLE TRADER ADD (
    APPROVAL_STATUS VARCHAR2(20) DEFAULT 'APPROVED' NOT NULL
);

UPDATE TRADER SET APPROVAL_STATUS = 'APPROVED' WHERE APPROVAL_STATUS IS NULL;

COMMIT;
