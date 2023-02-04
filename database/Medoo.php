<?php

declare(strict_types=1);
/**
 * Medoo Database Framework modified by Phyrus.
 *
 * Phyrus customization of the Medoo framework.
 *
 * @version 2.1.7
 * @author Angel Lai
 * @package Medoo
 * @copyright Copyright 2022 Medoo Project, Angel Lai.
 * @license https://opensource.org/licenses/MIT
 * @link https://medoo.in
 */

namespace Medoo;

use PDO;
use Exception;
use PDOException;
use PDOStatement;
use InvalidArgumentException;

/**
 * The Medoo raw object.
 */
class Raw
{
    /**
     * The array of mapping data for the raw string.
     *
     * @var array
     */
    public $map;

    /**
     * The raw string.
     *
     * @var string
     */
    public $value;
}

/**
 * @method array select(string $table, array $columns, array $where)
 * @method null select(string $table, array $columns, callable $callback)
 * @method null select(string $table, array $columns, array $where, callable $callback)
 * @method null select(string $table, array $join, array $columns, array $where, callable $callback)
 * @method mixed get(string $table, array|string $columns, array $where)
 * @method bool has(string $table, array $where)
 * @method mixed rand(string $table, array|string $column, array $where)
 * @method int count(string $table, array $where)
 * @method int max(string $table, string $column)
 * @method int min(string $table, string $column)
 * @method int avg(string $table, string $column)
 * @method int sum(string $table, string $column)
 * @method int max(string $table, string $column, array $where)
 * @method int min(string $table, string $column, array $where)
 * @method int avg(string $table, string $column, array $where)
 * @method int sum(string $table, string $column, array $where)
 */
class Medoo
{
    /**
     * The PDO object.
     *
     * @var \PDO
     */
    public $pdo;

    /**
     * The type of database.
     *
     * @var string
     */
    public $type;

    /**
     * Table prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The PDO statement object.
     *
     * @var \PDOStatement
     */
    protected $statement;

    /**
     * The DSN connection string.
     *
     * @var string
     */
    protected $dsn;

    /**
     * The array of logs.
     *
     * @var array
     */
    protected $logs = [];

    /**
     * Determine should log the query or not.
     *
     * @var bool
     */
    protected $logging = false;

    /**
     * Determine is in test mode.
     *
     * @var bool
     */
    protected $testMode = false;

    /**
     * The last query string was generated in test mode.
     *
     * @var string
     */
    public $queryString;

    /**
     * Determine is in debug mode.
     *
     * @var bool
     */
    protected $debugMode = false;

    /**
     * Determine should save debug logging.
     *
     * @var bool
     */
    protected $debugLogging = false;

    /**
     * The array of logs for debugging.
     *
     * @var array
     */
    protected $debugLogs = [];

    /**
     * The unique global id.
     *
     * @var integer
     */
    protected $guid = 0;

    /**
     * The returned id for the insert.
     *
     * @var string
     */
    public $returnId = '';

    /**
     * Error Message.
     *
     * @var string|null
     */
    public $error = null;

    /**
     * The array of error information.
     *
     * @var array|null
     */
    public $errorInfo = null;

    /**
     * Connect the database.
     *
     * ```
     * $database = new Medoo([
     *      // required
     *      'type' => 'mysql',
     *      'database' => 'name',
     *      'host' => 'localhost',
     *      'username' => 'your_username',
     *      'password' => 'your_password',
     *
     *      // [optional]
     *      'charset' => 'utf8mb4',
     *      'port' => 3306,
     *      'prefix' => 'PREFIX_'
     * ]);
     * ```
     *
     * @param array $options Connection options
     * @return Medoo
     * @throws PDOException
     * @link https://medoo.in/api/new
     * @codeCoverageIgnore
     */

    public function __construct(array $options)
    {
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }

        if (isset($options['testMode']) && $options['testMode'] == true) {
            $this->testMode = true;
            return;
        }

        $options['type'] = $options['type'] ?? $options['database_type'];

        if (!isset($options['pdo'])) {
            $options['database'] = $options['database'] ?? $options['database_name'];

            if (!isset($options['socket'])) {
                $options['host'] = $options['host'] ?? $options['server'] ?? false;
            }
        }

        if (isset($options['type'])) {
            $this->type = strtolower($options['type']);

            if ($this->type === 'mariadb') {
                $this->type = 'mysql';
            }
        }

        if (isset($options['logging']) && is_bool($options['logging'])) {
            $this->logging = $options['logging'];
        }

        $option = $options['option'] ?? [];
        $commands = [];

        switch ($this->type) {

            case 'mysql':
                // Make MySQL using standard quoted identifier.
                $commands[] = 'SET SQL_MODE=ANSI_QUOTES';

                break;

            case 'mssql':
                // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting.
                $commands[] = 'SET QUOTED_IDENTIFIER ON';

                // Make ANSI_NULLS is ON for NULL value.
                $commands[] = 'SET ANSI_NULLS ON';

                break;
        }

        if (isset($options['pdo'])) {
            if (!$options['pdo'] instanceof PDO) {
                throw new InvalidArgumentException('Invalid PDO object supplied.');
            }

            $this->pdo = $options['pdo'];

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }

            return;
        }

        if (isset($options['dsn'])) {
            if (is_array($options['dsn']) && isset($options['dsn']['driver'])) {
                $attr = $options['dsn'];
            } else {
                throw new InvalidArgumentException('Invalid DSN option supplied.');
            }
        } else {
            if (
                isset($options['port']) &&
                is_int($options['port'] * 1)
            ) {
                $port = $options['port'];
            }

            $isPort = isset($port);

            switch ($this->type) {

                case 'mysql':
                    $attr = [
                        'driver' => 'mysql',
                        'dbname' => $options['database']
                    ];

                    if (isset($options['socket'])) {
                        $attr['unix_socket'] = $options['socket'];
                    } else {
                        $attr['host'] = $options['host'];

                        if ($isPort) {
                            $attr['port'] = $port;
                        }
                    }

                    break;

                case 'pgsql':
                    $attr = [
                        'driver' => 'pgsql',
                        'host' => $options['host'],
                        'dbname' => $options['database']
                    ];

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'sybase':
                    $attr = [
                        'driver' => 'dblib',
                        'host' => $options['host'],
                        'dbname' => $options['database']
                    ];

                    if ($isPort) {
                        $attr['port'] = $port;
                    }

                    break;

                case 'oracle':
                    $attr = [
                        'driver' => 'oci',
                        'dbname' => $options['host'] ?
                            '//' . $options['host'] . ($isPort ? ':' . $port : ':1521') . '/' . $options['database'] :
                            $options['database']
                    ];

                    if (isset($options['charset'])) {
                        $attr['charset'] = $options['charset'];
                    }

                    break;

                case 'mssql':
                    if (isset($options['driver']) && $options['driver'] === 'dblib') {
                        $attr = [
                            'driver' => 'dblib',
                            'host' => $options['host'] . ($isPort ? ':' . $port : ''),
                            'dbname' => $options['database']
                        ];

                        if (isset($options['appname'])) {
                            $attr['appname'] = $options['appname'];
                        }

                        if (isset($options['charset'])) {
                            $attr['charset'] = $options['charset'];
                        }
                    } else {
                        $attr = [
                            'driver' => 'sqlsrv',
                            'Server' => $options['host'] . ($isPort ? ',' . $port : ''),
                            'Database' => $options['database']
                        ];

                        if (isset($options['appname'])) {
                            $attr['APP'] = $options['appname'];
                        }

                        $config = [
                            'ApplicationIntent',
                            'AttachDBFileName',
                            'Authentication',
                            'ColumnEncryption',
                            'ConnectionPooling',
                            'Encrypt',
                            'Failover_Partner',
                            'KeyStoreAuthentication',
                            'KeyStorePrincipalId',
                            'KeyStoreSecret',
                            'LoginTimeout',
                            'MultipleActiveResultSets',
                            'MultiSubnetFailover',
                            'Scrollable',
                            'TraceFile',
                            'TraceOn',
                            'TransactionIsolation',
                            'TransparentNetworkIPResolution',
                            'TrustServerCertificate',
                            'WSID',
                        ];

                        foreach ($config as $value) {
                            $keyname = strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $value));

                            if (isset($options[$keyname])) {
                                $attr[$value] = $options[$keyname];
                            }
                        }
                    }

                    break;

                case 'sqlite':
                    $attr = [
                        'driver' => 'sqlite',
                        $options['database']
                    ];

                    break;
            }
        }

        if (!isset($attr)) {
            throw new InvalidArgumentException('Incorrect connection options.');
        }

        $driver = $attr['driver'];

        if (!in_array($driver, PDO::getAvailableDrivers())) {
            throw new InvalidArgumentException("Unsupported PDO driver: {$driver}.");
        }

        unset($attr['driver']);

        $stack = [];

        foreach ($attr as $key => $value) {
            $stack[] = is_int($key) ? $value : $key . '=' . $value;
        }

        $dsn = $driver . ':' . implode(';', $stack);

        if (
            in_array($this->type, ['mysql', 'pgsql', 'sybase', 'mssql']) &&
            isset($options['charset'])
        ) {
            $commands[] = "SET NAMES '{$options['charset']}'" . (
                $this->type === 'mysql' && isset($options['collation']) ?
                " COLLATE '{$options['collation']}'" : ''
            );
        }

        $this->dsn = $dsn;

        try {
            $this->pdo = new PDO(
                $dsn,
                $options['username'] ?? null,
                $options['password'] ?? null,
                $option
            );

            if (isset($options['error'])) {
                $this->pdo->setAttribute(
                    PDO::ATTR_ERRMODE,
                    in_array($options['error'], [
                        PDO::ERRMODE_SILENT,
                        PDO::ERRMODE_WARNING,
                        PDO::ERRMODE_EXCEPTION
                    ]) ?
                    $options['error'] :
                    PDO::ERRMODE_SILENT
                );
            }

            if (isset($options['command']) && is_array($options['command'])) {
                $commands = array_merge($commands, $options['command']);
            }

            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    /**
     * Generate a new map key for the placeholder.
     *
     * @return string
     */
    protected function mapKey(): string
    {
        return ':MeD' . $this->guid++ . '_mK';
    }

    /**
     * Execute customized raw statement.
     *
     * @param string $statement The raw SQL statement.
     * @return \PDOStatement|null
     */
    public function query(string $statement): ?PDOStatement
    {
        return $this->exec($statement);
    }

    /**
     * Execute the raw statement.
     *
     * @param string $statement The SQL statement.
     * @codeCoverageIgnore
     * @return \PDOStatement|null
     */
    public function exec(string $statement, callable $callback = null): ?PDOStatement
    {
        $this->statement = null;
        $this->errorInfo = null;
        $this->error = null;

        if ($this->testMode) {
            $this->queryString = $statement;
            return null;
        }

        if ($this->debugMode) {
            if ($this->debugLogging) {
                $this->debugLogs[] = $statement;
                return null;
            }

            echo $statement;

            $this->debugMode = false;

            return null;
        }

        if ($this->logging) {
            $this->logs[] = [$statement];
        } else {
            $this->logs = [[$statement]];
        }

        $statement = $this->pdo->prepare($statement);
        $errorInfo = $this->pdo->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = $errorInfo[2];

            return null;
        }

        if (is_callable($callback)) {
            $this->pdo->beginTransaction();
            $callback($statement);
            $execute = $statement->execute();
            $this->pdo->commit();
        } else {
            $execute = $statement->execute();
        }

        $errorInfo = $statement->errorInfo();

        if ($errorInfo[0] !== '00000') {
            $this->errorInfo = $errorInfo;
            $this->error = $errorInfo[2];

            return null;
        }

        if ($execute) {
            $this->statement = $statement;
        }

        return $statement;
    }

    /**
     * Finds whether the object is raw.
     *
     * @param object $object
     * @return bool
     */
    protected function isRaw($object): bool
    {
        return $object instanceof Raw;
    }

    /**
     * Mapping the data from the table.
     *
     * @param array $data
     * @param array $columns
     * @param array $columnMap
     * @param array $stack
     * @param bool $root
     * @param array $result
     * @codeCoverageIgnore
     * @return void
     */
    protected function dataMap(
        array $data,
        array $columns,
        array $columnMap,
        array &$stack,
        bool $root,
        array &$result = null
    ): void {
        if ($root) {
            $columnsKey = array_keys($columns);

            if (count($columnsKey) === 1 && is_array($columns[$columnsKey[0]])) {
                $indexKey = array_keys($columns)[0];
                $dataKey = preg_replace("/^[\p{L}_][\p{L}\p{N}@$#\-_]*\./u", '', $indexKey);
                $currentStack = [];

                foreach ($data as $item) {
                    $this->dataMap($data, $columns[$indexKey], $columnMap, $currentStack, false, $result);
                    $index = $data[$dataKey];

                    if (isset($result)) {
                        $result[$index] = $currentStack;
                    } else {
                        $stack[$index] = $currentStack;
                    }
                }
            } else {
                $currentStack = [];
                $this->dataMap($data, $columns, $columnMap, $currentStack, false, $result);

                if (isset($result)) {
                    $result[] = $currentStack;
                } else {
                    $stack = $currentStack;
                }
            }

            return;
        }

        foreach ($columns as $key => $value) {
            $isRaw = $this->isRaw($value);

            if (is_int($key) || $isRaw) {
                $map = $columnMap[$isRaw ? $key : $value];
                $columnKey = $map[0];
                $item = $data[$columnKey];

                if (isset($map[1])) {
                    if ($isRaw && in_array($map[1], ['Object', 'JSON'])) {
                        continue;
                    }

                    if (is_null($item)) {
                        $stack[$columnKey] = null;
                        continue;
                    }

                    switch ($map[1]) {

                        case 'Number':
                            $stack[$columnKey] = (float) $item;
                            break;

                        case 'Int':
                            $stack[$columnKey] = (int) $item;
                            break;

                        case 'Bool':
                            $stack[$columnKey] = (bool) $item;
                            break;

                        case 'Object':
                            $stack[$columnKey] = unserialize($item);
                            break;

                        case 'JSON':
                            $stack[$columnKey] = json_decode($item, true);
                            break;

                        case 'String':
                            $stack[$columnKey] = $item;
                            break;
                    }
                } else {
                    $stack[$columnKey] = $item;
                }
            } else {
                $currentStack = [];
                $this->dataMap($data, $value, $columnMap, $currentStack, false, $result);

                $stack[$key] = $currentStack;
            }
        }
    }

    /**
     * Build and execute returning query.
     *
     * @param string $query
     * @param array $map
     * @param array $data
     * @return \PDOStatement|null
     */
    private function returningQuery($query, &$map, &$data): ?PDOStatement
    {
        $returnColumns = array_map(
            function ($value) {
                return $value[0];
            },
            $data
        );

        $query .= ' RETURNING ' .
                    implode(', ', array_map([$this, 'columnQuote'], $returnColumns)) .
                    ' INTO ' .
                    implode(', ', array_keys($data));

        return $this->exec($query, $map, function ($statement) use (&$data) {
            // @codeCoverageIgnoreStart
            foreach ($data as $key => $return) {
                if (isset($return[3])) {
                    $statement->bindParam($key, $data[$key][1], $return[2], $return[3]);
                } else {
                    $statement->bindParam($key, $data[$key][1], $return[2]);
                }
            }
            // @codeCoverageIgnoreEnd
        });
    }

    // @codeCoverageIgnoreEnd

    /**
     * Enable debug mode and output readable statement string.
     *
     * @codeCoverageIgnore
     * @return Medoo
     */
    public function debug(): self
    {
        $this->debugMode = true;

        return $this;
    }

    /**
     * Enable debug logging mode.
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function beginDebug(): void
    {
        $this->debugMode = true;
        $this->debugLogging = true;
    }

    /**
     * Disable debug logging and return all readable statements.
     *
     * @codeCoverageIgnore
     * @return void
     */
    public function debugLog(): array
    {
        $this->debugMode = false;
        $this->debugLogging = false;

        return $this->debugLogs;
    }

    /**
     * Get information about the database connection.
     *
     * @codeCoverageIgnore
     * @return array
     */
    public function info(): array
    {
        $output = [
            'server' => 'SERVER_INFO',
            'driver' => 'DRIVER_NAME',
            'client' => 'CLIENT_VERSION',
            'version' => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS'
        ];

        foreach ($output as $key => $value) {
            try {
                $output[$key] = $this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
            } catch (PDOException $e) {
                $output[$key] = $e->getMessage();
            }
        }

        $output['dsn'] = $this->dsn;

        return $output;
    }
}