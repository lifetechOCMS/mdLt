<?php 
if (!class_exists('Lifepage')) {
    class Lifepage extends LtModel { }
}

class LtNavigate {
    private $url = "";
    private $queryParams = [];

    // Set the URL for navigation
    public function to($page_name = "", $module_name = "") {
        $lifepageModel = new Lifepage;

        if ($module_name == "") {
            $lifepageModel->findId('page_name', $page_name);
            //$response_data = $lifepageModel->responseData->pageurlNew ?? null;
            $response_data = $lifepageModel->responseData['pageurlNew'] ?? null;
        } else {
            $lifepageModel->select('pageurl_new')
                ->where('page_name', $page_name)
                ->andWhere('module_name', $module_name)
                ->get();
            $response_data = $lifepageModel->responseData[0]['pageurlNew'] ?? null;
        }
        //print_r($lifepageModel->responseData[0]['pageurlNew']);
       
        $this->url = ($lifepageModel->responseCategory == "200" && $response_data)
            ? lifetech_site_host_address() . '/' . $response_data
            : "PageNotFound";

        return $this;
    }

    // Store query parameters
    public function withQuery(array $queryParams) {
        $this->queryParams = array_merge($this->queryParams, $queryParams);
        return $this;
    }

    // Store data in session
    public function withData($key, $value) {
        Session::set($key, $value);
        return $this;
    }

    // Construct full URL with query parameters
    private function buildUrl() {
        $queryString = http_build_query($this->queryParams);
        return $this->url . ($queryString ? "?$queryString" : "");
    }

    // Perform redirection
    public function redirect() { 
        header("Location: " . $this->buildUrl());
        exit();  
    }

    // Get the URL without redirecting
    public function getUrl() {
        return $this->buildUrl();
    }
}

// Helper functions
function ltNavigateTo($page_name = "", $module_name = "") {
    return (new LtNavigate())->to($page_name, $module_name);
}

function ltNavigateData($key = "") {
    return Session::get($key);
}

function ltNavigateBack() {
    return $_SERVER['HTTP_REFERER'] ?? '/';
}

/**
 * test this on testpage view
 * 
 */


?>
      