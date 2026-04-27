import { execFileSync, spawn } from "node:child_process";
import { existsSync, mkdirSync, writeFileSync } from "node:fs";
import { dirname, join, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(fileURLToPath(new URL("..", import.meta.url)));
const databasePath = join(root, "database", "e2e.sqlite");
const port = process.env.E2E_PORT ?? "8010";

mkdirSync(dirname(databasePath), { recursive: true });

if (!existsSync(databasePath)) {
    writeFileSync(databasePath, "");
}

const env = {
    ...process.env,
    APP_ENV: "testing",
    APP_KEY:
        process.env.APP_KEY ??
        "base64:3B4fVrc0YdG1mjpS4yA8e++8A6bY6wsWdT58mdQ0D64=",
    APP_DEBUG: "true",
    APP_URL: `http://127.0.0.1:${port}`,
    BCRYPT_ROUNDS: "4",
    CACHE_STORE: "array",
    DB_CONNECTION: "sqlite",
    DB_DATABASE: databasePath,
    LOG_CHANNEL: "stderr",
    MAIL_MAILER: "array",
    QUEUE_CONNECTION: "sync",
    SESSION_DRIVER: "array",
    SWIFT_AUTH_TABLE_PREFIX: "swift_auth_",
};

execFileSync("php", ["artisan", "migrate:fresh", "--seed", "--force"], {
    cwd: root,
    env,
    stdio: "inherit",
});

const server = spawn(
    "php",
    ["artisan", "serve", "--host=127.0.0.1", `--port=${port}`],
    {
        cwd: root,
        env,
        stdio: "inherit",
    },
);

const shutdown = () => {
    server.kill("SIGTERM");
};

process.on("SIGINT", shutdown);
process.on("SIGTERM", shutdown);
server.on("exit", (code) => process.exit(code ?? 0));
