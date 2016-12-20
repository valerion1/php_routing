<?php

namespace Routing\Http;

use Routing\Helpers\Helper;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

use InvalidArgumentException;

//class Request extends Message implements ServerRequestInterface {
class Request extends Message implements ServerRequestInterface {

    const METHOD_HEAD = 'HEAD';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';

    protected $validMethods = [
        'HEAD',
        'GET',
        'POST',
        'PUT',
        'PATCH',
        'DELETE'
    ];

//    protected static $headers = [];

    protected $method;

    protected $code;

    protected $fields = [];

//    protected $body;

    protected $cookies;

    protected $session;

    protected $uploadFiles;

    protected $queryParams;


    public function __construct(array $userHeaders = []) {


        $this->headers = $this->makeDefaultHeaders();
        $this->setCurrentHeaders();

        $this->setQueryData();

        if( ! empty($userSettings))
            $this->headers = array_merge($this->headers, $userHeaders);

    }

    protected function setCurrentHeaders() {

        $this->headers['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'];

        $this->headers['QUERY_STRING'] = urldecode($_SERVER['QUERY_STRING']); // example.com/page/?a=b&c=d -> a=b&c=d

        $this->headers['REQUEST_URI_FULL'] = urldecode($_SERVER['REQUEST_URI']);

        $this->headers['REQUEST_URI'] = str_replace([$this->headers['QUERY_STRING'], '?'], '', $this->headers['REQUEST_URI_FULL']); // example.com/page/?a=b&c=d -> /page/

        $this->headers['SERVER_PORT'] = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80; // $_SERVER['SERVER_PORT'] ?? 80;

        $this->headers['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'];

        $this->headers['SERVER_SOFTWARE'] = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null;// $_SERVER['SERVER_SOFTWARE'] ?? '';

        $this->headers['CONTENT_LENGTH'] = $_SERVER['CONTENT_LENGTH'];

        $this->headers['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];

        $this->headers['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'];

        $this->headers['ACCEPT'] = $_SERVER['HTTP_ACCEPT'];

        $this->headers['PATH_INFO'] = $_SERVER['PATH_INFO'];

//        $this->headers['COOKIE'] = $_SERVER['HTTP_COOKIE'];

        $this->headers['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }

    protected function makeDefaultHeaders($usersData = []) {

        return array_merge([
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => '',
            'PATH_INFO' => '',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'ACCEPT_LANGUAGE' => 'en-US,en;q=0.8',
            'ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.3',
            'USER_AGENT' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
            'CONTENT_TYPE' => ''
        ], $usersData);

    }

    protected function setQueryData(){

        $input = file_get_contents('php://input');

        if($input === false)
            throw new \Exception('Can`t get data by ' . $this->method);

        $this->queryParams = $this->headers['QUERY_STRING'];

        $this->setBody($input);

        $this->fields = array_diff($_REQUEST, $_COOKIE); // all but cookie

        $this->cookies = $_COOKIE;

        $this->uploadFiles = $_FILES; // todo: create FILE class

        if(session_status() != PHP_SESSION_NONE)
            $this->session = $_SESSION;

    }

    public function setBody($input) {

        if($this->isJson())
            $this->body = json_decode($input, true);
        else if($this->isXml()) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);
            $this->body = $result;
        }
        else if ($this->isMedia()){
            parse_str($input, $data);
            $this->body = $data;
        }

        $this->body = $input;
    }

    public function uri() {
        return $this->headers['REQUEST_URI'];
    }

    public function uriFull() {
        return self::$headers['REQUEST_URI_FULL'];
    }

    public function method() {
        return $this->headers['REQUEST_METHOD'];
    }

    public function isJson() {
        return $this->headers['CONTENT_TYPE'] === 'application/json';
    }

    public function isXml() {
        return  $this->headers['CONTENT_TYPE'] === 'application/xml' ||
                $this->headers['CONTENT_TYPE'] === 'text/xml';
    }

    public function isXhr() {
        return $this->hasHeader('X-Requested-With') &&
            $this->headers['X-Requested-With'] === 'XMLHttpRequest';
    }

    public function isMedia() {
        return stristr($this->headers['CONTENT_TYPE'], 'multipart/form-data');
    }

    public function __get($name) {
        return isset($this->fields[$name]) ?
            $this->fields[$name] : null;
    }

    public function getServerParams() {
        return $this->headers;
    }

    public function getCookieParams() {
        return $this->cookies;
    }

    public function withCookieParams(array $cookies) {

        $clone = clone $this;
        $clone->cookies = $cookies;

        return $clone;
    }

    public function getQueryParams() {
        return $this->queryParams;
    }

    public function withQueryParams(array $query) {

        $clone = clone $this;

        $clone->queryParams = $query;

        return $clone;
    }

    public function getUploadedFiles() {
        return $this->uploadFiles;
    }

    public function withUploadedFiles(array $uploadedFiles) {

        $clone = clone $this;

        $clone->uploadFiles = $uploadedFiles;

        return $clone;
    }

    public function getParsedBody() {
        // TODO: parse?
        return $this->body;
    }

    public function withParsedBody($data) {

        $clone = clone $this;
        $clone->body = $data;

        return $clone;
    }

    public function getAttributes() {
        return $this->fields;
    }

    public function getAttribute($name, $default = null) {
        return isset($this->fields[$name]) ? $this->fields[$name] : $default;
    }

    public function withAttribute($name, $value) {

        $clone = clone $this;
        $clone->fields[$name] = $value;

        return $clone;
    }

    public function withoutAttribute($name) {

        $clone = clone $this;
        if(isset($clone->fields[$name]))
            unset($clone->fields[$name]);

        return $clone;
    }


}