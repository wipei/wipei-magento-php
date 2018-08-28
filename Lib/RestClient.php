<?php
namespace Wipei\WipeiPayment\Lib;
/**
 * MercadoPago cURL RestClient
 */


class RestClient {

    /**
     *API URL
     */
    const API_BASE_URL = "http://localhost:8080";

    /**
     * @param       $uri
     * @param       $method
     * @param       $content_type
     * @param array $extra_params
     *
     * @return resource
     * @throws /Exception
     */
    private static function get_connect($uri, $method, $content_type, $extra_params = array()) {
        if (!extension_loaded ("curl")) {
            throw new \Exception("cURL extension not found. You need to enable cURL in your php.ini or any other configuration you have.");
        }

        $connect = curl_init(self::API_BASE_URL . $uri);

        curl_setopt($connect, CURLOPT_USERAGENT, "Wipei - Magento 2 integration");
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connect, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $header_opt = array("Accept: application/json", "Content-Type: " . $content_type);
      
        if (count($extra_params) > 0) {
            $header_opt = array_merge($header_opt, $extra_params);
        }

        curl_setopt($connect, CURLOPT_HTTPHEADER, $header_opt);

        return $connect;
    }

    /**
     * @param $connect
     * @param $data
     * @param $content_type
     *
     * @throws /Exception
     */
    private static function set_data(&$connect, $data, $content_type) {
        if ($content_type == "application/json") {
            if (gettype($data) == "string") {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if(function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error != JSON_ERROR_NONE) {
                    throw new \Exception("JSON Error [{$json_error}] - Data: {$data}");
                }
            }
        }

        curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @param $method
     * @param $uri
     * @param $data
     * @param $content_type
     * @param $extra_params
     *
     * @return array
     * @throws /Exception
     */
    private static function exec($method, $uri, $data, $content_type, $extra_params) {

        $connect = self::get_connect($uri, $method, $content_type, $extra_params);
        if ($data) {
            self::set_data($connect, $data, $content_type);
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($api_result === FALSE) {
            throw new \Exception (curl_error ($connect));
        }

        $response = array(
            "status" => $api_http_code,
            "response" => json_decode($api_result, true)
        );

        /*if ($response['status'] >= 400) {
            $message = $response['response']['message'];
            if (isset ($response['response']['cause'])) {
                if (isset ($response['response']['cause']['code']) && isset ($response['response']['cause']['description'])) {
                    $message .= " - ".$response['response']['cause']['code'].': '.$response['response']['cause']['description'];
                } else if (is_array ($response['response']['cause'])) {
                    foreach ($response['response']['cause'] as $cause) {
                        $message .= " - ".$cause['code'].': '.$cause['description'];
                    }
                }
            }

            throw new Exception ($message, $response['status']);
        }*/

      if ($response != null && $response['status'] >= 400 && self::$check_loop == 0) {
        try {
          self::$check_loop = 1;
          $message = null;
          $payloads = null;
          $endpoint = null;
          $errors = array();
          if (isset($response['response'])) {
            if (isset($response['response']['message'])) {
              $message = $response['response']['message'];
            }
            if (isset($response['response']['cause'])) {
              if (isset($response['response']['cause']['code']) && isset($response['response']['cause']['description'])) {
                $message .= " - " . $response['response']['cause']['code'] . ': ' . $response['response']['cause']['description'];
              } else if (is_array($response['response']['cause'])) {
                foreach ($response['response']['cause'] as $cause) {
                  $message .= " - " . $cause['code'] . ': ' . $cause['description'];
                }
              }
            }
          }

          //add data
          if (isset($data) && $data != null) {
            $payloads = json_encode($data);
          }
          //add uri
          if (isset($uri) && $uri != null) {
            $endpoint = $uri;
          }

          $errors[] = array(
            "endpoint" => $endpoint,
            "message" => $message,
            "payloads" => $payloads
          );

        } catch (\Exception $e) {
          error_log("error to call API LOGS" . $e);
        }
      }
      self::$check_loop = 0;

      curl_close($connect);

      return $response;
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array  $extra_params
     *
     * @return array
     * @throws /Exception
     */
    public static function get($uri, $content_type = "application/json", $extra_params = array()) {
        return self::exec("GET", $uri, null, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param        $data
     * @param string $content_type
     * @param array  $extra_params
     *
     * @return array
     * @throws /Exception
     */
    public static function post($uri, $data, $content_type = "application/json", $extra_params = array()) {
        return self::exec("POST", $uri, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param        $data
     * @param string $content_type
     * @param array  $extra_params
     *
     * @return array
     * @throws /Exception
     */
    public static function put($uri, $data, $content_type = "application/json", $extra_params = array()) {
        return self::exec("PUT", $uri, $data, $content_type, $extra_params);
    }

    /**
     * @param        $uri
     * @param string $content_type
     * @param array  $extra_params
     *
     * @return array
     * @throws /Exception
     */
    public static function delete($uri, $content_type = "application/json", $extra_params = array()) {
        return self::exec("DELETE", $uri, null, $content_type, $extra_params);
    }

    static $check_loop = 0;
}
