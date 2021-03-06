<?php
Finder::useModel('DBModel');

class PageTrackerSession extends DBModel
{
	protected $one = true;
	
	protected $pk = 'hash';
	
	protected $expireTime = 900;
	protected $sessionHash = '';
	protected $ip = '';
		
	public function initSession()
	{		
		$this->where = ($this->where ? $this->where." AND " : "")." {last_activity} > ".(time() - $this->expireTime);
		
		if ($this->getSessionHash())
		{
			$this->load("{hash} = ".DBModel::quote($this->getSessionHash()));
		}
		
		if (!$this->offsetGet('hash'))
		{
			$this->start();
		}
		
		if ($this->offsetGet('hash'))
		{
			$this->sessionHash = $this->offsetGet('hash');
			$this->saveSessionHash();
			$this->updateLastActivity();
		}
	}
	
	public function getExpireTime()
	{
		return $this->expireTime;
	}
	
	protected function start(&$storageModel = null)
	{
		$this->sessionHash = '';		
		$this->sessionHash = $this->generateSessionHash();
		$this->saveSessionHash();
		
		$data = array(
			"hash" => $this->sessionHash,
			"host" => $this->getIp(),
			"user_agent" => $this->getUserAgent(),
			"first_activity" => time(),
			"last_activity" => time(),
		);
		
		$this->insert($data);
		$this->setData(array($data));
	}

	protected function saveSessionHash()
	{
		if ($this->expireTime)
		{
			$expireTime = time() + $this->expireTime;
		}
		else
		{
			$expireTime = 0;
		}
		
		setcookie(Config::get('cookie_prefix').'track_hash', $this->getSessionHash(), $expireTime, Config::exists('front_end_path') ? Config::get('front_end_path') : RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
	}
	
	protected function updateLastActivity()
	{
		if ($this->getSessionHash())
		{
			$data = array('last_activity' => time());
			parent::update($data, "{hash} = ".DBModel::quote($this->getSessionHash()));
		}
	}
	
	protected function generateSessionHash()
	{
		return md5(time() . $this->getUserAgent() . $this->getIp() . rand(1, 1000000));
	}
		
	protected function getSessionHash()
	{
		if (!$this->sessionHash)
		{
			$this->sessionHash = $_COOKIE[Config::get('cookie_prefix')."track_hash"] ? $_COOKIE[Config::get('cookie_prefix')."track_hash"] : "";
		}
		
		return $this->sessionHash;
	}
		
	protected function getIp()
	{
		if (!$this->ip)
		{
			if ($_SERVER['HTTP_CLIENT_IP'])
			{
				$this->ip = $_SERVER['HTTP_CLIENT_IP'];
			}
			else if ($_SERVER['HTTP_X_FORWARDED_FOR'] && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches))
			{
				// make sure we dont pick up an internal IP defined by RFC1918
				foreach ($matches[0] AS $ip)
				{
					if (!preg_match("#^(10|172\.16|192\.168)\.#", $ip))
					{
						$this->ip = $ip;
						break;
					}
				}
			}
			else if ($_SERVER['HTTP_FROM'])
			{
				$this->ip = $_SERVER['HTTP_FROM'];
			}
			else
			{
				$this->ip = $_SERVER['REMOTE_ADDR'];
			}
		}

		return $this->ip;
	}
	
	protected function getUserAgent()
	{
		return $_SERVER['HTTP_USER_AGENT'];
	}
}

?>