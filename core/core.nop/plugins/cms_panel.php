<?php
//@cms_panel.html
	if($rh->principal->isAuth()) {
		$cookieOce = $_COOKIE['oce'];
		$getOce = $rh->ri->get('oce');
		if ($getOce) {
        	$oce = $getOce;
		} else if ($cookieOce) {
		} else {
		}
		$tpl->set('oce_on', $oce=='on');
		$tpl->set('oce_off_href',$rh->ri->hrefPlus('',array('oce' => 'off')));
		$tpl->set('oce_on_href',$rh->ri->hrefPlus('',array('oce' => 'on')));
		$tpl->set('cur_url',$rh->ri->hrefPlus('',array('oce' => '')));
        return $tpl->parse('cms_panel.html');
	} else return '';
?>