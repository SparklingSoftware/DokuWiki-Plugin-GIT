CREATE TABLE git (repo TEXT PRIMARY KEY, timestamp INTEGER, status TEXT);
CREATE UNIQUE INDEX idx_git ON git(repo);




