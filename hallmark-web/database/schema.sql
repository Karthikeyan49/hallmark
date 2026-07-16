-- Schema for the Hallmark image-automation prototype (SQLite).

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS compliance_items (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    uid            TEXT NOT NULL,
    item_type      TEXT NOT NULL DEFAULT '',
    company        TEXT NOT NULL DEFAULT '',
    weight_grams   REAL NOT NULL,
    track          TEXT NOT NULL,             -- 'A' or 'B'
    provider       TEXT NOT NULL,             -- model-pane provider used
    cost_inr       REAL NOT NULL DEFAULT 0,
    billable       INTEGER NOT NULL DEFAULT 0,
    composite_file TEXT NOT NULL,             -- filename under storage/output
    created_at     TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE INDEX IF NOT EXISTS idx_items_created ON compliance_items(created_at DESC);
