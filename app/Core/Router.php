<?php
namespace App\Core;

class Router
{
    private $routes = [];

    public function get($path, $controller, $method, array $middleware = [])
    {
        $this->routes['GET'][$path] = compact('controller', 'method', 'middleware');
    }

    public function post($path, $controller, $method, array $middleware = [])
    {
        $this->routes['POST'][$path] = compact('controller', 'method', 'middleware');
    }

    public function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (!isset($this->routes[$requestMethod][$uri])) {
            Controller::abort("Kh\u{00F4}ng t\u{00EC}m th\u{1EA5}y trang", "Kh\u{00F4}ng c\u{00F3} route ph\u{00F9} h\u{1EE3}p: {$requestMethod} {$uri}", 404);
        }
        $route = $this->routes[$requestMethod][$uri];
        try {
            foreach ($route['middleware'] as $middleware) {
                (new $middleware())->handle();
            }
            $controller = new $route['controller']();
            $controller->{$route['method']}();
        } catch (\Throwable $e) {
            Logger::error('http.unhandled_exception', ['exception' => get_class($e), 'message' => $e->getMessage(), 'path' => $uri]);
            Controller::abort(
                "\u{0110}\u{00E3} x\u{1EA3}y ra l\u{1ED7}i h\u{1EC7} th\u{1ED1}ng",
                get_class($e) . ': ' . $e->getMessage() . " t\u{1EA1}i " . $e->getFile() . ':' . $e->getLine()
            );
        }
    }
}
