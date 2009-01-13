<?

$this->UseClass("DBDataEditTree");

class TreeControl extends DBDataEditTree  {

	var $rh; //  $rh
	var $config; //    ModuleConfig
	var $loaded = false; //   ?

	var $state; // StateSet

	//templates
	var $template = "tree_control.html";
	var $template_head = "tree_control.html:Head";
	var $template_control = "tree_control.html:Control";
	var $template_response = "tree_control.html:Response";
	var $template_trash_show = "list_advanced.html:TrashShow";
	var $template_trash_hide = "list_advanced.html:TrashHide";
	var $store_to = "";
	var $_href_template; //   

	var $id_get_var = 'id';
	var $tree_behavior = 'explorer';//''classic

	var $EVOLUTORS = array();


	function TreeControl( &$config )
    {
		//base modules binds
		$this->config =& $config;
		//DBData
		//		$config->Read('tree');
		$config->SELECT_FIELDS = array_merge( $config->SELECT_FIELDS, array('_parent','_state','_left','_right') );
		DBDataEditTree::DBDataEditTree( $config->rh, $config->table_name, $config->SELECT_FIELDS, $config->where );
		$this->prefix = $config->module_name.'_tree_';
		$this->result_mode = 2;
		//StateSet
		$this->state =& new StateSet($this->rh);
		$this->state->Set($this->rh->state);
		//  
		$this->id = $this->rh->state->keep('id','integer');
		//   
		$this->rh->state->Keep('_show_trash');
		// 
		$this->store_to = "tree_".$config->module_name;
		$this->_href_template = $this->rh->path_rel."do/".$config->module_name."?".$this->rh->state->State();
        
        //nop: dirty hack
        if ($this->config->open_id > 0)
        {
           setcookie($this->config->open_id, "1");
        }
        
        $this->xml_encoding = 'windows-1251';
	}

	//  TreeSimple::Load().
	//      ?
	function Load(){
		//load data
		if( !$this->loaded )
        {
			//  
			$cache =& $this->rh->cache;
			$object_class = 'trees';
			//  
			/*			if( !($this->ITEMS =& $cache->Restore( $object_class, $this->config->table_name.'_ITEMS' )) ||
			!($this->CHILDREN =& $cache->Restore( $object_class, $this->config->table_name.'_CHILDREN' )) ){*/
			//   ,    - 
			//,  
			DBDataEditTree::Load( $this->rh->state->Get('_show_trash') ? '' : '_state<>2' );
			//  
			//				$cache->Store( $object_class, $this->config->table_name.'_ITEMS', 0, $this->ITEMS );
			//				$cache->Store( $object_class, $this->config->table_name.'_CHILDREN', 0, $this->CHILDREN );
			//			}
			$this->loaded = true;
		}
	}

	function Handle()
    {
		$this->Load();
		$rh =& $this->rh;
		$tpl =& $rh->tpl;
		//  -
		$action = $rh->GetVar('action');

		switch($action){

			case 'update':
				//    
				$rh->HeadersNoCache();

				if( $new_id = $this->UpdateTreeStruct() )
				//  
				$tpl->Assign('_new_id',$new_id);
				$tpl->Assign('_new_action',$this->_href_template.$this->id_get_var."=".$new_id);
				$tpl->Parse( $this->template_response, $this->store_to, true );
				break;

			case 'xml':
				// XML  xloadtree  
				//    ,   -   .
				/*if( $rh->GetVar('logs')!='show' ){
					$rh->UseClass("DebugDummy");
					$rh->debug =& new DebugDummy();
				}*/
				// ,   XML
				$rh->HeadersNoCache();
				header("Content-type: text/xml; charset=".$this->xml_encoding);
				echo $this->ToXML();
				die();
				//
				$rh->End();
				break;

			default:
			
				$tpl =& $this->rh->tpl;
    
			    $template_ban = $this->template_control.':buttons_ban';
			    //������
			    $tpl->Assign('_id',0);
			    $tpl->Assign('_array',implode(',',array(1,1,1)));
			    $tpl->Parse( $template_ban, 'buttons_ban', true );
			
				//  
				$this->_Handle();
				//     
				//render trash switcher
				$show_trash = $this->rh->state->Get('_show_trash');
				$tpl->Assign( '_href', $this->_href_template.'&_show_trash='.(!$show_trash) );
				$tpl->Parse( $show_trash ? $this->template_trash_hide : $this->template_trash_show, '__trash_switch' );
				//   
				$this->rh->logs->ParseLink( $this->config->module_name, 0, '__logs' );
				// 
				$_href = str_replace('&amp;','&',$this->_href_template);
				$_config_name = $this->config->PASSED[ 1 ];

                $tpl->Assign('root_id',  "'".(string)$this->getRootId()."'");

				$tpl->Assign( '_url_connect', $_href.'mode='.$_config_name.'&action=update&_show_trash='.$show_trash.'&' );
                
				$tpl->Assign( '_url_xml', $_href.'mode='.$_config_name.'&action=xml&_show_trash='.$show_trash.'&'.$this->id_get_var.'='.$this->id.'&' );

				$tpl->Assign( '_behavior', $this->tree_behavior );
				$tpl->Assign( '_cur_id', $this->id );
				$tpl->Assign( '_level_limit', $this->config->level_limit  ? $this->config->level_limit : 100 );
				$tpl->Parse( $this->template_head, 'html_head', true );
				$tpl->Parse( $this->template_control, '__tree' );
                
				$tpl->Parse( $this->template, $this->store_to, true );
				break;
		}
	}

	function _Handle(){}

	function AddNew( $FIELDS=array() ){
		return DBDataEdit::AddNew($FIELDS);
	}

	function UpdateTreeStruct()
	{
		
		$rh =& $this->rh;
		$db =& $rh->db;

		//   ,      
		$this->loaded = false;
		$this->rh->cache->Clear( 'trees', $this->config->table_name.'_ITEMS' );
		$this->rh->cache->Clear( 'trees', $this->config->table_name.'_CHILDREN' );
		//  
		$mode = $this->rh->GetVar('mode');
		$_href = $this->rh->url.'do/'.$this->config->module_name.( $mode ? '/'.$mode : '' ).'?'.$this->state->State();
		//  
		if( $rh->GetVar('add') ){
			//  
			//$rh->GetVar('parent','integer');
			if( $brother_id = $rh->GetVar('brother','integer') )
			{
				$rs = $db->QueryOne("SELECT _parent, _order FROM ".$this->config->table_name." WHERE id='$brother_id'");
				$parent_id = $rs["_parent"];
				$add_brother_mode = true;
				
			}
			else
			{
				$parent_id = $rh->GetVar('parent','integer');
			}
			//
			$new_id = $this->AddNew(array('_parent'=>$parent_id,));
			//  
			$this->rh->logs->Put( ' : ', 0, $this->config->module_title, $this->prefix.$this->SELECT_FIELDS[1].$this->suffix.$this->new_suffix, $_href );

			if( $add_brother_mode && $new_id ){
										
				$db->execute("
					UPDATE ". $this->config->table_name ."
					SET _order = _order + 1
					WHERE _order > " . $rs['_order'] . " AND _parent = '" . $rs['_parent'] . "'
				");

				$db->execute("
					UPDATE ". $this->config->table_name ."
					SET _order = " . ($rs['_order'] + 1) . "
					WHERE id = " . $new_id  . "
				");
				
			}
			elseif($new_id)
			{
				$rs = $db->execute("
					SELECT MAX(_order) AS _max
					FROM ".$this->config->table_name." 
					WHERE _parent='$parent_id'
					LIMIT 1
				");
				
				$db->execute("
					UPDATE ". $this->config->table_name ."
					SET _order = " . ($rs['_max'] + 1) . "
					WHERE id = " . $new_id  . "
				");
			}

			$this->Load();
			$this->Restore();
			$this->_KillOutsiders();
			
			return $new_id ? $new_id : true;
		}
		elseif($rh->GetVar('move'))
		{
			$id = $rh->GetVar('id','integer');
			if($id)
			{
				if( $brother_id = $rh->GetVar('brother','integer') )
				{
					$rs = $db->QueryOne("SELECT _parent, _order FROM ".$this->config->table_name." WHERE id='$brother_id'");
					$parent_id = $rs["_parent"];
															
					$db->execute("
						UPDATE ".$this->config->table_name." 
						SET _parent = '".$parent_id."' 
						WHERE id='".$id."'
					");
										
					$db->execute("
						UPDATE ". $this->config->table_name ."
						SET _order = _order + 1
						WHERE _order > " . $rs['_order'] . " AND _parent = '" . $rs['_parent'] . "'
					");

					$db->execute("
						UPDATE ". $this->config->table_name ."
						SET _order = " . ($rs['_order'] + 1) . "
						WHERE id = " . $id  . "
					");
									
				}
				else
				{
					$parent_id = $rh->GetVar('parent','integer');
					
					$rs = $db->execute("
						SELECT MAX(_order) AS _max
						FROM ".$this->config->table_name." 
						WHERE _parent='$parent_id'
						LIMIT 1
					");
					
					$db->execute("UPDATE ".$this->config->table_name." SET _parent = '".$parent_id."', _order = '".($rs['_max'] + 1)."' WHERE id='".$id."'");
				}
				
				$this->Load();
				$this->Restore();
				$this->_KillOutsiders();
				
				return true;
			}
			return false;
		}
		else
		{
			return false;
		}

		return false;
	}

    function getRootId()
    {
    		$rs = $this->rh->db->queryOne("
						SELECT id
						FROM ".$this->config->table_name."
						ORDER BY _level ASC, _order ASC
					");
			$id = $rs['id'];
			
        return $this->rh->GetVar("display_root","integer") ? $this->rh->GetVar("display_root","integer") : ($this->config->old_style ? 0 : $id)  ;
    }

	function ToXML(){  //$iconv=true
		//start XML
		$str = "<?xml version=\"1.0\" encoding=\"".$this->xml_encoding."\" ?>\n\n";
		//$str .= "<tree>\n";
		$str .= $this->xmlOpenTag('tree');

		//  ?
		$root_id = $this->getRootId();
		$root = $this->ITEMS[$root_id];

        if (!$this->config->old_style)
        {
            $node = (object)$root;
            //$str .= str_repeat(" ",$node->_level)."<tree text=\"".($this->_getTitle($node))."\" ".$this->_getAction($node->id, count($this->CHILDREN[$node->id]), true)." db_id=\"".$node->id."\" db_selected=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" >\n";
				$xml_attrs = array_merge( 
					array(
						'text',				($this->_getTitle($node)),
						'db_id',				$node->id,
						'db_selected',		( $node->id==$this->id ? 1 : '' ),
						'db_state',			$node->_state,
					),
					$this->_getAction($node->id, count($this->CHILDREN[$node->id]), true)
				);
				$str .= str_repeat(" ",$node->_level). $this->xmlOpenTag('tree', $xml_attrs)."\n";
		}
		//   
		$current = (object)$this->ITEMS[ $this->rh->GetVar("id","integer") ];
		$c_parent = (object)$this->ITEMS[ $current->_parent ];

		/* deep search */
		$stack = array();
		$cparent = $root_id;
		$level = array();
		//put root
		$arr =& $this->CHILDREN[$root_id];

		for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];

		//main loop
		while(count($stack))
        {
			$node = (object)$this->ITEMS[array_pop($stack)];
			$level[ $node->id ] = $level[ $node->_parent] + 1;

			//   ?
			//    ?
			if( $node->_left<=$c_parent->_left && $node->_right>=$c_parent->_right )
			$display_children = true;
			else
			//    
			$display_children = !($this->config->display_limit>0 && $level[ $node->id ] >= $this->config->display_limit);

			// -   xloadtree?
			$_is_folder = count($this->CHILDREN[$node->id]);
			$is_folder = $display_children && $_is_folder;

			//close subtrees
			if($node->_parent!=$cparent){
				for($i=0;$i<( $this->ITEMS[$cparent]['_level'] - $this->ITEMS[$node->_parent]['_level'] );$i++) $str .= "</tree>\n";
				$cparent = $node->_parent;
			}
			//write node
			//action or src?
			$action_src = $this->_getAction($node->id, $_is_folder, $display_childen);

			//$str .= str_repeat(" ",$node->_level)."<tree text=\"".($this->_getTitle($node))."\" ".$action_src." db_id=\"".$node->id."\" db_selected=\"".( $node->id==$this->id ? "1" : "" )."\" db_state=\"".$node->_state."\" ".(($is_folder)?">":"/>")."\n";
			$xml_attrs = array_merge(
				array(
					'text', ($this->_getTitle($node)),
					'db_id', $node->id,
					'db_selected', ( $node->id==$this->id ? "1" : "" ),
					'db_state', $node->_state,
				),
				$action_src
			);
			$str .= str_repeat(" ",$node->_level)
				.(
					$is_folder 
					? 
						$this->xmlOpenTag('tree', $xml_attrs) 
					:  $this->xmlTag('tree', $xml_attrs)
				)
				."\n";

			//put children
			if($is_folder)
            {
				$arr = $this->CHILDREN[$node->id];
				for($i=count($arr)-1;$i>=0;$i--) $stack[] = $arr[$i];
				$cparent = $node->id;
			}
		}
		for( $i=(integer)$root["_level"] ; $i<$this->ITEMS[$cparent]['_level']; $i++ ) $str .= "</tree>\n";

		//end XML
        if (!$this->config->old_style)
            //$str .= "</tree>\n";
            $str .= $this->xmlCloseTag('tree')."\n";
		$str .= $this->xmlCloseTag('tree')."\n";
		//mail ("nop@jetstyle.ru", "debug tree", $str);
		return $str;
	}
    
	function xmlQuote($str)
	{
		return htmlspecialchars($str, ENT_COMPAT, $this->xml_encoding);
	}

	function xmlQuoteAttr($str)
	{
		return htmlspecialchars($str, ENT_COMPAT, $this->xml_encoding);
	}

	function xmlTag($name, $args=NULL)
	{
		return '<'.$name.($args ? $this->xmlAttrs($args) : '').'/>';
	}

	function xmlOpenTag($name, $args=NULL)
	{
		return '<'.$name.($args ? $this->xmlAttrs($args) : '').'>';
	}

	function xmlCloseTag($name)
	{
		return '</'.$name.'>';
	}

	/**
	 * �� ������ $args = 
	 *	  array('name1', 'value1', 'name2', 'value2', ...);
	 * � ����� � ������: 
	 *	  array(array('name1', 'value1'), 'name2', 'value2', ...);
	 *
	 * ��������� ��������� ���� 
	 *	  name1="value1' name2="value2" ...
	 *
	 */
	function xmlAttrs($args)
	{
		$result = '';
		if (is_array($args))
		{
			reset($args);
			while (list($key, $name) = each($args) )
			{
				if ($name)
				{
					if (is_array($name))  // ��� ���� $name, $value
						list($name, $value) = $name;
					else // ��� $name, $value -- ��������� �� ���
					{
						$next = each($args); 
						if ($next) 
						{
							list($key1, $value) = $next;
						}
						// ������ ���������� -- ��� ��� � �������� ����
						else
						{
							// FIXME: ���-�� ��������� �� ������
							die('xmlAttrs');
						}
					}
					$result .= ' ' .$this->xmlAttr($name, $value);
				}
			}
		}
		return $result;
	}

	function xmlAttr($name, $value)
	{
		return $this->xmlQuoteAttr($name) .'="'.$this->xmlQuoteAttr($value).'"';
	}

	/* lucky: refactored
    function _getAction($id)
    {
        $action_src = "action=\"".$this->_href_template.$this->id_get_var."=".$id."\"";   
       	if( $_is_folder && !$display_children )
		    $action_src .= " src=\"".$this->_href_template."mode=tree&amp;action=xml&amp;display_root=".$node->id."\"";
        return $action_src;
    }
    
    function _getTitle(&$node)
    {
    	$_title = $node->title_short ? $node->title_short : $node->title;
    	$_title = $_title ? $_title : 'node_'.$node->id;
    	if ($node->_state > 0)
    	{
            $_title = $_title  .' [������]';
    	}
    	
     	$_title = preg_replace( "/<.*?>/is", '', $_title);
		$_title = str_replace('"','\'',$_title);   
		
        return $_title;
    }
	 */

	function _getAction($id)
	{
		$actions = array(
			array('action', $this->_href_template.$this->id_get_var."=".$id),
		);

	// !FIXME: what's $_is_folder && $display_children here?
		if( $_is_folder && !$display_children )
			$actions[] = array(
				'src', $this->_href_template."mode=tree&action=xml&display_root=".$node->id
			);
	  return $actions;
	}

	function _getTitle(&$node)
	 {
    	$_title = $node->title_short ? $node->title_short : $node->title;
    	$_title = $_title ? $_title : 'node_'.$node->id;

    	if ($node->_state == 1)
    	{
            $_title = $_title  .' [�����]';
    	}
	elseif ($node->_state > 1)
    	{
            $_title = $_title  .' [������]';
    	}
    	
     	$_title = preg_replace( "/<.*?>/is", '', $_title);
		
	   return $_title;
	}

    
	function _KillOutsiders(){
		//  ,    
		$S[] = 0;
		while(count($S)){
			$id = array_pop($S);
			if(is_array($this->CHILDREN[$id]))
			$S = array_merge($S,$this->CHILDREN[$id]);
			$IDS[] = $id;
		}
		//    
		$where = "_state<2 AND id<>'".implode("' AND id<>'",$IDS)."'";
		$rh =& $this->rh;
		$db =& $rh->db;
		//    $this->rh->db->execute("UPDATE ".$this->table_name." SET _state=2 WHERE id<>'".implode("' AND id<>'",$IDS)."'");
		//    mail('zharinov@jetstyle.ru','tree sql',"UPDATE ".$this->table_name." SET _state=2 WHERE id<>'".implode("' AND id<>'",$IDS)."'");
		//    ,   
		$this->rh->db->execute("UPDATE ".$this->table_name." SET _parent=0,_left=-1,_right=-1 WHERE ".$where);
		//   
		$TO_KILL = $db->query("SELECT id,title FROM ".$this->table_name." WHERE ".$where);
		
		foreach($TO_KILL as $r){
			$rh->logs->Put( ' :  ', $r['id'], $this->config->module_title, $r['title'], $this->_redirect.'&_show_trash=1' );
			$rh->trash->Delete( $this->config->table_name, $r['id'], $this->config->module_title, $r['title'], $rh->path_rel.'?'.str_replace('&amp;','&',$this->state->StateAll()).'&id='.$r['id'] );
		}
	}


	function Restore( $parent_id=0, $left=0, $order = 0 ) {

		//shortcuts
		$node =& $this->ITEMS[ $parent_id ];

		//_level
		if($node['id'])
		$node['_level'] = $this->ITEMS[ $node['_parent'] ]['_level'] + 1;

		/* Taken from http://www.sitepoint.com/article/1105/3 */

		// the right value of this node is the left value + 1
		$right = $left + 1;

		// get all children of this node
		$A =& $this->CHILDREN[$parent_id];

		$n = count($A);
		for($i=0;$i<$n;$i++){
			// recursive execution of this function for each
			// child of this node
			// $right is the current right value, which is
			// incremented by the rebuild_tree function
			$right = $this->Restore( $A[$i], $right, $i);
		}

		// we've got the left value, and now that we've processed
		// the children of this node we also know the right value
		$node['_left'] = $left;
		$node['_right'] = $right;

		//echo $node['_level'].' '.$order.' '.$node['title'].'<br />';

		//store in DB
		//    print("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."' WHERE id='".$node['id']."'<br>\n");
		$this->rh->db->execute("UPDATE ".$this->table_name." SET _level='".$node['_level']."', _left='".$node['_left']."', _right='".$node['_right']."', _order = '".$order."' WHERE id='".$node['id']."'");

		// return the right value of this node + 1
		return $right + 1;
	}


}

?>