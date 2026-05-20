-- GroceryGO: review trader replies + REVIEW_COMMENT table (Oracle / NEPSA)
-- Run once as schema owner. Skip ALTERs if columns exist; skip CREATE if table exists.
-- PHP equivalent: php scripts/apply-oracle-updates.php --skip-prices

ALTER TABLE REVIEW ADD (
    TRADER_REPLY VARCHAR2(1000)
);

ALTER TABLE REVIEW ADD (
    TRADER_REPLY_DATE DATE
);

CREATE TABLE REVIEW_COMMENT (
    COMMENT_ID     VARCHAR2(20)  NOT NULL,
    REVIEW_ID      VARCHAR2(20)  NOT NULL,
    COMMENT_BODY   VARCHAR2(1000) NOT NULL,
    COMMENT_DATE   DATE          DEFAULT SYSDATE NOT NULL,
    CUSTOMER_ID    VARCHAR2(20)  NOT NULL,
    CONSTRAINT PK_REVIEW_COMMENT PRIMARY KEY (COMMENT_ID)
);

COMMIT;
