<?php
$rh->OCE = array(
		"texts"=>"do/Texts/form?id=::id::&",
		"news"=>"do/News/form?id=::id::&",
		"content"=>"do/Content/form?id=::id::&",
  );


	if($rh->principal->isAuth())
    {

  	//$tpl =& $rh->tpl;

  	$module = $params['module'];
  	$var = $params['var'];
  	$id = (integer)$params['id'];
    
		if( !isset($rh->OCE[$module]) )
			$rh->debug->Error("OCE: module not found, module=$module, id=$id, var=$var");
  	
  	if($var)
  		$id = (integer)$tpl->GetValue($var);
    
		if( !$id )
			$rh->debug->Error("OCE: id not found, module=$module, id=$id, var=$var");
  	
  	$tpl->set('_module',$module);		
  	$tpl->set('_id',$id);		
    //echo ('cms_url='.$rh->cms_url);
  	$tpl->set('_href', $rh->cms_url.str_replace('::id::',$id,$rh->OCE[$module]).'hide_toolbar=1&popup=1' );
  	$tpl->set('_width', $params['width'] ? $params['width'] : 300 );		
  	$tpl->set('_height', $params['height'] ? $params['height'] : 400 );		
  	$tpl->set('_title', $params['title'] ? $params['title'] : '�������������' );		
    
  	return $tpl->parse('oce.html');
    
	}else
		return '';

?>