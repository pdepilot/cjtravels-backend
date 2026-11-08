<?php

namespace App\Core;

use Closure;

class Route
{
    public string $method;
    public string $path;
    public mixed $action;
    public ?string $name = null;
    public array $middleware = [];
    protected array $paramNames = [];
    protected string $regex;

    public function __construct(string $method, string $path, mixed $action, array $middleware = [])
    {
        $this->method = strtoupper($method);
        $this->path = '/' . trim($path, '/');
        $this->action = $action;
        $this->middleware = $middleware;
        $this->compileRegex();
    }

    protected function compileRegex(): void
    {
        $pattern = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) {
            $this->paramNames[] = $matches[1];
            return '([^/]+)';
        }, $this->path);

        $this->regex = '#^' . ($pattern === '/' ? '/' : $pattern) . '$#';
    }

    public function name(string $name): self
    {
        $this->name = $name;
        Router::registerName($name, $this);
        return $this;
    }

    public function middleware(string|array $middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }

    public function matches(string $method, string $uri, array &$params = []): bool
    {
        if ($this->method !== 'ANY' && $this->method !== strtoupper($method)) {
            return false;
        }

        $normalizedUri = '/' . trim($uri, '/');
        if (preg_match($this->regex, $normalizedUri, $matches)) {
            array_shift($matches);
            $params = [];
            foreach ($this->paramNames as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
            return true;
        }

        return false;
    }
}

class Router
{
    /** @var Route[] */
    protected static array $routes = [];
    /** @var array<string, Route> */
    protected static array $namedRoutes = [];
    protected static ?Route $currentRoute = null;
    protected static array $groupStack = [];

    protected static array $middlewareAliases = [
        'admin.auth' => \App\Http\Middleware\AdminAuthMiddleware::class,
        'admin.guest' => \App\Http\Middleware\AdminGuestMiddleware::class,
        'csrf' => \App\Http\Middleware\VerifyCsrfToken::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];

    public static function aliasMiddleware(string $name, string $class): void
    {
        self::$middlewareAliases[$name] = $class;
    }

    public static function group(array $attributes, callable $callback): void
    {
        self::$groupStack[] = $attributes;
        call_user_func($callback);
        array_pop(self::$groupStack);
    }

    public static function get(string $path, mixed $action): Route
    {
        return self::addRoute('GET', $path, $action);
    }

    public static function post(string $path, mixed $action): Route
    {
        return self::addRoute('POST', $path, $action);
    }

    public static function any(string $path, mixed $action): Route
    {
        return self::addRoute('ANY', $path, $action);
    }

    protected static function addRoute(string $method, string $path, mixed $action): Route
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach (self::$groupStack as $group) {
            if (!empty($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
            if (!empty($group['middleware'])) {
                $m = is_array($group['middleware']) ? $group['middleware'] : [$group['middleware']];
                $groupMiddleware = array_merge($groupMiddleware, $m);
            }
        }

        $fullPath = $prefix . '/' . ltrim($path, '/');
        $route = new Route($method, $fullPath, $action, $groupMiddleware);
        self::$routes[] = $route;
        return $route;
    }

    public static function registerName(string $name, Route $route): void
    {
        self::$namedRoutes[$name] = $route;
    }

    public static function getRoutes(): array
    {
        return self::$routes;
    }

    public static function currentRouteName(): ?string
    {
        return self::$currentRoute?->name;
    }

    public static function routeIs(string $pattern): bool
    {
        $current = self::currentRouteName();
        if ($current === null) {
            return false;
        }

        if ($pattern === $current) {
            return true;
        }

        if (str_ends_with($pattern, '*')) {
            $prefix = rtrim($pattern, '*');
            return str_starts_with($current, $prefix);
        }

        return false;
    }

    public static function route(string $name, array $params = []): string
    {
        if (!isset(self::$namedRoutes[$name])) {
            return '/' . trim($name, '/');
        }

        $path = self::$namedRoutes[$name]->path;
        foreach ($params as $key => $val) {
            $path = str_replace('{' . $key . '}', (string)$val, $path);
        }

        return $path;
    }

    public static function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach (self::$routes as $route) {
            $params = [];
            if ($route->matches($method, $uri, $params)) {
                self::$currentRoute = $route;
                return self::runPipeline($route, $request, $params);
            }
        }

        return new Response(
            View::render('pages/404', ['title' => 'Page Not Found', 'uri' => $uri]),
            404
        );
    }

    protected static function runPipeline(Route $route, Request $request, array $params): Response
    {
        $middleware = $route->middleware;

        // Destination action at the center of the pipeline
        $destination = function (Request $req) use ($route, $params): Response {
            return self::executeAction($route->action, $params, $req);
        };

        if (empty($middleware)) {
            return $destination($request);
        }

        // Wrap pipeline in reverse order
        $pipeline = array_reverse($middleware);
        $next = $destination;

        foreach ($pipeline as $m) {
            $currentMiddleware = $m;
            $previousNext = $next;

            $next = function (Request $req) use ($currentMiddleware, $previousNext): Response {
                [$class, $args] = self::parseMiddleware($currentMiddleware);
                if (!class_exists($class)) {
                    throw new \RuntimeException("Middleware class [{$class}] not found.");
                }

                $instance = new $class();
                return $instance->handle($req, $previousNext, ...$args);
            };
        }

        return $next($request);
    }

    protected static function parseMiddleware(string $middleware): array
    {
        $parts = explode(':', $middleware, 2);
        $name = $parts[0];
        $args = isset($parts[1]) ? explode(',', $parts[1]) : [];

        $class = self::$middlewareAliases[$name] ?? $name;
        return [$class, $args];
    }

    protected static function executeAction(mixed $action, array $params, Request $request): Response
    {
        $result = null;

        if (is_callable($action)) {
            $result = call_user_func_array($action, array_merge([$request], array_values($params)));
        } elseif (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $controller = new $class();
            $result = call_user_func_array([$controller, $method], array_merge([$request], array_values($params)));
        } elseif (is_string($action) && str_contains($action, '@')) {
            [$class, $method] = explode('@', $action);
            $fullClass = str_starts_with($class, 'App\\') ? $class : 'App\\Http\\Controllers\\' . $class;
            $controller = new $fullClass();
            $result = call_user_func_array([$controller, $method], array_merge([$request], array_values($params)));
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return new Response((string)$result);
    }
}
