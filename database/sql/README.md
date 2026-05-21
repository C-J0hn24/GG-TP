# Oracle SQL scripts (GroceryGO / NEPSA)

Run these in **SQL*Plus** or **SQL Developer** as your schema user (e.g. `NEPSA`).

Recommended order:

| Order | File | Purpose |
|-------|------|---------|
| 1 | `oracle-email-verification.sql` | `USERS` + `VERIFICATION` columns for OTP login |
| 2 | `oracle-collection-slot.sql` | `COLLECTION_SLOT.PICKUP_LOCATION` |
| 3 | `oracle-review-comments.sql` | `REVIEW` trader reply + `REVIEW_COMMENT` table |
| 4 | `oracle-trader-approval.sql` | `TRADER.APPROVAL_STATUS` for trader portal login |

**PHP alternative (idempotent):**

```bash
php artisan grocery:email-verification-schema
php scripts/apply-oracle-updates.php --skip-prices
```

**Trader portal notes:** see `trader-portal/sql/oracle_notes.sql` (optional sequences / product columns).

If a statement fails with **ORA-01430** (column already exists) or **ORA-00955** (table exists), skip that block and continue.
