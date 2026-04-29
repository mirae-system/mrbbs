<?php
/**
 * core/ApiClient.php
 * 외부 API 연동 기반 추상 클래스 — PHP 7.3 호환
 */
abstract class ApiClient
{
    protected $timeout  = 10;
    protected $lastError = '';

    abstract protected function getBaseUrl();
    abstract protected function getHeaders();

    public function get($endpoint, $params = array()) {
        $url = $this->getBaseUrl() . $endpoint;
        if ($params) $url .= '?' . http_build_query($params);
        return $this->request('GET', $url, array());
    }

    public function post($endpoint, $data = array()) {
        return $this->request('POST', $this->getBaseUrl() . $endpoint, $data);
    }

    protected function request($method, $url, $data) {
        $headers = $this->getHeaders();
        $headerLines = array();
        foreach ($headers as $k => $v) {
            $headerLines[] = "$k: $v";
        }

        $opts = array(
            'http' => array(
                'method'        => $method,
                'header'        => implode("\r\n", $headerLines),
                'timeout'       => $this->timeout,
                'ignore_errors' => true,
            )
        );

        if ($data && in_array($method, array('POST','PUT','PATCH'))) {
            $opts['http']['content']  = json_encode($data);
            $opts['http']['header'] .= "\r\nContent-Type: application/json";
        }

        $ctx    = stream_context_create($opts);
        $result = @file_get_contents($url, false, $ctx);

        if ($result === false) {
            $this->lastError = 'HTTP request failed: ' . $url;
            return null;
        }
        $decoded = json_decode($result, true);
        return $decoded !== null ? $decoded : array('raw' => $result);
    }

    public function getLastError() { return $this->lastError; }
}

class GoogleOAuthClient extends ApiClient
{
    private $accessToken;

    public function __construct($accessToken = '') {
        $this->accessToken = $accessToken;
    }

    protected function getBaseUrl() { return 'https://www.googleapis.com'; }

    protected function getHeaders() {
        return array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept'        => 'application/json',
        );
    }

    public function exchangeCode($code) {
        $clientId     = defined('GOOGLE_CLIENT_ID_DB')     && GOOGLE_CLIENT_ID_DB     ? GOOGLE_CLIENT_ID_DB     : GOOGLE_CLIENT_ID;
        $clientSecret = defined('GOOGLE_CLIENT_SECRET_DB') && GOOGLE_CLIENT_SECRET_DB ? GOOGLE_CLIENT_SECRET_DB : GOOGLE_CLIENT_SECRET;

        $data = array(
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => GOOGLE_REDIRECT_URI,
            'grant_type'    => 'authorization_code',
        );
        $ctx = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
                'timeout' => 10,
            )
        ));
        $result = @file_get_contents('https://oauth2.googleapis.com/token', false, $ctx);
        return $result ? json_decode($result, true) : null;
    }

    public function getUserInfo() {
        return $this->get('/oauth2/v2/userinfo');
    }
}

class NaverOAuthClient extends ApiClient
{
    private $accessToken;

    public function __construct($accessToken = '') {
        $this->accessToken = $accessToken;
    }

    protected function getBaseUrl() { return 'https://openapi.naver.com'; }

    protected function getHeaders() {
        return array(
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Accept'        => 'application/json',
        );
    }

    public function exchangeCode($code, $state) {
        $clientId     = defined('NAVER_CLIENT_ID_DB')     && NAVER_CLIENT_ID_DB     ? NAVER_CLIENT_ID_DB     : NAVER_CLIENT_ID;
        $clientSecret = defined('NAVER_CLIENT_SECRET_DB') && NAVER_CLIENT_SECRET_DB ? NAVER_CLIENT_SECRET_DB : NAVER_CLIENT_SECRET;

        $url = 'https://nid.naver.com/oauth2.0/token?' . http_build_query(array(
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'state'         => $state,
        ));
        $result = @file_get_contents($url);
        return $result ? json_decode($result, true) : null;
    }

    public function getUserInfo() {
        return $this->get('/v1/nid/me');
    }
}
