<?php  

class LtRequest
{
    public $method;

    public function __construct()
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        switch ($this->method) {
            case 'POST':
                $this->loadData($_POST);
                break;
            case 'GET':
                $this->loadData($_GET);
                break;
            case 'PUT':
            case 'DELETE':
            case 'PATCH':
                $this->loadRawInput();
                break;
        }
    }

    private function loadData(array $source)
    {
        foreach ($source as $key => $value) {
            $this->$key = $value;
        }
    }

    private function loadRawInput()
    {
        $input = file_get_contents('php://input');

        // Try to parse as JSON first
        $data = json_decode($input, true);

        if (is_array($data)) {
            $this->loadData($data);
        } else {
            // Fallback to form-encoded parsing
            parse_str($input, $parsed);
            $this->loadData($parsed);
        }
    }
}
?>
      