-- Widen user.Password column to support password_hash (up to 255 chars)
ALTER TABLE `user`
  MODIFY `Password` VARCHAR(255) NOT NULL;
