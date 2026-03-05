<?php
declare(strict_types=1);

/**
 * Simple URL Router
 * Parses REQUEST_URI, matches against registered routes, and dispatches to controllers.
 */
class Router
{
    /** @var array Registered routes */
    private array $routes = [];

    /**
     * Registers a GET route.
     *
     * @param string   $pattern  URL pattern, e.g. '/blog/{slug}'
     * @param callable|array $handler Controller callback or [ClassName, method]
     * @return void
     */
    public function get(string $pattern, callable|array $handler): void
    {
        $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Registers a POST route.
     *
     * @param string   $pattern
     * @param callable|array $handler
     * @return void
     */
    public function post(string $pattern, callable|array $handler): void
    {
        $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Adds a route to the internal registry.
     *
     * @param string         $method
     * @param string         $pattern
     * @param callable|array $handler
     * @return void
     */
    private function addRoute(string $method, string $pattern, callable|array $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler,
            'regex' => $this->patternToRegex($pattern),
        ];
    }

    /**
     * Converts a route pattern like '/blog/{slug}' to a regex.
     *
     * @param string $pattern
     * @return string
     */
    private function patternToRegex(string $pattern): string
    {
        $escaped = preg_quote($pattern, '#');
        $regex = preg_replace('#\\\{([a-zA-Z_]+)\\\}#', '([^/]+)', $escaped);
        return '#^' . $regex . '$#';
    }

    /**
     * Dispatches the current request to the matching route handler.
     * Returns a 404 response if no route matches.
     *
     * @return void
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Strip sub-directory path if running in a subfolder (e.g. localhost/devlync.com)
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        $scriptName = str_replace('\\', '/', $scriptName);
        if ($scriptName !== '/' && strpos($uri, $scriptName) === 0) {
            $uri = substr($uri, strlen($scriptName));
        }

        $uri = '/' . trim((string) $uri, '/');
        if ($uri === '') {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (preg_match($route['regex'], $uri, $matches)) {
                array_shift($matches); // Remove full match
                $this->callHandler($route['handler'], $matches);
                return;
            }
        }

        // No route matched
        $this->handle404();
    }

    /**
     * Calls the route handler with extracted URL parameters.
     *
     * @param callable|array $handler
     * @param array          $params
     * @return void
     */
    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = new $class();
            call_user_func_array([$controller, $method], $params);
        } else {
            call_user_func_array($handler, $params);
        }
    }

    /**
     * Renders the 404 error page.
     *
     * @return void
     */
    private function handle404(): void
    {
        http_response_code(404);
        $viewFile = VIEWS_PATH . '/errors/404.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<h1>404 — Page Not Found</h1>';
        }
    }
}
