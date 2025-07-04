<?php

namespace App\Core\Database;

use PDO;
use PDOException;

/**
 * Class QueryBuilder
 *
 * Provides a fluent, secure interface for building and executing SQL queries.
 * Supports SELECT, INSERT, UPDATE, DELETE, WHERE, ORDER BY, LIMIT, and parameter binding.
 */
class QueryBuilder {
    /**
     * @var PDO
     */
    protected $pdo;
    /**
     * @var string
     */
    protected $table;
    /**
     * @var array
     */
    protected $select = ['*'];
    /**
     * @var array
     */
    protected $wheres = [];
    /**
     * @var array
     */
    protected $bindings = [];
    /**
     * @var string|null
     */
    protected $orderBy = null;
    /**
     * @var int|null
     */
    protected $limit = null;

    protected $joins = [];
    protected $updates = [];
    protected $inserts = [];
    protected $deletes = false;
    protected $orWheres = [];
    protected $queryType = 'select';

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function table($table) {
        $this->table = $table;
        return $this;
    }

    public function select($columns = ['*']) {
        $this->select = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where($column, $operator, $value) {
        $param = ':w_' . count($this->bindings);
        $this->wheres[] = "$column $operator $param";
        $this->bindings[$param] = $value;
        return $this;
    }

    public function orderBy($column, $direction = 'ASC') {
        $this->orderBy = "$column $direction";
        return $this;
    }

    public function limit($limit) {
        $this->limit = (int)$limit;
        return $this;
    }

    /**
     * Add a JOIN clause.
     */
    public function join($table, $first, $operator, $second, $type = 'INNER') {
        $this->joins[] = strtoupper($type) . " JOIN $table ON $first $operator $second";
        return $this;
    }

    /**
     * Add an OR WHERE clause.
     */
    public function orWhere($column, $operator, $value) {
        $param = ':w_' . count($this->bindings);
        $this->orWheres[] = "$column $operator $param";
        $this->bindings[$param] = $value;
        return $this;
    }

    /**
     * Insert data into the table.
     */
    public function insert(array $data) {
        $this->queryType = 'insert';
        $this->inserts = $data;
        return $this->executeInsert();
    }

    /**
     * Update data in the table.
     */
    public function update(array $data) {
        $this->queryType = 'update';
        $this->updates = $data;
        return $this->executeUpdate();
    }

    /**
     * Delete data from the table.
     */
    public function delete() {
        $this->queryType = 'delete';
        $this->deletes = true;
        return $this->executeDelete();
    }

    /**
     * Build and execute an INSERT statement.
     */
    protected function executeInsert() {
        $columns = array_keys($this->inserts);
        $params = array_map(function($col) { return ':i_' . $col; }, $columns);
        $sql = "INSERT INTO `{$this->table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $params) . ")";
        $bindings = [];
        foreach ($columns as $col) {
            $bindings[':i_' . $col] = $this->inserts[$col];
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $this->pdo->lastInsertId();
    }

    /**
     * Build and execute an UPDATE statement.
     */
    protected function executeUpdate() {
        $set = [];
        $bindings = [];
        foreach ($this->updates as $col => $val) {
            $param = ':u_' . $col;
            $set[] = "$col = $param";
            $bindings[$param] = $val;
        }
        $sql = "UPDATE `{$this->table}` SET " . implode(', ', $set);
        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
            $bindings = array_merge($bindings, $this->bindings);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    /**
     * Build and execute a DELETE statement.
     */
    protected function executeDelete() {
        $sql = "DELETE FROM `{$this->table}`";
        $bindings = $this->bindings;
        if ($this->wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->rowCount();
    }

    /**
     * Build the SQL for SELECT queries, including JOINs and OR WHEREs.
     */
    public function toSql($count = false) {
        $columns = $count ? 'COUNT(*)' : implode(', ', $this->select);
        $sql = "SELECT $columns FROM `{$this->table}`";
        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        if ($this->wheres || $this->orWheres) {
            $where = [];
            if ($this->wheres) $where[] = implode(' AND ', $this->wheres);
            if ($this->orWheres) $where[] = implode(' OR ', $this->orWheres);
            $sql .= ' WHERE ' . implode(' OR ', $where);
        }
        if ($this->orderBy && !$count) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }
        if ($this->limit && !$count) {
            $sql .= ' LIMIT ' . $this->limit;
        }
        return $sql;
    }

    /**
     * Fetch the first result (or null if none).
     *
     * @return array|null
     */
    public function first() {
        $this->limit(1);
        $sql = $this->toSql();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }

    // TODO: Add more advanced features.
}
