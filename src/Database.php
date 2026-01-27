<?php

/**
 * SQLite Database Handler for Telegram Bot
 * Provides user storage and bulk messaging support
 *
 * @author AhmCho <ahmad@cholluyev.com>
 * @version 1.0.0
 */

class Database
{
    /**
     * PDO instance
     */
    private ?PDO $pdo = null;

    /**
     * Database file path
     */
    private string $dbPath;

    /**
     * Constructor
     *
     * @param string|null $dbPath Path to SQLite database file (null for default in data directory)
     * @throws Exception if PDO is not available or database cannot be created
     */
    public function __construct(?string $dbPath = null)
    {
        // Check if PDO is available
        if (!extension_loaded('pdo')) {
            throw new Exception('PDO extension is not enabled. Please enable extension=pdo in your php.ini file.');
        }

        // Check if PDO SQLite is available
        if (!extension_loaded('pdo_sqlite')) {
            throw new Exception('PDO SQLite extension is not enabled. Please enable extension=pdo_sqlite in your php.ini file.');
        }

        // Set default database path if not provided
        if ($dbPath === null) {
            $dbPath = __DIR__ . '/../data/bot.db';
        }

        $this->dbPath = $dbPath;

        // Create data directory if it doesn't exist
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }

        // Connect to database
        try {
            $this->pdo = new PDO('sqlite:' . $dbPath);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Create tables if they don't exist
            $this->createTables();
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Create database tables
     *
     * @return void
     */
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
     * Save or update user data
     *
     * @param array $userData User data array
     * @return bool Success status
     */
    public function saveUser(array $userData): bool
    {
        $required = ['telegram_id', 'chat_id'];
        foreach ($required as $field) {
            if (!isset($userData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

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
            return $stmt->execute([
                ':telegram_id' => $userData['telegram_id'],
                ':chat_id' => $userData['chat_id'],
                ':first_name' => $userData['first_name'] ?? null,
                ':last_name' => $userData['last_name'] ?? null,
                ':username' => $userData['username'] ?? null,
                ':language_code' => $userData['language_code'] ?? null,
                ':is_bot' => $userData['is_bot'] ?? false ? 1 : 0,
                ':is_premium' => $userData['is_premium'] ?? false ? 1 : 0,
            ]);
        } catch (PDOException $e) {
            throw new Exception("Failed to save user: " . $e->getMessage());
        }
    }

    /**
     * Extract user data from Telegram update
     * Supports all update types (message, callback_query, inline_query, etc.)
     *
     * @param array $update Telegram update array
     * @return array|null Extracted user data or null if no user found
     */
    public static function extractUserData(array $update): ?array
    {
        $user = null;
        $chatId = null;

        // Try different update types
        if (isset($update['message']['from'])) {
            $user = $update['message']['from'];
            $chatId = $update['message']['chat']['id'] ?? null;
        } elseif (isset($update['callback_query']['from'])) {
            $user = $update['callback_query']['from'];
            $chatId = $update['callback_query']['message']['chat']['id'] ?? null;
        } elseif (isset($update['inline_query']['from'])) {
            $user = $update['inline_query']['from'];
            $chatId = $user['id']; // For inline queries, use user ID
        } elseif (isset($update['chosen_inline_result']['from'])) {
            $user = $update['chosen_inline_result']['from'];
            $chatId = $user['id'];
        } elseif (isset($update['shipping_query']['from'])) {
            $user = $update['shipping_query']['from'];
            $chatId = $user['id'];
        } elseif (isset($update['pre_checkout_query']['from'])) {
            $user = $update['pre_checkout_query']['from'];
            $chatId = $user['id'];
        } elseif (isset($update['poll_answer']['user'])) {
            $user = $update['poll_answer']['user'];
            $chatId = $user['id'];
        } elseif (isset($update['my_chat_member']['from'])) {
            $user = $update['my_chat_member']['from'];
            $chatId = $update['my_chat_member']['chat']['id'] ?? null;
        } elseif (isset($update['chat_member']['from'])) {
            $user = $update['chat_member']['from'];
            $chatId = $update['chat_member']['chat']['id'] ?? null;
        } elseif (isset($update['chat_join_request']['from'])) {
            $user = $update['chat_join_request']['from'];
            $chatId = $update['chat_join_request']['chat']['id'] ?? null;
        }

        if ($user === null) {
            return null;
        }

        // If no chat_id found, try to use user's private chat (user ID = chat ID for private chats)
        if ($chatId === null) {
            $chatId = $user['id'];
        }

        return [
            'telegram_id' => $user['id'],
            'chat_id' => $chatId,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'username' => $user['username'] ?? null,
            'language_code' => $user['language_code'] ?? null,
            'is_bot' => $user['is_bot'] ?? false,
            'is_premium' => $user['is_premium'] ?? false,
        ];
    }

    /**
     * Get all chat IDs for bulk messaging
     *
     * @param array $filters Optional filters (active_since, has_username, is_premium, limit)
     * @return array Array of chat IDs
     */
    public function getAllChatIds(array $filters = []): array
    {
        $sql = "SELECT DISTINCT chat_id FROM users WHERE 1=1";
        $params = [];

        // Filter by last active date
        if (isset($filters['active_since'])) {
            $sql .= " AND last_active >= :active_since";
            $params[':active_since'] = $filters['active_since'];
        }

        // Filter by username presence
        if (isset($filters['has_username']) && $filters['has_username']) {
            $sql .= " AND username IS NOT NULL AND username != ''";
        }

        // Filter by premium status
        if (isset($filters['is_premium'])) {
            $sql .= " AND is_premium = :is_premium";
            $params[':is_premium'] = $filters['is_premium'] ? 1 : 0;
        }

        // Filter out bots
        if (!isset($filters['include_bots']) || !$filters['include_bots']) {
            $sql .= " AND is_bot = 0";
        }

        // Limit results
        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            return array_column($results, 'chat_id');
        } catch (PDOException $e) {
            throw new Exception("Failed to get chat IDs: " . $e->getMessage());
        }
    }

    /**
     * Get user by Telegram ID
     *
     * @param int $telegramId Telegram user ID
     * @return array|null User data or null if not found
     */
    public function getUserByTelegramId(int $telegramId): ?array
    {
        $sql = "SELECT * FROM users WHERE telegram_id = :telegram_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':telegram_id' => $telegramId]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Failed to get user: " . $e->getMessage());
        }
    }

    /**
     * Get user by username
     *
     * @param string $username Username (with or without @)
     * @return array|null User data or null if not found
     */
    public function getUserByUsername(string $username): ?array
    {
        // Remove @ if present
        $username = ltrim($username, '@');

        $sql = "SELECT * FROM users WHERE LOWER(username) = LOWER(:username)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (PDOException $e) {
            throw new Exception("Failed to get user: " . $e->getMessage());
        }
    }

    /**
     * Get all users with pagination and filters
     *
     * @param array $filters Optional filters
     * @param int $limit Maximum number of results
     * @param int $offset Number of results to skip
     * @return array Array of users
     */
    public function getAllUsers(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        // Filter by last active date
        if (isset($filters['active_since'])) {
            $sql .= " AND last_active >= :active_since";
            $params[':active_since'] = $filters['active_since'];
        }

        // Filter by username presence
        if (isset($filters['has_username']) && $filters['has_username']) {
            $sql .= " AND username IS NOT NULL AND username != ''";
        }

        // Filter by premium status
        if (isset($filters['is_premium'])) {
            $sql .= " AND is_premium = :is_premium";
            $params[':is_premium'] = $filters['is_premium'] ? 1 : 0;
        }

        // Filter out bots
        if (!isset($filters['include_bots']) || !$filters['include_bots']) {
            $sql .= " AND is_bot = 0";
        }

        // Order and paginate
        $sql .= " ORDER BY last_active DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Failed to get users: " . $e->getMessage());
        }
    }

    /**
     * Update user's last active timestamp
     *
     * @param int $telegramId Telegram user ID
     * @return bool Success status
     */
    public function updateLastActive(int $telegramId): bool
    {
        $sql = "UPDATE users SET last_active = datetime('now') WHERE telegram_id = :telegram_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':telegram_id' => $telegramId]);
        } catch (PDOException $e) {
            throw new Exception("Failed to update last active: " . $e->getMessage());
        }
    }

    /**
     * Delete user from database
     *
     * @param int $telegramId Telegram user ID
     * @return bool Success status
     */
    public function deleteUser(int $telegramId): bool
    {
        $sql = "DELETE FROM users WHERE telegram_id = :telegram_id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':telegram_id' => $telegramId]);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete user: " . $e->getMessage());
        }
    }

    /**
     * Get database statistics
     *
     * @return array Statistics array
     */
    public function getStats(): array
    {
        $stats = [];

        // Total users
        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->pdo->query($sql);
        $stats['total'] = $stmt->fetch()['total'];

        // Active users in last 30 days
        $sql = "SELECT COUNT(*) as active FROM users WHERE last_active >= datetime('now', '-30 days')";
        $stmt = $this->pdo->query($sql);
        $stats['active_30_days'] = $stmt->fetch()['active'];

        // Premium users
        $sql = "SELECT COUNT(*) as premium FROM users WHERE is_premium = 1";
        $stmt = $this->pdo->query($sql);
        $stats['premium'] = $stmt->fetch()['premium'];

        // Users with username
        $sql = "SELECT COUNT(*) as with_username FROM users WHERE username IS NOT NULL AND username != ''";
        $stmt = $this->pdo->query($sql);
        $stats['with_username'] = $stmt->fetch()['with_username'];

        // Bots
        $sql = "SELECT COUNT(*) as bots FROM users WHERE is_bot = 1";
        $stmt = $this->pdo->query($sql);
        $stats['bots'] = $stmt->fetch()['bots'];

        // New users today
        $sql = "SELECT COUNT(*) as new_today FROM users WHERE date(created_at) = date('now')";
        $stmt = $this->pdo->query($sql);
        $stats['new_today'] = $stmt->fetch()['new_today'];

        return $stats;
    }

    /**
     * Get raw PDO instance for custom queries
     *
     * @return PDO PDO instance
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Get database file path
     *
     * @return string Database file path
     */
    public function getDbPath(): string
    {
        return $this->dbPath;
    }

    /**
     * Close database connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
    }
}
