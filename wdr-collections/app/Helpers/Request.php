<?php
/**
 * Woo Discount Rules: Collections
 *
 * @package   wdr-collections
 * @author    Anantharaj B <anantharaj@flycart.org>
 * @copyright 2022 Flycart
 * @license   GPL-3.0-or-later
 * @link      https://flycart.org
 */

namespace WDR_COL\App\Helpers;

defined('ABSPATH') or exit;

class Request
{
    /**
     * $_REQUEST & php://input
     *
     * @var array
     */
    protected $params;

    /**
     * $_GET
     *
     * @var array
     */
    public $query;

    /**
     * $_POST
     *
     * @var array
     */
    public $post;

    /**
     * $_SERVER
     *
     * @var array
     */
    public $server;

    /**
     * $_FILES
     *
     * @var array
     */
    public $files;

    /**
     * $_COOKIE
     *
     * @var array
     */
    public $cookies;

    /**
     * Request Headers
     *
     * @var array
     */
    public $headers;

    /**
     * List of available types.
     *
     * @var array
     */
    protected $types = [
        'params',
        'query',
        'post',
        'cookies',
        'files',
        'server',
        'headers',
    ];

    /**
     * Build request.
     */
    public function __construct()
    {
        $this->params     = $this->request();
        $this->query   	  = $_GET;
        $this->post       = $_POST;
        $this->cookies    = $_COOKIE;
        $this->files      = $_FILES;
        $this->server     = $_SERVER;
        $this->headers    = $this->getHeaders();
    }

    /**
     * Get all Headers
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $new_name = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($name, 5)))));
                $headers[$new_name] = $value;
            } elseif ($name == 'CONTENT_TYPE') {
                $headers['Content-Type'] = $value;
            } elseif ($name == 'CONTENT_LENGTH') {
                $headers['Content-Length'] = $value;
            }
        }
        return $headers;
    }


    /**
     * Gets $_REQUEST & php://input
     *
     * @return array
     */
    public function request()
    {
        $server  = $_SERVER;
        $request = $_REQUEST;

        if (isset($server['CONTENT_TYPE']) && $server['CONTENT_TYPE'] == 'application/json') {
            $json = json_decode(file_get_contents('php://input'), true);
            if ($json) {
                $request = array_merge($request, $json);
            }
        }
        return $request;
    }

    /**
     * Check given type is valid.
     *
     * @param  string
     * @return void
     */
    protected function checkType($type)
    {
        if (!in_array($type, $this->types)) {
            throw new \UnexpectedValueException('Expected a valid type on request method');
        }
    }

    /**
     * Gets a request parameter.
     *
     * @param  string
     * @param  string
     * @param  string
     * @param  string
     * @return string
     */
    public function get($var, $default = '', $type = 'params', $sanitize = '')
    {
        $this->checkType($type);

        if (!isset($this->{$type}[$var]) || empty($this->{$type}[$var])) {
            return $default;
        }

        $value = $this->{$type}[$var];

        if (!empty($sanitize)) {
            return Input::sanitize($value, $sanitize);
        }
        return $value;
    }

    /**
     * Check if a request parameter exists.
     *
     * @param  string
     * @param  string
     * @return bool
     */
    public function has($var, $type = 'params')
    {
        return $this->get($var, null, $type) !== null;
    }

    /**
     * Return all the request params.
     *
     * @param  string
     * @return mixed
     */
    public function all($type = 'params')
    {
        $this->checkType($type);

        return $this->$type;
    }

    /**
     * Merges an array into the request params.
     *
     * @param  array
     * @param  string
     * @return void
     */
    public function merge($data, $type = 'params')
    {
        $this->checkType($type);

        $this->{$type} = array_merge($this->{$type}, $data);
    }

    /**
     * Returns only the values specified by $keys
     *
     * @param  array
     * @param  string
     * @return array
     */
    public function only($keys, $type = 'params')
    {
        $keys = is_array($keys) ? $keys : array($keys);

        $results = [];
        foreach ($keys as $key) {
            if ($this->has($key, $type)) {
                $results[$key] = $this->get($key, $type);
            }
        }
        return $results;
    }

    /**
     * Return all the request params except values specified by $keys
     *
     * @param  array
     * @param  string
     * @return array
     */
    public function except($keys, $type = 'params')
    {
        $keys = is_array($keys) ? $keys : array($keys);

        $results = $this->all($type);
        foreach ($keys as $key) {
            if (isset($results[$key])) {
                unset($results[$key]);
            }
        }
        return $results;
    }

    /**
     * Gets method used, supporting _method
     *
     * @return string
     */
    public function method()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST') {
            if (isset($_SERVER['X-HTTP-METHOD-OVERRIDE'])) {
                $method = $_SERVER['X-HTTP-METHOD-OVERRIDE'];
            } elseif ($this->has('_method')) {
                $method = $this->get('_method');
            }
        }
        return strtoupper($method);
    }
}