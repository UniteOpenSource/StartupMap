<?php
class Http_Exception extends Exception{
	final const NOT_MODIFIED = 304;
	final const BAD_REQUEST = 400;
	final const NOT_FOUND = 404;
	final const NOT_ALOWED = 405;
	final const CONFLICT = 409;
	final const PRECONDITION_FAILED = 412;
	final const INTERNAL_ERROR = 500;
}

class Http_Multiple_Error
{
	function __construct(private $_status, private $_type, private $_url, private $_params)
 {
 }

	function getStatus()
	{
		return $this->_status;
	}

	function getType()
	{
		return $this->_type;
	}

	function getUrl()
	{
		return $this->_url;
	}

	function getParams()
	{
		return $this->_params;
	}
}

class Http
{
	private $_user;
	private $_pass;

	final const HTTP  = 'http';
	final const HTTPS = 'https';
	/**
	 * Factory of the class. Lazy connect
	 *
	 * @param string $host
	 * @param integer $port
	 * @param string $user
	 * @param string $pass
	 * @return Http
	 */
	static public function connect($host, $port = 80, $protocol = self::HTTP,$options = null)
	{
		return new self($host, $port, $protocol, $options, false);
	}

	/**
	 *
	 * @return Http
	 */
	static public function multiConnect()
	{
		return new self(null, null, null, null, true);
	}

	private array $_append = [];
	public function add($http)
	{
		$this->_append[] = $http;
		return $this;
	}

	private false $_silentMode = false;
	/**
	 *
	 * @param bool $mode
	 * @return Http
	 */
	public function silentMode($mode=true)
	{
		$this->_silentMode = $mode;
		return $this;
	}

	protected function __construct(private $_host, $port, private $_protocol, private $_options, private $_connMultiple)
 {
 }

	public function setCredentials($user, $pass)
	{
		$this->_user = $user;
		$this->_pass = $pass;
		return $this;
	}

	final const POST   = 'POST';
	final const GET    = 'GET';
	final const DELETE = 'DELETE';
	final const PUT    = 'PUT';

	private array $_requests = [];

	/**
	 * @param string $url
	 * @param array $params
	 * @return Http
	 */
	public function put($url, $params=[])
	{
		$this->_requests[] = [self::PUT, $this->_url($url), $params];
		return $this;
	}

	/**
	 * @param string $url
	 * @param array $params
	 * @return Http
	 */
	public function post($url, $params=[])
	{
		$this->_requests[] = [self::POST, $this->_url($url), $params];
		return $this;
	}

	/**
	 * @param string $url
	 * @param array $params
	 * @return Http
	 */
	public function get($url, $params=[])
	{
		$this->_requests[] = [self::GET, $this->_url($url), $params];
		return $this;
	}

	/**
	 * @param string $url
	 * @param array $params
	 * @return Http
	 */
	public function delete($url, $params=[])
	{
		$this->_requests[] = [self::DELETE, $this->_url($url), $params];
		return $this;
	}

	public function _getRequests()
	{
		return $this->_requests;
	}

	/**
	 * PUT request
	 *
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	public function doPut($url, $params=[])
	{
		return $this->_exec(self::PUT, $this->_url($url), $params);
	}

	/**
	 * POST request
	 *
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	public function doPost($url, $params=[])
	{
		return $this->_exec(self::POST, $this->_url($url), $params);
	}

	/**
	 * GET Request
	 *
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	public function doGet($url, $params=[])
	{
		return $this->_exec(self::GET, $this->_url($url), $params);
	}

	/**
	 * DELETE Request
	 *
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	public function doDelete($url, $params=[])
	{
		return $this->_exec(self::DELETE, $this->_url($url), $params);
	}

	private array $_headers = [];
	/**
	 * setHeaders
	 *
	 * @param array $headers
	 * @return Http
	 */
	public function setHeaders($headers)
	{
		$this->_headers = $headers;
		return $this;
	}

	/**
	 * Builds absolute url
	 *
	 * @param unknown_type $url
	 * @return unknown
	 */
	private function _url($url=null)
	{
		// return "{$this->_protocol}://{$this->_host}:{$this->_port}/{$url}";
		return "{$this->_protocol}://{$this->_host}/{$url}";
	}

	final const HTTP_OK = 200;
	final const HTTP_CREATED = 201;
	final const HTTP_ACEPTED = 202;

	/**
	 * Performing the real request
	 *
	 * @param string $type
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	private function _exec($type, $url, $params = [])
	{
		$out = null;
  $headers = $this->_headers;
		$s = curl_init();
		if(is_array($this->_options))
		{
			foreach($this->_options as $key=>$val)
			{
				curl_setopt($s, $key, $val);
			}
		}
		if(!is_null($this->_user)){
			curl_setopt($s, CURLOPT_USERPWD, $this->_user.':'.$this->_pass);
		}

		switch ($type) {
		case self::DELETE:
			curl_setopt($s, CURLOPT_URL, $url . '?' . http_build_query($params));
			curl_setopt($s, CURLOPT_CUSTOMREQUEST, self::DELETE);
			break;
		case self::PUT:
			curl_setopt($s, CURLOPT_URL, $url);
			curl_setopt($s, CURLOPT_CUSTOMREQUEST, self::PUT);
			curl_setopt($s, CURLOPT_HTTPHEADER, ['Content-Length: ' . strlen(http_build_query($params))]);
			curl_setopt($s, CURLOPT_POSTFIELDS, http_build_query($params));
			break;
		case self::POST:
			curl_setopt($s, CURLOPT_URL, $url);
			curl_setopt($s, CURLOPT_POST, true);
			curl_setopt($s, CURLOPT_POSTFIELDS, $params);
			break;
		case self::GET:
			curl_setopt($s, CURLOPT_URL, $url . '?' . http_build_query($params));
			break;
		}

		curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($s, CURLOPT_HTTPHEADER, $headers);
		$_out = curl_exec($s);
		$status = curl_getinfo($s, CURLINFO_HTTP_CODE);
		$err = curl_error($s); //jic
		curl_close($s);
		switch ($status) {
		case self::HTTP_OK:
		case self::HTTP_CREATED:
		case self::HTTP_ACEPTED:
			$out = $_out;
			break;
		default:
			if (!$this->_silentMode) {
				$_dout = json_decode($_out, null, 512, JSON_THROW_ON_ERROR);
				if($_dout != NULL)
				{
					throw new Http_Exception("{$_dout->error->message}", $status);
				}else{
					error_log("Unknown http error: $_out, $err ($status)");
					throw new Http_Exception($err, $status);
				}
			}
		}
		return $out;
	}

	public function run()
	{
		if ($this->_connMultiple) {
			return $this->_runMultiple();
		} else {
			return $this->_run();
		}
	}

	private function _runMultiple()
	{
		$out= null;
		if (count($this->_append) > 0) {
			$arr = [];
			foreach ($this->_append as $_append) {
				$arr = array_merge($arr, $_append->_getRequests());
			}

			$this->_requests = $arr;
			$out = $this->_run();
		}
		return $out;
	}

	private function _run()
	{
		$headers = $this->_headers;
		$curly = $result = [];

		$mh = curl_multi_init();
		foreach ($this->_requests as $id => $reg) {
			$curly[$id] = curl_init();

			$type   = $reg[0];
			$url    = $reg[1];
			$params = $reg[2];

			if(!is_null($this->_user)){
				curl_setopt($curly[$id], CURLOPT_USERPWD, $this->_user.':'.$this->_pass);
			}

			switch ($type) {
			case self::DELETE:
				curl_setopt($curly[$id], CURLOPT_URL, $url . '?' . http_build_query($params));
				curl_setopt($curly[$id], CURLOPT_CUSTOMREQUEST, self::DELETE);
				break;
			case self::PUT:
				curl_setopt($curly[$id], CURLOPT_URL, $url);
				curl_setopt($curly[$id], CURLOPT_CUSTOMREQUEST, self::PUT);
				curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $params);
				break;
			case self::POST:
				curl_setopt($curly[$id], CURLOPT_URL, $url);
				curl_setopt($curly[$id], CURLOPT_POST, true);
				curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $params);
				break;
			case self::GET:
				curl_setopt($curly[$id], CURLOPT_URL, $url . '?' . http_build_query($params));
				break;
			}
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curly[$id], CURLOPT_HTTPHEADER, $headers);

			curl_multi_add_handle($mh, $curly[$id]);
		}

		$running = null;
		do {
			curl_multi_exec($mh, $running);
			sleep(0.2);
		} while($running > 0);

		foreach($curly as $id => $c) {
			$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
			switch ($status) {
			case self::HTTP_OK:
			case self::HTTP_CREATED:
			case self::HTTP_ACEPTED:
				$result[$id] = curl_multi_getcontent($c);
				break;
			default:
				if (!$this->_silentMode) {
					$result[$id] = new Http_Multiple_Error($status, $type, $url, $params);
				}
			}
			curl_multi_remove_handle($mh, $c);
		}

		curl_multi_close($mh);
		return $result;
	}
}
