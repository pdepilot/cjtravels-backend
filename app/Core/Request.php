<?php

namespace App\Core;

class Request
{
    protected string $method;
    protected string $uri;
    protected array $query;
    protected array $post;
    protected array $server;

    public function __construct()
    {
        $this->server = $_SERVER;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->query = $_GET;
        $this->post = $_POST;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $this->post = array_merge($this->post, $decoded);
                }
            }
        }

        $rawUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($rawUri, PHP_URL_PATH) ?: '/';

        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $scriptDir = rtrim(dirname($scriptName), '/');

        // Check if path starts with script directory (e.g. /TravelBooking/public)
        if ($scriptDir !== '' && $scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
            $path = substr($path, strlen($scriptDir));
        } else {
            // Check base project folder (e.g. /TravelBooking)
            $base = function_exists('base_subfolder') ? base_subfolder() : '';
            if ($base === '' && (str_ends_with($scriptDir, '/public') || str_ends_with($scriptDir, '/admin'))) {
                $parent = dirname($scriptDir);
                $base = ($parent === '/' || $parent === '\\') ? '' : rtrim($parent, '/');
            }
            if ($base !== '' && strpos($path, $base) === 0) {
                $path = substr($path, strlen($base));
            }
        }

        $this->uri = '/' . trim($path, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isMethod(string $method): bool
    {
        return strcasecmp($this->method, $method) === 0;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function input(?string $key = null, mixed $default = null): mixed
    {
        $all = $this->all();
        if ($key === null) {
            return $all;
        }
        return $all[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $all = $this->all();
        return array_key_exists($key, $all);
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function header(string $key, ?string $default = null): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        if (isset($this->server[$normalized])) {
            return $this->server[$normalized];
        }
        if (isset($this->server[strtoupper($key)])) {
            return $this->server[strtoupper($key)];
        }
        return $default;
    }

    public static function create(string $uri, string $method = 'GET', array $parameters = [], array $server = []): self
    {
        $backupServer = $_SERVER;
        $backupGet = $_GET;
        $backupPost = $_POST;

        $parsedUri = parse_url($uri);
        $path = $parsedUri['path'] ?? '/';
        $queryString = $parsedUri['query'] ?? '';

        if (!empty($queryString)) {
            parse_str($queryString, $parsedQuery);
            if (strtoupper($method) === 'GET') {
                $parameters = array_merge($parsedQuery, $parameters);
            }
        }

        $_SERVER = array_merge([
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $uri,
            'SCRIPT_NAME' => '/index.php',
            'QUERY_STRING' => $queryString,
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'localhost',
        ], $server);

        if (strtoupper($method) === 'GET') {
            $_GET = $parameters;
            $_POST = [];
        } else {
            $_POST = $parameters;
            $_GET = [];
        }

        $instance = new self();

        $_SERVER = $backupServer;
        $_GET = $backupGet;
        $_POST = $backupPost;

        return $instance;
    }
}


