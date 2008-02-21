<?php
/*
 * @created Feb 21, 2008
 * @author lunatic lunatic@jetstyle.ru
 * 
 * �������� ����� ������� �������
 */
 
class HandlerPageDomain extends BasicPageDomain
{
	function findByUrl($url)
	{
		$possible_paths = $this->getPossiblePaths($url);

		foreach ($possible_paths as $up)
		{
			if (isset($this->handlers_map[$up]) || isset($this->handlers_map[$up."/"]))
			{
				//���� ������� ����� �����������
				if (isset($this->handlers_map[$up]))
				{
					$_handler = $this->handlers_map[$up];
				}
				//���� ��� ������ �� ������
				else if ($this->handlers_map[$up."/"][strlen($this->handlers_map[$up."/"])-1]=="*")
				{
					$_handler = $url;
				}
				//������� ������� �� ���� ������� (������ � ������)
				elseif ($this->handlers_map[$up."/"])
				{
					$_handler = $this->handlers_map[$up."/"];
				}

				/*
				* �������� ������� ����������� �� �����
				*/
				if (!empty($_handler))
				{
					$page_cls = $_handler;
					$config = array (
					'class' => $page_cls,
					'config' => array (),
					'path' => $up,
					'url' => $url,
					);
					if ($this->rh->FindScript("classes/controllers", $page_cls))
					{
						$this->rh->UseClass("controllers/".$page_cls);
						if ($this->handler = &$this->buildPage($config))
						{
							return True;
						}
					}
				}
			}
		}
		return False;
	}

	function findByClass($page_cls)
	{
		/*
		* �������� ������� ����������� �� �����
		*/
		if (!empty($page_cls))
		{
			$config = array (
			'class' => $page_cls,
			'config' => array (),
			'path' => $this->rh->url,
			'url' => $this->rh->url,
			);
			if ($this->rh->FindScript("classes/controllers", $page_cls))
			{
				$this->rh->UseClass("controllers/".$page_cls);
				if ($this->handler = &$this->buildPage($config))
				{
					return True;
				}
			}
		}
		return False;
	}

	function &find($criteria)
	{
		if (empty($criteria)) return False;

		if (isset($criteria['url'])) return $this->findByUrl($criteria['url']);
		if (isset($criteria['class'])) return $this->findByClass($criteria['class']);
		return False;
	}

}
 
?>