<?php 
class Response {


    // ---- CORE SENDER ----
    protected function sendResponse($status, $httpCode, $message = "", $data = []) { //IMPORTANT it echos and exit imediatly    AND data should always be assoc array
        http_response_code($httpCode);

        $payload = [
            "status"  => $status,
            "message" => $message,
            "data"    => $data
        ];

        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); //Leaves / unescaped    Leaves Unicode characters as-is 
        exit;
    }

    // ---- SUCCESS ----
    protected function success($message = "Success", $data = [], $httpCode = 200) {
        $this->sendResponse("success", $httpCode, $message, $data);
    }

    // ---- ERROR ----
    protected function error($message = "Error", $data = [], $httpCode = 400) {
        $this->sendResponse("error", $httpCode, $message, $data);
    }

}
