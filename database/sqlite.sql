-- Initialiser avec : sqlite3 var/database.sqlite < database/sqlite.sql
CREATE TABLE IF NOT EXISTS "user" (
    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
    firstname TEXT NOT NULL,
    lastname TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    hash_password TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT,
    img_profil TEXT,
    "grant" TEXT
);
