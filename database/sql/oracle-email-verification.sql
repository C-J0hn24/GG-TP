-- GroceryGO: email verification columns (Oracle / NEPSA)
-- Run once in SQL*Plus or SQL Developer as schema owner NEPSA.
-- Safe to re-run only if columns are missing (otherwise ORA-01430).
-- PHP equivalent: php artisan grocery:email-verification-schema

-- USERS: verified flag
ALTER TABLE USERS ADD (
    EMAIL_VERIFIED NUMBER(1) DEFAULT 0 NOT NULL
);

ALTER TABLE USERS ADD (
    EMAIL_VERIFIED_AT DATE
);

-- Existing accounts: allow login without re-verification
UPDATE USERS SET EMAIL_VERIFIED = 1, EMAIL_VERIFIED_AT = SYSDATE WHERE EMAIL_VERIFIED = 0;

-- VERIFICATION: OTP + metadata
ALTER TABLE VERIFICATION ADD (
    VERIFICATION_CODE VARCHAR2(6)
);

ALTER TABLE VERIFICATION ADD (
    EMAIL VARCHAR2(100)
);

ALTER TABLE VERIFICATION ADD (
    PURPOSE VARCHAR2(30)
);

ALTER TABLE VERIFICATION ADD (
    STATUS VARCHAR2(20)
);

COMMIT;
