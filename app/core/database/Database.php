<?php
declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\App;
use App\Core\Services\CoreException;

/**
 * Class Database
 *
 * Handles PDO connection, config loading, and basic DB utilities.
 * Supports multiple drivers (MySQL, SQLite, etc.) via config.php.
 *
 * @package App\Core\Database
 */
class Database {
    /**
     * PDO instance or null if not connected
     * @var PDO|null
     */
    protected ?PDO $pdo = null;
    /**
     * Last error info (code, message)
     * @var array
     */
    protected array $lastError = [];

    /**
     * Database constructor. Loads config and connects.
     *
     * @param array|null $databaseConfig The database config array (host, dbname, etc.)
     * @param array|null $credentialsConfig The credentials config array (user, pass)
     * @throws CoreException|PDOException
     */
    public function __construct(?array $databaseConfig = null, ?array $credentialsConfig = null) {
        if ($databaseConfig === null || $credentialsConfig === null) {
            $envConfig = App::loadConfig();
            $databaseConfig = $envConfig['database'] ?? [];
            $credentialsConfig = $envConfig['credentials'] ?? [];
        }

        $this->connect($databaseConfig, $credentialsConfig);
    }

    /**
     * Establish a PDO connection using config.
     *
     * @param array $databaseConfig
     * @param array $credentialsConfig
     * @throws CoreException|PDOException
     */
    protected function connect(array $databaseConfig, array $credentialsConfig): void {
        $driver = $databaseConfig['driver'] ?? 'mysql';
        $charset = $databaseConfig['charset'] ?? 'utf8mb4';

        try {
            if ($driver === 'mysql') {
                $dsn = "mysql:host={$databaseConfig['host']};dbname={$databaseConfig['dbname']};charset=$charset";
                $user = $credentialsConfig['user'] ?? '';
                $pass = $credentialsConfig['pass'] ?? '';
                $this->pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } elseif ($driver === 'sqlite') {
                $dsn = "sqlite:" . ($databaseConfig['path'] ?? ':memory:');
                $this->pdo = new PDO($dsn, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
            } else {
                throw new CoreException("Unsupported DB driver: $driver");
            }
        } catch (PDOException $e) {
            $this->lastError = ['code' => $e->getCode(), 'message' => $e->getMessage()];
            throw new CoreException($e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO|null
     */
    public function pdo(): ?PDO {
        return $this->pdo;
    }

    /**
     * Get last error info (code, message).
     *
     * @return array
     */
    public function lastError(): array {
        return $this->lastError;
    }

    /**
     * Check if a table exists in the current database.
     *
     * @param string $table
     * @return bool
     */
    public function tableExists(string $table): bool {
        try {
            $result = $this->pdo?->query("SELECT 1 FROM `$table` LIMIT 1");
            return $result !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check if a record exists in a table by column/value.
     *
     * @param string $table
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public function recordExists(string $table, string $column, mixed $value): bool {
        $sql = "SELECT 1 FROM `$table` WHERE `$column` = :value LIMIT 1";
        $stmt = $this->pdo?->prepare($sql);
        if (!$stmt) return false;
        $stmt->execute(['value' => $value]);
        return $stmt->fetch() !== false;
    }

    /**
     * Get a new QueryBuilder instance for fluent query building.
     *
     * Usage:
     *   $db->query()->table('users')->where('id', '=', 1)->first();
     *
     * @return QueryBuilder
     */
    public function query(): QueryBuilder {
        return new QueryBuilder($this->pdo);
    }

    /**
     * Find a single row by criteria.
     *
     * @param string $table
     * @param array $criteria
     * @return array|null
     */
    public function findOne(string $table, array $criteria): ?array {
        $qb = $this->query()->table($table);
        foreach ($criteria as $col => $val) {
            $qb->where($col, '=', $val);
        }
        return $qb->first();
    }

    /**
     * Find all rows by criteria, with optional order and limit.
     *
     * @param string $table
     * @param array $criteria
     * @param string|null $orderBy
     * @param int|null $limit
     * @return array
     */
    public function findAll(string $table, array $criteria = [], ?string $orderBy = null, ?int $limit = null): array {
        $qb = $this->query()->table($table);
        foreach ($criteria as $col => $val) {
            $qb->where($col, '=', $val);
        }
        if ($orderBy) $qb->orderBy($orderBy);
        if ($limit) $qb->limit($limit);
        $results = [];
        $stmt = $qb->pdo->prepare($qb->toSql());
        $stmt->execute($qb->bindings);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    /**
     * Count rows by criteria.
     *
     * @param string $table
     * @param array $criteria
     * @return int
     */
    public function count(string $table, array $criteria = []): int {
        $qb = $this->query()->table($table);
        foreach ($criteria as $col => $val) {
            $qb->where($col, '=', $val);
        }
        $sql = $qb->toSql(true);
        $stmt = $qb->pdo->prepare($sql);
        $stmt->execute($qb->bindings);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Insert a single row and return the new ID.
     *
     * @param string $table
     * @param array $data
     * @return int
     */
    public function insertOne(string $table, array $data): int {
        return $this->query()->table($table)->insert($data);
    }

    /**
     * Update a single row matching criteria.
     *
     * @param string $table
     * @param array $criteria
     * @param array $data
     * @return int
     */
    public function updateOne(string $table, array $criteria, array $data): int {
        $qb = $this->query()->table($table);
        foreach ($criteria as $col => $val) {
            $qb->where($col, '=', $val);
        }
        return $qb->update($data);
    }

    /**
     * Delete a single row matching criteria.
     *
     * @param string $table
     * @param array $criteria
     * @return int
     */
    public function deleteOne(string $table, array $criteria): int {
        $qb = $this->query()->table($table);
        foreach ($criteria as $col => $val) {
            $qb->where($col, '=', $val);
        }
        return $qb->delete();
    }
}