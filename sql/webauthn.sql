-- Table to store WebAuthn Public Key Credentials
CREATE TABLE IF NOT EXISTS student_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    credential_id VARBINARY(255) NOT NULL,
    public_key TEXT NOT NULL,
    sign_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE(credential_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
