-- Add payment-related fields to invoice tables
ALTER TABLE flight_invoice
    ADD COLUMN PaymentStatus ENUM('unpaid','pending','paid','failed') DEFAULT 'unpaid',
    ADD COLUMN PaymentMethod VARCHAR(32) NULL,
    ADD COLUMN VnpTxnRef VARCHAR(64) NULL,
    ADD COLUMN VnpBankCode VARCHAR(32) NULL,
    ADD COLUMN VnpPayDate DATETIME NULL;

ALTER TABLE bus_invoice
    ADD COLUMN PaymentStatus ENUM('unpaid','pending','paid','failed') DEFAULT 'unpaid',
    ADD COLUMN PaymentMethod VARCHAR(32) NULL,
    ADD COLUMN VnpTxnRef VARCHAR(64) NULL,
    ADD COLUMN VnpBankCode VARCHAR(32) NULL,
    ADD COLUMN VnpPayDate DATETIME NULL;

ALTER TABLE room_invoice
    ADD COLUMN PaymentStatus ENUM('unpaid','pending','paid','failed') DEFAULT 'unpaid',
    ADD COLUMN PaymentMethod VARCHAR(32) NULL,
    ADD COLUMN VnpTxnRef VARCHAR(64) NULL,
    ADD COLUMN VnpBankCode VARCHAR(32) NULL,
    ADD COLUMN VnpPayDate DATETIME NULL;

ALTER TABLE taxi_invoice
    ADD COLUMN PaymentStatus ENUM('unpaid','pending','paid','failed') DEFAULT 'unpaid',
    ADD COLUMN PaymentMethod VARCHAR(32) NULL,
    ADD COLUMN VnpTxnRef VARCHAR(64) NULL,
    ADD COLUMN VnpBankCode VARCHAR(32) NULL,
    ADD COLUMN VnpPayDate DATETIME NULL;
