<?php
/*
 * @created Feb 21, 2008
 * @author lunatic lunatic@jetstyle.ru
 * 
 * �������� � ������ ��������
 */

class ContentPageDomain extends BasicPageDomain
{

	function getPageClassByMode($mode)
	{
		return isset($this->rh->mode_map[$mode])
		? $this->rh->mode_map[$mode]
		: (($mode ? implode('', array_map(ucfirst, explode('_', $mode))) : "Content" ) .  "Page");
	}
	
	function getModeByPageClass($cls)
	{
		$res = strtolower(trim(preg_replace('#([A-Z])#', '_\\1', $cls), '_'));
		if ($res == 'content') $res = 0;
		return $res;
	}

	function &find($criteria=NULL)
	{
		if (empty($criteria)) return False; // FIXME: lucky@npj -- ������� ��� ��������?

		$this->rh->useClass('models/ContentModel');
		$content =& new ContentModel();
		$content->initialize($this->rh);

		$where = array();
		if (!isset($criteria['class']) && $criteria['url']=="")
			$where[] = "mode='home'";
		elseif (isset($criteria['url']))
		{
			$url = $criteria['url'];
			$possible_paths = $this->getPossiblePaths($url);
			$where[] = '_path IN ('.$content->buildValues($possible_paths). ')';
		}
		elseif (isset($criteria['class']))
			$where[] = 'mode='.$content->quote($this->getModeByPageClass($criteria['class']));
		$where = implode(" AND ", $where);


		//var_dump($where);
		//die();
		
		$content->load($where);
		$data = $content->data[0];

		//SEO HACK
		$url = rtrim($criteria['url'], "/");
		if (!empty($data) && $data['mode']=="" && $data['_path']!=$url && isset($criteria['url']) )
		{
		    return false;
		    //die('11');
		}
		else if (!empty($data))
		{
			$page_cls = $this->getPageClassByMode($data['mode']);
			$config = array (
			'class' => $page_cls,
			'config' => $data,
			'path' => $data['_path'],
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
		return False;
	}
}
?>