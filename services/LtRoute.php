<?php

class LtRoute
{
    private $method;
    private $key;
    private $value;
    private $middleware;
    private $handled = false; // Handle flag

   
   public static function __callStatic($method, $args)
    {
        $httpMethod = strtoupper($method);
        $key = $args[0] ?? null;
        $value = $args[1] ?? null;
        return new self($httpMethod, $key, $value);
    }
    // Constructor to initialize the route
    private function __construct($method, $key, $value)
    {
        $this->method = $method;
        $this->key = $key;
        $this->value = $value;

        //echo "check no of times invoking construct d ";

        // Only handle once
        if (!$this->handled) {
            $this->handle();
            //$this->handled = true;
        }
    }
    
    //this is used to get the url path to match with the routere
    public function trimBasePath(): string {
        $url = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = ltSiteHostAddress(); 
        if ($base === '/') {
            return $url;
        }
    
        if (str_starts_with($url, $base)) {
            return substr($url, strlen($base)) ?: '/';
        }
    
        return $url;
    }
    // Middleware method for chaining middleware checks
    public function middleware($callback = null)
    {
        // Check if handle hasn't been triggered already
        if (!$this->handled) {
            //$this->handle();
            return false;
            $this->handled = true;
        }

        // Middleware logic
        if ($callback !== null) {
            if (is_callable($callback)) {
                $proceed = $callback();
                if ($proceed === false) return false;
            } elseif (is_string($callback) && strpos($callback, '@') !== false) {
                list($class, $method) = explode('@', $callback, 2);
                if (class_exists($class) && method_exists($class, $method)) {
                    $instance = new $class();
                    $proceed = call_user_func([$instance, $method]);
                    if ($proceed === false) return false;
                }
            }
        }

        // If everything passes, re-handle the route (e.g., call controller)
        //return self::invokeAction($this->value);
    }
    
     // Resolve request method (with support for method spoofing)
    private function resolveRequestMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper($_POST['_method']);
            if (in_array($spoofed, ['PUT', 'DELETE', 'PATCH'])) {
                $method = $spoofed;
            }
        }
        return $method;
    }

    // Extract request data based on HTTP method and content type
    private function getRequestData()
    {
        $method = $this->resolveRequestMethod();
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            if (stripos($contentType, 'application/json') !== false) {
                $raw = file_get_contents('php://input');
                return json_decode($raw, true);
            }

            if ($method === 'POST') {
                return $_POST;
            }

            parse_str(file_get_contents("php://input"), $parsed);
            return $parsed;
        }

        return $_GET;
    }
    
     // Handle matching logic for routes
    private function handle()
    {
        //$request = $this->method === 'POST' ? $_POST : $_GET;
        
        $actualMethod = $this->resolveRequestMethod();
        if ($actualMethod !== $this->method) {
            return false;
        }

        $request = $this->getRequestData();


        if (strpos($this->key, '/') !== false) {
            $urlPath = $this->trimBasePath();
            if ($urlPath === $this->key) {
                return self::invokeAction($this->value);
            }
            return false;
        }

        if (strpos($this->key, '@') !== false) {
            list($param, $expected) = explode('@', $this->key, 2);
        } else {
            $param = 'action';
            $expected = $this->key;
        }

        if ($this->value === null) {
            if (isset($request[$param]) && $request[$param] === $expected) {
                return self::invokeAction($this->value);
            }
            return false;
        }

        if (isset($request[$param]) && $request[$param] === $expected) {
            return self::invokeAction($this->value);
        }
        return false;
    }

    // Invoke action (call controller or method)
    public static function invokeAction($value)
    {
        if (is_callable($value)) {
            return $value();
        }

        if (is_string($value) && substr_count($value, '@') >= 1) {
            $parts = explode('@', $value);

            if (count($parts) === 2) {
                list($controller, $method) = $parts;
                ltImport($controller);
            } elseif (count($parts) === 3) {
                list($module, $controller, $method) = $parts;
                $ct = pathinfo($controller, PATHINFO_EXTENSION) ? $controller : $controller . '.php';
                ltImport($module, $ct);
            } else {
                return false;
            }

            if (class_exists($controller) && method_exists($controller, $method)) {
                $instance = new $controller();
                ob_end_clean(); //cleare any output buffer
                    echo call_user_func([$instance, $method]);
                exit();  //exist any code aftermath
            }
        }

        return false;
    }
}

?>  
      