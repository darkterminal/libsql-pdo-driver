<?php

/**
 * Parse a DSN string into its components and generate a config array for libsql.
 *
 * @param string $dsn The DSN string to parse.
 * @return array The generated configuration array.
 */
function parseDSNForLibSQL(string $dsn): array
{
    $result = [
        'driver' => null,
        'dbname' => null,
        'username' => null,
        'password' => null,
        'host' => null,
        'port' => null,
        'org' => null,
        'authToken' => null,
        'syncInterval' => null,
        'read_your_writes' => null,
        'encryptionKey' => null,
        'options' => []
    ];

    // Split DSN into parts
    $parts = explode(';', $dsn);
    foreach ($parts as $part) {
        if (strpos($part, ':') !== false) {
            // Extract driver
            [$driver, $params] = explode(':', $part, 2);
            $result['driver'] = $driver;
            $part = $params;
        }

        if (strpos($part, '=') !== false) {
            [$key, $value] = explode('=', $part, 2);
            $key = strtolower(trim($key));
            $value = trim($value);

            switch ($key) {
                case 'dbname':
                    $result['dbname'] = $value;
                    break;
                case 'password':
                    $result['password'] = $value;
                    break;
                case 'host':
                    $result['host'] = str_contains('localhost', $value) ? str_replace('localhost', 'http://127.0.0.1', $value) : $value;
                    break;
                case 'port':
                    $result['port'] = $value;
                    break;
                case 'org':
                    $result['org'] = $value;
                    break;
                case 'authtoken':
                    $result['authToken'] = $value;
                    break;
                case 'syncinterval':
                    $result['options']['syncInterval'] = (int) $value;
                    break;
                case 'read_your_writes':
                    $result['options']['read_your_writes'] = $value;
                    break;
                case 'encryptionkey':
                    $result['options']['encryptionkey'] = $value;
                    break;
                default:
                    $result['options'][$key] = $value;
            }
        }
    }

    // Build the config array
    $config = [
        "url" => "file:{$result['dbname']}",
        "authToken" => $result['authToken'] ?? $result['password'] ?? null,
        "syncUrl" => $result['host'] ?? "https://" . str_replace([".db", ".sqlite"], "", $result['dbname']) . "-{$result['org']}.turso.io",
        "syncInterval" => $result['options']['syncInterval'] ?? 0,
        "read_your_writes" => $result['options']['read_your_writes'] ?? true,
        "encryptionKey" => $result['options']['encryptionKey'] ?? null,
    ];

    return $config;
}

function configBuilder(string $dsn, ?string $password, array $options = []): array
{
    if ($dsn === ':memory:') {
        return [
            "url" => $dsn,
        ];
    }

    $config = [
        "url" => "file:{$dsn}",
        "authToken" => $password ?? $options['password'] ?? null,
    ];

    if (isset($options['url'])) {
        $config['syncUrl'] = $options['url'];
    }

    if (isset($options['syncInterval'])) {
        $config['syncInterval'] = (int) $options['syncInterval'];
    }

    if (isset($options['read_your_writes'])) {
        $config['read_your_writes'] = (bool) $options['read_your_writes'];
    }

    if (isset($options['encryptionKey'])) {
        $config['encryptionKey'] = $options['encryptionKey'];
    }

    return $config;
}

function str_ends_with_extension_name(string $string, string|array $extension) {
    $selector = is_array($extension) ? implode('|', $extension) : $extension;
    return preg_match("/\.($selector)$/", $string) === 1;
}
