<?php
Finder::useClass("controllers/Controller");
class LoginController extends Controller
{
	protected $plugins = array(
/*
		array('MenuPlugin', array(
			'__aspect' => 'MainMenu',
			'store_to' => 'menu',
			'level' => 2,
			'depth' => 1,
		)),
*/
	);

	protected $params_map = array(
		array('default', array(NULL)),
	);

	function handle_default($config)
	{
		$prp = &Locator::get('principal');
		
		if ($_POST['submit'])
		{
			if ($prp->login($_POST['login'], $_POST['password'], $_POST['permanent']) === PrincipalInterface::AUTH)
			{
				$redirectTo = RequestInfo::get('retpath') ?
						  RequestInfo::get('retpath') :
						  RequestInfo::$baseUrl;
				Controller::redirect($redirectTo);
			}
		}
		else
		{
			if ($prp->security('noguests'))
			{
				if (RequestInfo::get('logout'))
				{
					$redirectTo = RequestInfo::get('retpath') ?
								  RequestInfo::get('retpath') :
								  RequestInfo::$baseUrl.'login';
					$prp->logout();
				}
				else
				{
					$redirectTo = RequestInfo::$baseUrl;
				}
				Controller::redirect($redirectTo);
			}
		}
		
		$this->siteMap = 'login';
	}
}
?>
