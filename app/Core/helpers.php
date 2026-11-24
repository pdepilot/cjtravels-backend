<?php

use App\Core\App;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        return match (strtolower((string)$value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('app')) {
    function app(): App
    {
        return App::getInstance();
    }
}

if (!function_exists('request')) {
    function request(): Request
    {
        return App::getInstance()->getRequest();
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): string
    {
        return View::render($view, $data);
    }
}

if (!function_exists('response')) {
    function response(string $content = '', int $status = 200, array $headers = []): Response
    {
        return new Response($content, $status, $headers);
    }
}

if (!function_exists('base_subfolder')) {
    function base_subfolder(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = dirname($script);
        if ($dir === '/' || $dir === '\\' || $dir === '.' || empty($dir)) {
            return '';
        }
        if (str_ends_with($dir, '/public') || str_ends_with($dir, '/admin')) {
            $parent = dirname($dir);
            return ($parent === '/' || $parent === '\\') ? '' : rtrim($parent, '/');
        }
        return rtrim($dir, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): Response
    {
        $base = base_subfolder();
        if ($base !== '' && str_starts_with($url, '/') && !str_starts_with($url, $base)) {
            $url = $url === '/' ? $base : ($base . '/' . ltrim($url, '/'));
        }
        return Response::redirect($url, $status);
    }
}

if (!function_exists('route')) {
    function route(string $name, array $params = []): string
    {
        $path = Router::route($name, $params);
        $base = base_subfolder();
        if ($base !== '' && !str_starts_with($path, $base)) {
            $path = $path === '/' ? $base : ($base . '/' . ltrim($path, '/'));
        }
        return $path;
    }
}

if (!function_exists('routeIs')) {
    function routeIs(string $pattern): bool
    {
        return Router::routeIs($pattern);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(config('app.url', ''), '/');
        $cleanPath = '/' . ltrim($path, '/');
        return $base . $cleanPath;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $clean = ltrim($path, '/');
        $base = base_subfolder();
        if ($base !== '') {
            return $base . '/' . $clean;
        }
        return '/' . $clean;
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $configs = [];

        $parts = explode('.', $key);
        $file = $parts[0];

        if (!isset($configs[$file])) {
            $path = App::getInstance()->getBaseDir() . 'config' . DIRECTORY_SEPARATOR . $file . '.php';
            if (file_exists($path)) {
                $configs[$file] = require $path;
            } else {
                $configs[$file] = [];
            }
        }

        $data = $configs[$file];
        for ($i = 1; $i < count($parts); $i++) {
            $part = $parts[$i];
            if (is_array($data) && array_key_exists($part, $data)) {
                $data = $data[$part];
            } else {
                return $default;
            }
        }

        return $data;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '" />';
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Session::class;
        }
        return Session::get($key, $default);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return Session::getFlash('old_' . $key, $default);
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cj_contact')) {
    function cj_contact(): array
    {
        static $contact = null;
        if ($contact !== null) {
            return $contact;
        }

        try {
            $contact = \App\Services\RealtimeDataService::contactPhone();
        } catch (\Throwable $e) {
            $contact = [
                'display' => '08135196772',
                'tel' => '+2348135196772',
                'whatsapp' => '2348135196772',
            ];
        }

        return $contact;
    }
}

if (!function_exists('cj_phone_display')) {
    function cj_phone_display(): string
    {
        return (string)(cj_contact()['display'] ?? '08135196772');
    }
}

if (!function_exists('cj_phone_tel')) {
    function cj_phone_tel(): string
    {
        return (string)(cj_contact()['tel'] ?? '+2348135196772');
    }
}

if (!function_exists('cj_whatsapp_number')) {
    function cj_whatsapp_number(): string
    {
        return (string)(cj_contact()['whatsapp'] ?? '2348135196772');
    }
}

