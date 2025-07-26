CREATE TABLE IF NOT EXISTS user (
    id INTEGER PRIMARY KEY,
    username TEXT NOT NULL,
    display_name TEXT NOT NULL,
    password_hash TEXT NULL,
    about TEXT NULL,
    website TEXT NULL,
    mood TEXT NULL
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY,
    site_title TEXT NOT NULL,
    site_description TEXT NULL,
    base_url TEXT NOT NULL,
    base_path TEXT NOT NULL,
    items_per_page INTEGER NOT NULL,
    css_id INTEGER NULL,
    strict_accessibility BOOLEAN DEFAULT TRUE
);

CREATE TABLE IF NOT EXISTS css (
    id INTEGER PRIMARY KEY,
    filename TEXT UNIQUE NOT NULL,
    description TEXT NULL
);

CREATE TABLE IF NOT EXISTS emoji(
    id INTEGER PRIMARY KEY,
    emoji TEXT UNIQUE NOT NULL,
    description TEXT NOT NULL
);