-- Create TFA codes table
CREATE TABLE IF NOT EXISTS tfa_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code VARCHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_code (email, code),
    INDEX idx_expires (expires_at)
);

-- Clean up expired codes (optional - you can run this periodically)
DELETE FROM tfa_codes WHERE expires_at < NOW(); 