<?php
namespace Wipei\WipeiPayment\Lib;
/**
 * Wipei API integration
 * 
 */

class Api {

    /**
     * Api version
     */
    const version = "0.1.0";

    /**
     * @var mixed
     */
    private $client_id;
    /**
     * @var mixed
     */
    private $client_secret;
    /**
     * @var mixed
     */
    private $ll_access_token;
    /**
     * @var
     */
    private $access_data;

    /**
     * @var null
     */
    private $_platform = null;
    /**
     * @var null
     */
    private $_so = null;
    /**
     * @var null
     */
    private $_type = null;

    /**
     * @var \Wipei\WipeiPayment\Helper\Data
     */
    protected $_helperData;
    /**
     * \Wipei\WipeiPayment\Lib\Api constructor.
     * @throws
     */
    public function __construct(
    ) {
        $i = func_num_args();

        if ($i > 2 || $i < 1) {
            throw new \Exception('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }

        if ($i == 1) {
            $this->ll_access_token = func_get_arg(0);
        }

        if ($i == 2) {
            $this->client_id = func_get_arg(0);
            $this->client_secret = func_get_arg(1);
        }
    }

    /**
     * Get Access Token for API use
     * @throws
     */
    public function get_access_token() {
        $this->_helperData->log("setting access token::", 'wipei.log');

        if (isset ($this->ll_access_token) && !is_null($this->ll_access_token)) {
            $this->_helperData->log("Access Token already set::", 'wipei.log');
            return $this->ll_access_token;
        }

        $app_client_values = $this->build_query(array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
        ));

        $access_data = \Wipei\WipeiPayment\Lib\RestClient::post("/token", $app_client_values, "application/x-www-form-urlencoded");
        $this->_helperData->log("Access Token request sent::", 'wipei.log', $access_data);

        if ($access_data["status"] != 200) {
            throw new \Exception ($access_data['response']['message'], $access_data['status']);
        }

        $this->access_data = $access_data['response'];

        return $this->access_data['access_token'];
    }

    /**
     * Create a checkout preference
     * @param array $preference
     * @return array(json)
     * @throws
     */
    public function create_preference($preference) {
        $this->_helperData->log("get access token::", 'wipei.log');

        $access_token = $this->get_access_token();
        $this->_helperData->log("Access Token created::", 'wipei.log', $access_token);

        $extra_params =  array('platform: ' . $this->_platform, 'so;', 'type: ' .  $this->_type, 'Authorization: ' . $access_token);
        $this->_helperData->log("With extra params::", 'wipei.log', $extra_params);

        $preference_result = \Wipei\WipeiPayment\Lib\RestClient::post("/order", $preference, "application/json", $extra_params);
        $this->_helperData->log("Preference result::", 'wipei.log', $preference_result);

        return $preference_result;
    }

    /**
     * Get a checkout preference
     * @param string $id
     * @return
     * @throws
     */
    public function get_preference($id) {
        $access_token = $this->get_access_token();

        $extra_params =  array('Authorization: ' . $access_token);
        $preference_result = \Wipei\WipeiPayment\Lib\RestClient::get("/order?id=" . $id,null, $extra_params);
        return $preference_result;
    }

    /* Generic resource call methods */

    /**
     * Generic resource get
     * @param $authenticate = true
     * @param $uri
     * @param $params
     * @return array
     * @throws
     */
    public function get($uri, $params = null, $authenticate = true) {
        $params = is_array ($params) ? $params : array();

        if ($authenticate !== false) {
            $access_token = $this->get_access_token();

            $params["access_token"] = $access_token;
        }

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }
        $extra_params =  array('Authorization: ' . $access_token);

        $result = \Wipei\WipeiPayment\Lib\RestClient::get($uri, null, $extra_params);
        return $result;
    }

    /**
     * Generic resource post
     * @param $uri
     * @param $data
     * @param $params
     * @return array
     * @throws
     */
    public function post($uri, $data, $params = null) {
        $params = is_array ($params) ? $params : array();

        $access_token = $this->get_access_token();
        $params["access_token"] = $access_token;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $extra_params =  array('platform: ' . $this->_platform, 'so;', 'type: ' .  $this->_type);
        $result = \Wipei\WipeiPayment\Lib\RestClient::post($uri, $data, "application/json", $extra_params);
        return $result;
    }

    /**
     * Generic resource put
     * @param $uri
     * @param $data
     * @param $params
     * @return array
     * @throws
     */
    public function put($uri, $data, $params = null) {
        $params = is_array ($params) ? $params : array();

        $access_token = $this->get_access_token();
        $params["access_token"] = $access_token;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $result = \Wipei\WipeiPayment\Lib\RestClient::put($uri, $data);
        return $result;
    }

    /**
     * Generic resource delete
     * @param $uri
     * @param $params
     * @return array
     * @throws
     */
    public function delete($uri, $params = null) {
        $params = is_array ($params) ? $params : array();

        $access_token = $this->get_access_token();
        $params["access_token"] = $access_token;

        if (count($params) > 0) {
            $uri .= (strpos($uri, "?") === false) ? "?" : "&";
            $uri .= $this->build_query($params);
        }

        $result = \Wipei\WipeiPayment\Lib\RestClient::delete($uri);
        return $result;
    }

    /* **************************************************************************************** */

    /**
     * @param $params
     *
     * @return string
     */
    private function build_query($params) {
        if (function_exists("http_build_query")) {
            return http_build_query($params, "", "&");
        } else {
            $elements = [];
            foreach ($params as $name => $value) {
                $elements[] = "{$name}=" . urlencode($value);
            }

            return implode("&", $elements);
        }
    }

    /**
     * @param null $platform
     */
    public function set_platform($platform)
    {
        $this->_platform = $platform;
    }

    /**
     * @param null $so
     */
    public function set_so($so = '')
    {
        $this->_so = $so;
    }

    /**
     * @param null $type
     */
    public function set_type($type)
    {
        $this->_type = $type;
    }

    /**
     * @param null $type
     */
    public function set_log($helperData)
    {
        $this->_helperData = $helperData;
    }

    

}

