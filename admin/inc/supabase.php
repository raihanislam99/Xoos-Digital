<?php
/**
 * Supabase Service — Auth (REST API) + Database (PostgreSQL PDO)
 */

require_once __DIR__ . '/../config.php';

class Supabase {
    private static ?Supabase $instance = null;

    private string $url;
    private string $anonKey;
    private string $serviceRoleKey;
    private ?PDO $pdo = null;

    private function __construct() {
        $this->url           = rtrim(SUPABASE_URL, '/');
        $this->anonKey       = SUPABASE_ANON_KEY;
        $this->serviceRoleKey = SUPABASE_SERVICE_ROLE_KEY;
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ── Auth REST API ─────────────────────────────────

    public function authSignIn(string $email, string $password): array {
        return $this->rawApiCall('POST', '/auth/v1/token?grant_type=password', [
            'email'    => $email,
            'password' => $password,
        ], $this->anonKey);
    }

    public function authSignOut(string $accessToken): bool {
        try {
            $this->apiCall('POST', '/auth/v1/logout', [], true, $accessToken);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function authGetUser(string $accessToken): ?array {
        try {
            return $this->apiCall('GET', '/auth/v1/user', [], true, $accessToken);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function authRefreshToken(string $refreshToken): array {
        return $this->rawApiCall('POST', '/auth/v1/token?grant_type=refresh_token', [
            'refresh_token' => $refreshToken,
        ]);
    }

    public function authAdminCreateUser(string $email, string $password, array $metadata = [], bool $confirmEmail = true): array {
        $payload = [
            'email'           => $email,
            'password'        => $password,
            'email_confirm'   => $confirmEmail,
        ];
        if (!empty($metadata)) {
            $payload['user_metadata'] = $metadata;
        }
        return $this->apiCall('POST', '/auth/v1/admin/users', $payload, true);
    }

    public function authAdminDeleteUser(string $userId): bool {
        try {
            $this->apiCall('DELETE', "/auth/v1/admin/users/{$userId}", [], true);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function authAdminListUsers(): array {
        return $this->apiCall('GET', '/auth/v1/admin/users', [], true);
    }

    public function authAdminGetUserByEmail(string $email): ?array {
        $users = $this->apiCall('GET', '/auth/v1/admin/users?filter%5Bemail%5D=eq.' . urlencode($email), [], true);
        return $users['users'][0] ?? null;
    }

    public function authInviteUserByEmail(string $email, string $redirectTo = null): ?array {
        try {
            // Use admin API to send invite (uses service role key)
            $payload = ['email' => $email];
            if ($redirectTo) {
                $payload['redirect_to'] = $redirectTo;
            }
            $result = $this->apiCall('POST', '/auth/v1/admin/invite', $payload, true);
            return $result;
        } catch (\Exception $e) {
            // Fallback 1: try public invite endpoint
            try {
                $result = $this->apiCall('POST', '/auth/v1/invite', ['email' => $email], false, $this->serviceRoleKey);
                return $result;
            } catch (\Exception $e2) {
                // Fallback 2: try magic link endpoint
                try {
                    $result = $this->apiCall('POST', '/auth/v1/magiclink', ['email' => $email], false, $this->serviceRoleKey);
                    return $result;
                } catch (\Exception $e3) {
                    // Email sending failed — just return null, user can use forgot password
                    error_log('Failed to send invite to ' . $email . ': ' . $e3->getMessage());
                    return null;
                }
            }
        }
    }

    // ── Database (PostgreSQL PDO) ──────────────────────

    public function db(): ?PDO {
        if ($this->pdo === null) {
            try {
                $host = SUPABASE_DB_HOST;
                $port = SUPABASE_DB_PORT;
                $name = SUPABASE_DB_NAME;
                $user = SUPABASE_DB_USER;
                $pass = SUPABASE_DB_PASS;

                $this->pdo = new PDO(
                    "pgsql:host={$host};port={$port};dbname={$name};sslmode=require",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                return null;
            }
        }
        return $this->pdo;
    }

    public function lastInsertId(): string {
        return $this->pdo ? $this->pdo->lastInsertId() : '0';
    }

    // ── Database (REST / PostgREST) ────────────────────
    // Used when direct PostgreSQL PDO is unavailable (IPv6-only hosts, etc.)

    private ?SupabaseRestDB $restDb = null;

    public function restDb(): SupabaseRestDB {
        if ($this->restDb === null) {
            $this->restDb = new SupabaseRestDB($this->url, $this->serviceRoleKey);
        }
        return $this->restDb;
    }

    // ── API Call (public for advanced use) ─────────────

    public function apiCall(
        string $method,
        string $path,
        array  $data = [],
        bool   $useAuthHeader = false,
        ?string $customToken = null
    ): array {
        $url = $this->url . $path;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($customToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $customToken;
            $headers[] = 'apikey: ' . $customToken;
        } elseif ($useAuthHeader) {
            $pos = strpos($path, '/admin/');
            if ($pos !== false) {
                $headers[] = 'Authorization: Bearer ' . $this->serviceRoleKey;
                $headers[] = 'apikey: ' . $this->serviceRoleKey;
            } else {
                $headers[] = 'Authorization: Bearer ' . $this->anonKey;
                $headers[] = 'apikey: ' . $this->anonKey;
            }
        } else {
            $headers[] = 'apikey: ' . $this->anonKey;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new RuntimeException('Supabase API error: ' . $curlErr);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $decoded['error_description'] ?? $decoded['error'] ?? $decoded['msg'] ?? "HTTP {$httpCode}";
            if (is_array($msg)) $msg = json_encode($msg);
            throw new RuntimeException('Supabase API error: ' . $msg);
        }

        return $decoded ?? [];
    }

    // ── Raw API call (path includes query string, no URL doubling) ──

    private function rawApiCall(string $method, string $path, array $data = [], ?string $apiKey = null): array {
        $url = $this->url . $path;
        $key = $apiKey ?? $this->anonKey;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'apikey: ' . $key,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new RuntimeException('Supabase API error: ' . $curlErr);
        }
        $decoded = json_decode($response, true);
        if ($httpCode >= 400) {
            $msg = $decoded['error_description'] ?? $decoded['error'] ?? $decoded['msg'] ?? $decoded['code'] ?? "HTTP {$httpCode}";
            if (is_array($msg)) $msg = json_encode($msg);
            throw new RuntimeException('Supabase API error: ' . $msg);
        }
        return $decoded ?? [];
    }
}

// ── PDO-compatible REST Database Wrapper ─────────────────────
// Translates common SQL patterns to Supabase PostgREST API calls.
// Used when direct PDO connection is unavailable (IPv6-only hosts).

class SupabaseRestDB {
    private string $restUrl;
    private string $serviceRoleKey;
    private ?int $lastInsertId = null;
    private ?\CurlHandle $curl = null;

    /** @var array<string, array> Request-scoped query result cache */
    private static array $queryCache = [];

    public function __construct(string $supabaseUrl, string $serviceRoleKey) {
        $this->restUrl = rtrim($supabaseUrl, '/') . '/rest/v1/';
        $this->serviceRoleKey = $serviceRoleKey;
        // Single persistent cURL handle — reuse across calls for HTTP keep-alive
        $this->curl = curl_init();
        curl_setopt_array($this->curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_TCP_KEEPALIVE  => 1,
            CURLOPT_TCP_KEEPIDLE   => 60,
        ]);
    }

    public function __destruct() {
        if ($this->curl !== null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    public function prepare(string $sql): SupabaseRestStmt {
        return new SupabaseRestStmt($this, $sql);
    }

    public function query(string $sql): SupabaseRestStmt {
        $key = md5($sql);
        if (isset(self::$queryCache[$key])) {
            $stmt = new SupabaseRestStmt($this, $sql);
            $stmt->hydrateFromCache(self::$queryCache[$key]);
            return $stmt;
        }

        $stmt = new SupabaseRestStmt($this, $sql);
        $stmt->execute();

        if (stripos(trim($sql), 'SELECT') === 0) {
            self::$queryCache[$key] = $stmt->exportResults();
        }

        return $stmt;
    }

    public function clearQueryCache(): void {
        self::$queryCache = [];
    }

    public function lastInsertId(): string {
        return (string)($this->lastInsertId ?? '0');
    }

    public function setLastInsertId(?int $id): void {
        $this->lastInsertId = $id;
    }

    public function exec(string $sql): int {
        // DDL (CREATE, ALTER, DROP, etc.) cannot be executed via REST.
        // Tables already exist in Supabase — silently succeed.
        return 0;
    }

    public function beginTransaction(): bool { return true; }
    public function commit(): bool { return true; }
    public function rollback(): bool { return true; }

    // ── Low-level REST call (reuses persistent cURL handle) ──

    public function restCall(string $method, string $path, ?array $body = null, array $extraHeaders = []): array {
        $url = $this->restUrl . $path;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'apikey: ' . $this->serviceRoleKey,
            'Authorization: Bearer ' . $this->serviceRoleKey,
        ];
        foreach ($extraHeaders as $h) {
            $headers[] = $h;
        }

        // Reset method-specific options from previous call
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, null);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, null);
        curl_setopt($this->curl, CURLOPT_HTTPGET, true);

        curl_setopt_array($this->curl, [
            CURLOPT_URL        => $url,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST') {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_HTTPGET, false);
            if ($body !== null) {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($this->curl, CURLOPT_HTTPGET, false);
            if ($body !== null) {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($body));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($this->curl, CURLOPT_HTTPGET, false);
        } elseif ($method === 'PUT') {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($this->curl, CURLOPT_HTTPGET, false);
            if ($body !== null) {
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $response = curl_exec($this->curl);
        $httpCode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($this->curl);

        if ($curlErr) {
            throw new RuntimeException('Supabase REST error: ' . $curlErr);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? $decoded['msg'] ?? "HTTP {$httpCode}";
            if (is_array($msg)) $msg = json_encode($msg);
            throw new RuntimeException('Supabase REST error: ' . $msg);
        }

        return $decoded ?? [];
    }
}

class SupabaseRestStmt {
    private SupabaseRestDB $db;
    private string $origSql;
    private ?array $results = null;
    private int $rowIndex = 0;
    private int $rowCount = 0;

    // Parsed SQL components
    private string $operation = '';
    private string $table = '';
    private ?string $columns = null;    // for SELECT: "col1,col2" or null for *
    private array $whereCols = [];      // ['col' => ['op' => 'eq', 'pos' => 0], ...]
    private array $whereVals = [];      // ['col' => resolved_value, ...]
    private ?string $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;
    private array $insertCols = [];     // for INSERT
    private array $insertVals = [];     // for INSERT: col => placeholder
    private array $setCols = [];        // for UPDATE
    private bool $isCount = false;
    private bool $isDistinct = false;

    public function __construct(SupabaseRestDB $db, string $sql) {
        $this->db = $db;
        $this->origSql = $sql;
    }

    public function execute(?array $params = null): bool {
        $sql = $this->origSql;
        if ($params !== null) {
            $sql = $this->substituteParams($sql, $params);
        }
        return $this->parseAndExecute($sql);
    }

    /** Hydrate from cached results (avoids a REST round-trip). */
    public function hydrateFromCache(array $results): void {
        $this->results  = $results;
        $this->rowCount = count($results);
        $this->rowIndex = 0;
    }

    /** Export results for caching. */
    public function exportResults(): array {
        return $this->results ?? [];
    }

    public function fetch(int $mode = PDO::FETCH_ASSOC): mixed {
        if ($this->results === null) return false;
        if ($this->rowIndex >= count($this->results)) return false;
        $row = $this->results[$this->rowIndex];
        $this->rowIndex++;
        if ($mode === PDO::FETCH_COLUMN) {
            $vals = array_values($row);
            return $vals[0] ?? false;
        }
        if ($mode === PDO::FETCH_ASSOC || $mode === PDO::FETCH_DEFAULT) {
            return $row;
        }
        return $row;
    }

    public function fetchAll(int $mode = PDO::FETCH_ASSOC): array {
        if ($this->results === null) return [];
        if ($mode === PDO::FETCH_COLUMN) {
            $cols = [];
            foreach ($this->results as $row) {
                $vals = array_values($row);
                $cols[] = $vals[0] ?? null;
            }
            return $cols;
        }
        if ($mode === PDO::FETCH_ASSOC || $mode === PDO::FETCH_DEFAULT) {
            return $this->results;
        }
        return $this->results;
    }

    public function fetchColumn(int $col = 0): mixed {
        if ($this->results === null || empty($this->results)) return false;
        $vals = array_values($this->results[0]);
        return $vals[$col] ?? false;
    }

    public function rowCount(): int {
        return $this->rowCount;
    }

    public function closeCursor(): void {}
    public function setFetchMode(int $mode): bool { return true; }

    // ── Parameter substitution ──

    private function substituteParams(string $sql, array $params): string {
        if (isset($params[0])) {
            // Positional params: replace ? in order
            $parts = explode('?', $sql);
            $result = $parts[0];
            $i = 0;
            for ($i = 0; $i < count($params); $i++) {
                if (!isset($parts[$i + 1])) break;
                $result .= $this->quoteParam($params[$i]) . $parts[$i + 1];
            }
            return $result;
        }
        // Named params: replace :name (longest keys first to avoid partial matches like :title inside :meta_title)
        $keys = array_keys($params);
        usort($keys, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($keys as $key) {
            $sql = str_replace(':' . $key, $this->quoteParam($params[$key]), $sql);
        }
        return $sql;
    }

    private function quoteParam($val): string {
        if ($val === null) return 'NULL';
        if (is_int($val) || is_float($val)) return (string)$val;
        if (is_bool($val)) return $val ? 'true' : 'false';
        return "'" . str_replace("'", "''", (string)$val) . "'";
    }

    // ── SQL parsing and REST execution ──

    private function parseAndExecute(string $sql): bool {
        // Normalize whitespace only outside of single-quoted string literals to preserve newlines/spaces within them
        $parts = explode("'", $sql);
        for ($i = 0; $i < count($parts); $i += 2) {
            $parts[$i] = preg_replace('/\s+/', ' ', $parts[$i]);
        }
        $sql = trim(implode("'", $parts));
        $upper = strtoupper($sql);

        try {
            if (str_starts_with($upper, 'SELECT')) {
                return $this->parseSelect($sql, $upper);
            }
            if (str_starts_with($upper, 'INSERT')) {
                return $this->parseInsert($sql, $upper);
            }
            if (str_starts_with($upper, 'UPDATE')) {
                return $this->parseUpdate($sql, $upper);
            }
            if (str_starts_with($upper, 'DELETE')) {
                return $this->parseDelete($sql, $upper);
            }
            throw new RuntimeException("Unsupported SQL operation: " . substr($upper, 0, 30));
        } catch (RuntimeException $e) {
            error_log("SupabaseRestDB: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }

    private function parseSelect(string $sql, string $upper): bool {
        $this->operation = 'SELECT';

        // Check for COUNT(*) vs DISTINCT
        if (preg_match('/\bCOUNT\s*\(\s*\*\s*\)/i', $sql)) {
            $this->isCount = true;
        }
        if (preg_match('/\bDISTINCT\s+/i', $sql)) {
            $this->isDistinct = true;
        }

        // Extract columns
        if (preg_match('/^SELECT\s+(.*?)\s+FROM\s+/i', $sql, $m)) {
            $this->columns = trim($m[1]);
        }

        // Extract table
        if (preg_match('/\bFROM\s+([a-z_][a-z0-9_]*)(?:\s+(?:AS\s+)?[a-z][a-z0-9_]*)?/i', $sql, $m)) {
            $this->table = $m[1];
        }
        if (empty($this->table)) {
            throw new RuntimeException('Cannot parse table name');
        }

        // Extract WHERE
        $whereClause = '';
        if (preg_match('/\bWHERE\s+(.+?)(?:\s+ORDER\s+BY\s+|\s+LIMIT\s+|\s+OFFSET\s+|\s+GROUP\s+BY\s+|\s+HAVING\s+|$)/is', $sql, $m)) {
            $whereClause = trim($m[1]);
        }

        // Extract ORDER BY
        if (preg_match('/\bORDER\s+BY\s+(.+?)(?:\s+LIMIT\s+|\s+OFFSET\s+|\s+GROUP\s+BY\s+|\s+HAVING\s+|$)/is', $sql, $m)) {
            $this->orderBy = trim($m[1]);
        }

        // Extract LIMIT
        if (preg_match('/\bLIMIT\s+(\d+)/i', $sql, $m)) {
            $this->limit = (int)$m[1];
        }

        // Extract OFFSET
        if (preg_match('/\bOFFSET\s+(\d+)/i', $sql, $m)) {
            $this->offset = (int)$m[1];
        }

        // Parse WHERE conditions
        if (!empty($whereClause)) {
            $this->parseWhere($whereClause);
        }

        // Now build and execute the REST call
        $this->executeSelectRest();
        return true;
    }

    private function parseWhere(string $where): void {
        // Split by AND (handling parenthesized sub-expressions)
        $conditions = $this->splitByAnd($where);

        foreach ($conditions as $cond) {
            $cond = trim($cond);
            if (empty($cond)) continue;

            // col IN (val1, val2, ...)
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s+IN\s*\((.+?)\)\s*$/i', $cond, $m)) {
                $col = $m[1];
                $vals = explode(',', $m[2]);
                $processed = [];
                foreach ($vals as $v) {
                    $processed[] = $this->stripQuotes(trim($v));
                }
                $this->whereCols[$col] = ['op' => 'in'];
                $this->whereVals[$col] = '(' . implode(',', $processed) . ')';
                continue;
            }

            // col IS NOT NULL
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s+IS\s+NOT\s+NULL\s*$/i', $cond, $m)) {
                $this->whereCols[$m[1]] = ['op' => 'not.is.null'];
                continue;
            }

            // col IS NULL
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s+IS\s+NULL\s*$/i', $cond, $m)) {
                $this->whereCols[$m[1]] = ['op' => 'is.null'];
                continue;
            }

            // col != ''  or col != 'value' or col != value
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s*!=\s*(.+?)\s*$/i', $cond, $m)) {
                $col = $m[1];
                $val = trim($m[2]);
                $this->whereCols[$col] = ['op' => 'neq'];
                $this->whereVals[$col] = $this->stripQuotes($val);
                continue;
            }

            // col >= expr, col > expr, col <= expr, col < expr
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s*(>=|<=|>|<)\s*(.+?)\s*$/i', $cond, $m)) {
                $col = $m[1];
                $op = $m[2] === '>=' ? 'gte' : ($m[2] === '<=' ? 'lte' : ($m[2] === '>' ? 'gt' : 'lt'));
                $val = trim($m[3]);
                $resolved = $this->resolveValue($val);
                $this->whereCols[$col] = ['op' => $op];
                $this->whereVals[$col] = $resolved;
                continue;
            }

            // col::date = expr (PostgreSQL date cast)
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s*::\s*date\s*=\s*(.+?)\s*$/i', $cond, $m)) {
                $col = $m[1];
                $val = trim($m[2]);
                $resolved = $this->resolveValue($val);
                $startKey = $col . '__start';
                $endKey = $col . '__end';
                $this->whereCols[$startKey] = ['op' => 'gte', 'col' => $col];
                $this->whereVals[$startKey] = $resolved;
                $this->whereCols[$endKey] = ['op' => 'lt', 'col' => $col];
                $this->whereVals[$endKey] = $resolved . 'T23:59:59Z';
                continue;
            }

            // col = value (standard equality)
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s*=\s*(.+?)\s*$/i', $cond, $m)) {
                $col = $m[1];
                $val = trim($m[2]);
                $resolved = $this->resolveValue($val);
                $this->whereCols[$col] = ['op' => 'eq'];
                $this->whereVals[$col] = $resolved;
                continue;
            }

            // col::date >= expr (date cast with comparison) - just handle as regular comparison
            if (preg_match('/^\s*([a-z_][a-z0-9_]*)\s*::\s*date\s*(>=|<=|>|<)\s*(.+?)\s*$/i', $cond, $m)) {
                $col = $m[1];
                $op = $m[2] === '>=' ? 'gte' : ($m[2] === '<=' ? 'lte' : ($m[2] === '>' ? 'gt' : 'lt'));
                $val = trim($m[3]);
                $resolved = $this->resolveValue($val);
                $this->whereCols[$col] = ['op' => $op];
                $this->whereVals[$col] = $resolved;
                continue;
            }

            // Fallback: log warning
            error_log("SupabaseRestDB: unparseable WHERE condition: $cond");
        }
    }

    private function resolveValue(string $val): string {
        $val = trim($val);

        // NULL
        if (strtoupper($val) === 'NULL') return 'NULL';

        // Quoted string — un-double escaped single quotes
        if ((str_starts_with($val, "'") && str_ends_with($val, "'")) ||
            (str_starts_with($val, '"') && str_ends_with($val, '"'))) {
            return str_replace("''", "'", $this->stripQuotes($val));
        }

        // Numeric
        if (is_numeric($val)) return $val;

        // CURRENT_DATE, CURRENT_TIMESTAMP, NOW()
        if (strtoupper($val) === 'CURRENT_DATE') return date('Y-m-d');
        if (strtoupper($val) === 'CURRENT_TIMESTAMP' || strtoupper($val) === 'NOW()') {
            return date('Y-m-d\TH:i:s\Z');
        }

        // NOW() - INTERVAL '7 days' (with whitespace tolerance)
        if (preg_match("/^NOW\s*\(\s*\)\s*-\s*INTERVAL\s+'\s*(\d+)\s*(day|days|hour|hours|month|months|year|years)\s*'\s*$/i", $val, $m)) {
            $n = (int)$m[1];
            $unit = strtolower($m[2]);
            if (in_array($unit, ['day', 'days'])) return date('Y-m-d\TH:i:s\Z', strtotime("-{$n} days"));
            if (in_array($unit, ['hour', 'hours'])) return date('Y-m-d\TH:i:s\Z', strtotime("-{$n} hours"));
            if (in_array($unit, ['month', 'months'])) return date('Y-m-d\TH:i:s\Z', strtotime("-{$n} months"));
            if (in_array($unit, ['year', 'years'])) return date('Y-m-d\TH:i:s\Z', strtotime("-{$n} years"));
        }

        // CURRENT_DATE - INTERVAL '7 days' (with whitespace tolerance)
        if (preg_match("/^CURRENT_DATE\s*-\s*INTERVAL\s+'\s*(\d+)\s*(day|days)\s*'\s*$/i", $val, $m)) {
            return date('Y-m-d', strtotime("-{$m[1]} days"));
        }

        // If it's a bare word (column reference or function call), try to evaluate
        // For simple function calls without params, evaluate them
        if (preg_match('/^[A-Z_]+\(\)$/i', $val)) {
            $funcName = strtoupper(str_replace('()', '', $val));
            if ($funcName === 'NOW') return date('Y-m-d\TH:i:s\Z');
        }

        // Return as-is (might be a column reference)
        return $val;
    }

    private function stripQuotes(string $s): string {
        $s = trim($s);
        if ((str_starts_with($s, "'") && str_ends_with($s, "'")) ||
            (str_starts_with($s, '"') && str_ends_with($s, '"'))) {
            return trim(substr($s, 1, -1));
        }
        return $s;
    }

    private function splitByAnd(string $where): array {
        $parts = [];
        $depth = 0;
        $current = '';
        $len = strlen($where);
        $inStr = false;
        for ($i = 0; $i < $len; $i++) {
            $ch = $where[$i];
            if ($inStr) {
                $current .= $ch;
                if ($ch === "'") {
                    if ($i + 1 < $len && $where[$i + 1] === "'") {
                        $current .= "'";
                        $i++;
                    } else {
                        $inStr = false;
                    }
                }
            } elseif ($ch === "'") {
                $current .= $ch;
                $inStr = true;
            } elseif ($ch === '(') {
                $depth++;
                $current .= $ch;
            } elseif ($ch === ')') {
                $depth--;
                $current .= $ch;
            } elseif ($depth === 0 &&
                $i + 2 < $len &&
                strtoupper(substr($where, $i, 3)) === 'AND' &&
                ($i === 0 || preg_match('/\s/', $where[$i - 1]) || $where[$i - 1] === '(') &&
                ($i + 3 >= $len || preg_match('/\s/', $where[$i + 3]) || $where[$i + 3] === ')')) {
                $parts[] = trim($current);
                $current = '';
                $i += 2;
            } else {
                $current .= $ch;
            }
        }
        if (trim($current) !== '') {
            $parts[] = trim($current);
        }
        return $parts;
    }

    private function executeSelectRest(): void {
        $path = $this->table;

        $queryParams = []; // array of [key, value] pairs (supports duplicate keys)

        // Columns
        if ($this->isCount) {
            $queryParams[] = ['select', 'count'];
        } elseif ($this->columns !== null && $this->columns !== '*' && !$this->isDistinct) {
            // Clean column names (remove aliases, type casts)
            $cols = [];
            foreach (explode(',', $this->columns) as $col) {
                $col = trim($col);
                // Handle "col::date as alias" or "col as alias"
                $col = preg_replace('/\s+as\s+.*$/i', '', $col);
                $col = preg_replace('/::.*$/', '', $col);
                $col = trim($col);
                if (!empty($col)) $cols[] = $col;
            }
            if (!empty($cols)) {
                $queryParams[] = ['select', implode(',', $cols)];
            }
        }

        // WHERE conditions
        foreach ($this->whereCols as $col => $info) {
            $op = $info['op'];
            $actualCol = $info['col'] ?? $col;
            if ($op === 'in') {
                $queryParams[] = [$actualCol, 'in.' . $this->whereVals[$col]];
            } elseif ($op === 'not.is.null') {
                $queryParams[] = [$actualCol, 'not.is.null'];
            } elseif ($op === 'is.null') {
                $queryParams[] = [$actualCol, 'is.null'];
            } elseif ($op === 'neq') {
                $queryParams[] = [$actualCol, 'neq.' . $this->whereVals[$col]];
            } elseif (in_array($op, ['eq', 'gte', 'lte', 'gt', 'lt'])) {
                $queryParams[] = [$actualCol, $op . '.' . $this->whereVals[$col]];
            }
        }

        // ORDER BY
        if ($this->orderBy !== null) {
            // Convert PostgreSQL ORDER BY to PostgREST format
            // "col DESC, col2 ASC" → "col.desc,col2.asc"
            $orders = [];
            foreach (explode(',', $this->orderBy) as $o) {
                $o = trim($o);
                $o = preg_replace('/\s+::date\s*/i', ' ', $o);
                if (preg_match('/^([a-z_][a-z0-9_]*)\s+(DESC|ASC)\s*$/i', $o, $m)) {
                    $orders[] = $m[1] . '.' . strtolower($m[2]);
                } elseif (preg_match('/^([a-z_][a-z0-9_]*)\s*$/i', $o, $m)) {
                    $orders[] = $m[1] . '.asc';
                }
            }
            if (!empty($orders)) {
                $queryParams[] = ['order', implode(',', $orders)];
            }
        }

        // LIMIT / OFFSET
        if ($this->limit !== null) $queryParams[] = ['limit', $this->limit];
        if ($this->offset !== null) $queryParams[] = ['offset', $this->offset];

        // Distinct
        if ($this->isDistinct) {
            $queryParams[] = ['select', ($this->columns ? preg_replace('/\s*DISTINCT\s+/i', '', $this->columns) : '*')];
        }

        // Build query string
        $queryString = '';
        foreach ($queryParams as $qp) {
            if ($queryString !== '') $queryString .= '&';
            $queryString .= urlencode((string)$qp[0]) . '=' . urlencode((string)$qp[1]);
        }

        $path = $this->table;
        if ($queryString !== '') {
            $path .= '?' . $queryString;
        }

        $result = $this->db->restCall('GET', $path);

        // PostgREST returns: [{"count": 123}] for count queries
        if ($this->isCount) {
            if (!empty($result) && isset($result[0]['count'])) {
                $this->results = [['count' => (int)$result[0]['count']]];
            } else {
                $this->results = [['count' => count($result)]];
            }
        } else {
            $this->results = $result;
        }

        $this->rowCount = count($this->results);
        $this->rowIndex = 0;
    }

    private function parseInsert(string $sql, string $upper): bool {
        $this->operation = 'INSERT';

        // INSERT INTO table (col1, col2) VALUES (...)
        if (!preg_match('/^\s*INSERT\s+INTO\s+([a-z_][a-z0-9_]*)\s*\(([^)]+)\)/i', $sql, $m)) {
            throw new RuntimeException('Cannot parse INSERT statement');
        }

        $this->table = $m[1];
        $cols = array_map('trim', explode(',', $m[2]));

        // Find the VALUES clause and its matching paren (handles nested parens & quoted strings)
        $valuesPos = stripos($sql, 'VALUES');
        if ($valuesPos === false) {
            throw new RuntimeException('Cannot parse INSERT: VALUES not found');
        }
        $openParen = strpos($sql, '(', $valuesPos);
        if ($openParen === false) {
            throw new RuntimeException('Cannot parse INSERT: missing ( after VALUES');
        }
        $closeParen = $this->findMatchingParen($sql, $openParen);
        if ($closeParen === null) {
            throw new RuntimeException('Cannot parse INSERT: missing ) for VALUES');
        }

        $valuesContent = substr($sql, $openParen + 1, $closeParen - $openParen - 1);
        $placeholders = $this->splitTopLevelCommas($valuesContent);

        $data = [];
        foreach ($cols as $i => $col) {
            $placeholder = $placeholders[$i] ?? '';
            if (str_starts_with($placeholder, ':')) {
                $paramName = substr($placeholder, 1);
                $data[$col] = $placeholder;
                $this->insertCols[] = $col;
                $this->insertVals[$col] = $paramName;
            } else {
                // Literal value in the VALUES
                $data[$col] = $this->resolveBodyValue($placeholder);
                $this->insertCols[] = $col;
            }
        }

        $this->executeInsertRest($data);
        return true;
    }

    /**
     * Split a comma-separated list respecting string literals and parentheses.
     * Handles single-quoted strings with escaped quotes (''), nested parens.
     */
    private function splitTopLevelCommas(string $s): array {
        $parts = [];
        $cur = '';
        $inStr = false;
        $depth = 0;
        $len = strlen($s);
        for ($i = 0; $i < $len; $i++) {
            $ch = $s[$i];
            if ($inStr) {
                $cur .= $ch;
                if ($ch === "'") {
                    if ($i + 1 < $len && $s[$i + 1] === "'") {
                        $cur .= "'";
                        $i++;
                    } else {
                        $inStr = false;
                    }
                }
            } elseif ($ch === "'") {
                $cur .= $ch;
                $inStr = true;
            } elseif ($ch === '(') {
                $cur .= $ch;
                $depth++;
            } elseif ($ch === ')') {
                $cur .= $ch;
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $parts[] = trim($cur);
                $cur = '';
            } else {
                $cur .= $ch;
            }
        }
        $rem = trim($cur);
        if ($rem !== '') $parts[] = $rem;
        return $parts;
    }

    /**
     * Find matching close-paren from a given position, handling strings and nesting.
     */
    private function findMatchingParen(string $sql, int $openPos): ?int {
        $len = strlen($sql);
        $inStr = false;
        $depth = 0;
        $started = false;
        for ($i = $openPos; $i < $len; $i++) {
            $ch = $sql[$i];
            if ($inStr) {
                if ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $i++;
                    } else {
                        $inStr = false;
                    }
                }
            } elseif ($ch === "'") {
                $inStr = true;
            } elseif ($ch === '(') {
                $depth++;
                $started = true;
            } elseif ($ch === ')') {
                $depth--;
                if ($started && $depth === 0) {
                    return $i;
                }
            }
        }
        return null;
    }

    private function resolveBodyValue(string $val): mixed {
        $unquoted = $this->stripQuotes($val);
        // Un-double escaped single quotes (SQL '' -> ')
        $unquoted = str_replace("''", "'", $unquoted);
        if (is_numeric($unquoted)) {
            return $unquoted + 0;
        }
        $upper = strtoupper($unquoted);
        if ($upper === 'NULL') return null;
        if ($upper === 'TRUE') return true;
        if ($upper === 'FALSE') return false;
        if ($upper === 'CURRENT_DATE') return date('Y-m-d');
        if ($upper === 'CURRENT_TIMESTAMP' || $upper === 'NOW()') return date('Y-m-d\TH:i:s\Z');
        return $unquoted;
    }

    private function executeInsertRest(array $data): void {
        $body = [];
        foreach ($data as $col => $val) {
            if (str_starts_with($val, ':')) {
                $body[$col] = substr($val, 1);
            } elseif (is_string($val)) {
                $body[$col] = $this->resolveBodyValue($val);
            } else {
                $body[$col] = $val;
            }
        }

        $result = $this->db->restCall('POST', $this->table . '?select=id', $body, ['Prefer: return=representation']);

        // Extract last insert ID
        if (!empty($result) && isset($result[0]['id'])) {
            $this->db->setLastInsertId((int)$result[0]['id']);
        } elseif (!empty($result)) {
            $vals = array_values($result[0]);
            $this->db->setLastInsertId(is_numeric($vals[0]) ? (int)$vals[0] : null);
        }

        $this->results = $result;
        $this->rowCount = count($result);
    }

    private function findWherePos(string $sql): ?int {
        $inStr = false;
        $len = strlen($sql);
        for ($i = 0; $i < $len - 4; $i++) {
            $ch = $sql[$i];
            if ($inStr) {
                if ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $i++;
                    } else {
                        $inStr = false;
                    }
                }
            } elseif ($ch === "'") {
                $inStr = true;
            } elseif (
                strtoupper(substr($sql, $i, 5)) === 'WHERE' &&
                ($i === 0 || preg_match('/\s/', $sql[$i - 1])) &&
                ($i + 5 >= $len || preg_match('/\s/', $sql[$i + 5]))
            ) {
                return $i;
            }
        }
        return null;
    }

    private function parseUpdate(string $sql, string $upper): bool {
        $this->operation = 'UPDATE';

        if (preg_match('/^\s*UPDATE\s+([a-z_][a-z0-9_]*)\s+SET\s+/i', $sql, $m)) {
            $this->table = $m[1];
            $rest = ltrim(substr($sql, strlen($m[0])));

            $wherePos = $this->findWherePos($rest);
            $setClause = $wherePos !== null ? substr($rest, 0, $wherePos) : $rest;
            $whereClause = $wherePos !== null ? trim(substr($rest, $wherePos + 5)) : '';

            // Parse SET clause — split by top-level commas only (content may contain commas)
            $setParts = $this->splitTopLevelCommas($setClause);
            foreach ($setParts as $part) {
                $part = trim($part);
                // Find the first = sign (values may contain = as well, but col name never does)
                $eqPos = strpos($part, '=');
                if ($eqPos !== false) {
                    $col = trim(substr($part, 0, $eqPos));
                    $val = trim(substr($part, $eqPos + 1));
                    $this->setCols[$col] = $val;
                }
            }

            // Parse WHERE
            if ($whereClause !== '') {
                $this->parseWhere($whereClause);
            }

            $this->executeUpdateRest();
            return true;
        }

        throw new RuntimeException('Cannot parse UPDATE statement');
    }

    private function executeUpdateRest(): void {
        $body = [];
        foreach ($this->setCols as $col => $val) {
            $body[$col] = $this->resolveBodyValue($val);
        }

        // Build filter path
        $filters = [];
        foreach ($this->whereCols as $col => $info) {
            $op = $info['op'];
            $actualCol = $info['col'] ?? $col;
            if (in_array($op, ['eq', 'neq', 'gte', 'lte', 'gt', 'lt'])) {
                $filters[] = urlencode($actualCol) . '=' . urlencode($op . '.' . $this->whereVals[$col]);
            } elseif ($op === 'in') {
                $filters[] = urlencode($actualCol) . '=' . urlencode('in.' . $this->whereVals[$col]);
            } elseif ($op === 'not.is.null') {
                $filters[] = urlencode($actualCol) . '=' . urlencode('not.is.null');
            } elseif ($op === 'is.null') {
                $filters[] = urlencode($actualCol) . '=' . urlencode('is.null');
            }
        }

        $path = $this->table;
        if (!empty($filters)) {
            $path .= '?' . implode('&', $filters);
        }

        $result = $this->db->restCall('PATCH', $path, $body);
        $this->results = $result;
        $this->rowCount = count($result);
    }

    private function parseDelete(string $sql, string $upper): bool {
        $this->operation = 'DELETE';

        if (preg_match('/^\s*DELETE\s+FROM\s+([a-z_][a-z0-9_]*)/i', $sql, $m)) {
            $this->table = $m[1];
            $rest = ltrim(substr($sql, strlen($m[0])));

            $wherePos = $this->findWherePos($rest);
            $whereClause = $wherePos !== null ? trim(substr($rest, $wherePos + 5)) : '';

            if ($whereClause !== '') {
                $this->parseWhere($whereClause);
            }

            $this->executeDeleteRest();
            return true;
        }

        throw new RuntimeException('Cannot parse DELETE statement');
    }

    private function executeDeleteRest(): void {
        $filters = [];
        foreach ($this->whereCols as $col => $info) {
            $op = $info['op'];
            $actualCol = $info['col'] ?? $col;
            if (in_array($op, ['eq', 'neq', 'gte', 'lte', 'gt', 'lt'])) {
                $filters[] = urlencode($actualCol) . '=' . urlencode($op . '.' . $this->whereVals[$col]);
            } elseif ($op === 'in') {
                $filters[] = urlencode($actualCol) . '=' . urlencode('in.' . $this->whereVals[$col]);
            } elseif ($op === 'not.is.null') {
                $filters[] = urlencode($actualCol) . '=' . urlencode('not.is.null');
            } elseif ($op === 'is.null') {
                $filters[] = urlencode($actualCol) . '=' . urlencode('is.null');
            }
        }

        $path = $this->table;
        if (!empty($filters)) {
            $path .= '?' . implode('&', $filters);
        }

        $result = $this->db->restCall('DELETE', $path);
        $this->results = $result;
        $this->rowCount = count($result);
    }
}
