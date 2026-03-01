<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Database;

use DateTimeImmutable;
use PDO;
use PDOException;
use AhmCho\Telegram\Exception\TelegramException;
use AhmCho\Telegram\Logging\LoggerInterface;

/**
 * SQLite User Repository Implementation
 *
 * Stores and retrieves user data using SQLite database
 */
class SqliteUserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct(
        private readonly string $dbPath,
        private readonly ?LoggerInterface $logger = null
    ) {
        $this->ensureExtensionsLoaded();
        $this->ensureDirectoryExists();
        $this->connect();
        $this->createTables();
    }

    public function save(UserEntity $user): bool
    {
        $sql = "INSERT INTO users (
            telegram_id, chat_id, first_name, last_name, username,
            language_code, is_bot, is_premium, last_active
        ) VALUES (
            :telegram_id, :chat_id, :first_name, :last_name, :username,
            :language_code, :is_bot, :is_premium, datetime('now')
        )
        ON CONFLICT(telegram_id) DO UPDATE SET
            chat_id = excluded.chat_id,
            first_name = excluded.first_name,
            last_name = excluded.last_name,
            username = excluded.username,
            language_code = excluded.language_code,
            is_bot = excluded.is_bot,
            is_premium = excluded.is_premium,
            updated_at = datetime('now'),
            last_active = excluded.last_active";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':telegram_id' => $user->telegramId,
                ':chat_id' => $user->chatId,
                ':first_name' => $user->firstName,
                ':last_name' => $user->lastName,
                ':username' => $user->username,
                ':language_code' => $user->languageCode,
                ':is_bot' => $user->isBot ? 1 : 0,
                ':is_premium' => $user->isPremium ? 1 : 0,
            ]);
            return $result;
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to save user: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception, ['telegram_id' => $user->telegramId]);
            throw $exception;
        }
    }

    public function findByTelegramId(int $telegramId): ?UserEntity
    {
        $sql = "SELECT * FROM users WHERE telegram_id = :telegram_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':telegram_id' => $telegramId]);
            $result = $stmt->fetch();

            return $result ? UserEntity::fromDatabaseRow($result) : null;
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to get user: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception, ['telegram_id' => $telegramId]);
            throw $exception;
        }
    }

    public function findByUsername(string $username): ?UserEntity
    {
        $username = ltrim($username, '@');
        $sql = "SELECT * FROM users WHERE LOWER(username) = LOWER(:username)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $result = $stmt->fetch();

            return $result ? UserEntity::fromDatabaseRow($result) : null;
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to get user: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception, ['username' => $username]);
            throw $exception;
        }
    }

    public function getAllChatIds(?UserFilters $filters = null): array
    {
        $filters ??= UserFilters::create();

        $sql = "SELECT DISTINCT chat_id FROM users WHERE 1=1";
        $params = [];

        if ($filters->activeSince !== null) {
            $sql .= " AND last_active >= :active_since";
            $params[':active_since'] = $filters->activeSince;
        }

        if ($filters->hasUsername === true) {
            $sql .= " AND username IS NOT NULL AND username != ''";
        }

        if ($filters->isPremium !== null) {
            $sql .= " AND is_premium = :is_premium";
            $params[':is_premium'] = $filters->isPremium ? 1 : 0;
        }

        if ($filters->includeBots !== true) {
            $sql .= " AND is_bot = 0";
        }

        if ($filters->limit !== null) {
            $sql .= " LIMIT " . (int) $filters->limit;
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            return array_column($results, 'chat_id');
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to get chat IDs: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }
    }

    public function findAll(?UserFilters $filters = null, int $limit = 100, int $offset = 0): array
    {
        $filters ??= UserFilters::create();

        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if ($filters->activeSince !== null) {
            $sql .= " AND last_active >= :active_since";
            $params[':active_since'] = $filters->activeSince;
        }

        if ($filters->hasUsername === true) {
            $sql .= " AND username IS NOT NULL AND username != ''";
        }

        if ($filters->isPremium !== null) {
            $sql .= " AND is_premium = :is_premium";
            $params[':is_premium'] = $filters->isPremium ? 1 : 0;
        }

        if ($filters->includeBots !== true) {
            $sql .= " AND is_bot = 0";
        }

        $sql .= " ORDER BY last_active DESC LIMIT " . (int) $limit . " OFFSET " . (int) $offset;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();

            return array_map(
                fn($row) => UserEntity::fromDatabaseRow($row),
                $results
            );
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to get users: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception);
            throw $exception;
        }
    }

    public function updateLastActive(int $telegramId): bool
    {
        $sql = "UPDATE users SET last_active = datetime('now') WHERE telegram_id = :telegram_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':telegram_id' => $telegramId]);
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to update last active: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception, ['telegram_id' => $telegramId]);
            throw $exception;
        }
    }

    public function delete(int $telegramId): bool
    {
        $sql = "DELETE FROM users WHERE telegram_id = :telegram_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':telegram_id' => $telegramId]);
        } catch (PDOException $e) {
            $exception = new TelegramException("Failed to delete user: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception, ['telegram_id' => $telegramId]);
            throw $exception;
        }
    }

    public function getStats(): array
    {
        $queries = [
            'total' => "SELECT COUNT(*) as count FROM users",
            'active_30_days' => "SELECT COUNT(*) as count FROM users WHERE last_active >= datetime('now', '-30 days')",
            'premium' => "SELECT COUNT(*) as count FROM users WHERE is_premium = 1",
            'with_username' => "SELECT COUNT(*) as count FROM users WHERE username IS NOT NULL AND username != ''",
            'bots' => "SELECT COUNT(*) as count FROM users WHERE is_bot = 1",
            'new_today' => "SELECT COUNT(*) as count FROM users WHERE date(created_at) = date('now')"
        ];

        $stats = [];
        foreach ($queries as $key => $sql) {
            $stmt = $this->pdo->query($sql);
            $stats[$key] = (int) $stmt->fetch()['count'];
        }

        return $stats;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    private function ensureExtensionsLoaded(): void
    {
        if (!extension_loaded('pdo')) {
            throw new TelegramException('PDO extension is not enabled. Please enable extension=pdo in your php.ini file.');
        }

        if (!extension_loaded('pdo_sqlite')) {
            throw new TelegramException('PDO SQLite extension is not enabled. Please enable extension=pdo_sqlite in your php.ini file.');
        }
    }

    private function ensureDirectoryExists(): void
    {
        $dir = dirname($this->dbPath);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new TelegramException("Failed to create directory: $dir");
            }
        }
    }

    private function connect(): void
    {
        try {
            $this->pdo = new PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $exception = new TelegramException("Database connection failed: " . $e->getMessage(), 0, $e);
            $this->logExceptionIfEnabled($exception, ['db_path' => $this->dbPath]);
            throw $exception;
        }
    }

    private function createTables(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            telegram_id INTEGER UNIQUE NOT NULL,
            chat_id INTEGER NOT NULL,
            first_name TEXT,
            last_name TEXT,
            username TEXT,
            language_code TEXT,
            is_bot BOOLEAN DEFAULT 0,
            is_premium BOOLEAN DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_active DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_users_telegram_id ON users(telegram_id);
        CREATE INDEX IF NOT EXISTS idx_users_chat_id ON users(chat_id);
        CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
        CREATE INDEX IF NOT EXISTS idx_users_last_active ON users(last_active);
        CREATE INDEX IF NOT EXISTS idx_users_is_premium ON users(is_premium);";

        $this->pdo->exec($sql);
    }

    /**
     * Log exception if logger is configured
     * Never throws exceptions from logging operations
     */
    private function logExceptionIfEnabled(\Throwable $exception, array $context = []): void
    {
        if ($this->logger !== null) {
            try {
                $this->logger->logException($exception, $context);
            } catch (\Throwable $e) {
                // Fail silently - never throw from logger
            }
        }
    }
}
