<?php

namespace App\Core;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getStatus(): int
    {
        return $this->statusCode;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->content;
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        $response = new self(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), $statusCode);
        $response->header('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        if (function_exists('base_subfolder')) {
            $base = base_subfolder();
            if ($base !== '' && str_starts_with($url, '/') && !str_starts_with($url, $base)) {
                $url = $url === '/' ? $base : ($base . '/' . ltrim($url, '/'));
            }
        }
        $response = new self('', $statusCode);
        $response->header('Location', $url);
        return $response;
    }
}

