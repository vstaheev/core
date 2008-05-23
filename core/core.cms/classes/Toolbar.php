<?php
/**
 * @author lunatic lunatic@jetstyle.ru
 *  
 * @modified 09.05.2008
 */
 
 class Toolbar
 {
 	protected $rh;
 	protected $items = array('main' => array(), 'submenu' => array());
 	
 	public function __construct(&$rh)
 	{
 		$this->rh = &$rh;
 	}
 	 	
 	public function getData()
 	{
 		$this->load();
 		return $this->items;
 	}
 	
 	/**
 	 * load two levels of menu
 	 */
 	protected function load()
 	{
 		$this->constructResult($this->getLoadResult());
 	}
 	
 	protected function getLoadResult()
 	{
 		return $this->rh->db->execute("" .
 				"SELECT id, title, href, _level, _parent " .
 				"FROM ??toolbar " .
 				"WHERE _state = 0 AND _level IN (1,2) " .
 				"ORDER BY _level ASC, _order ASC " .
 		"");
 	}
 	
 	protected function constructResult($result)
 	{
 		$moduleName = 'do/'.$this->rh->params[0];

 		while($r = $this->rh->db->getRow($result))
 		{
 			$r['granted'] = $this->rh->principal->isGrantedTo($r['href']);
 			
 			
 			if($r['_level'] == 1)
 			{
 				$this->items['main'][$r['id']] = $r;
 			}
 			else
 			{
 				if(!$r['granted'])
 				{
 					continue;
 				}
 				elseif($r['granted'] && $this->items['main'][$r['_parent']])
 				{
 					$this->items['main'][$r['_parent']]['granted'] = true;
 				}
 				
 				if(!isset($this->items['submenu'][$r['_parent']]))
 				{
 					$this->items['submenu'][$r['_parent']] = array('id' => $r['_parent'], 'childs' => array());
 				}
 				$this->items['submenu'][$r['_parent']]['childs'][$r['id']] = $r;
 			}
 			if($moduleName == $r['href'])
 			{
 				if($this->items['main'][$r['id']])
 				{
 					$this->items['main'][$r['id']]['selected'] = true;
 					$this->rh->tpl->set('menu_selected', $r['id']);
 				}
 				else
 				{
 					$this->items['submenu'][$r['_parent']]['childs'][$r['id']]['selected'] = true;
 					$this->items['submenu'][$r['_parent']]['selected'] = true;
 					$this->items['main'][$r['_parent']]['selected'] = true;
 					$this->rh->tpl->set('menu_selected', $r['_parent']);
 				}
 			}
 		}
 		
 		foreach($this->items['main'] AS $k => $item)
 		{
 			if(!$item['granted'])
 			{
 				unset($this->items['submenu'][$item['id']], $this->items['main'][$k]);
 			}
 		}
 	}
 	 	
 }
?>