CREATE TABLE "user" (
    id SERIAL PRIMARY KEY,
    firstname VARCHAR(50),
    lastname VARCHAR(50),
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL
    
);
