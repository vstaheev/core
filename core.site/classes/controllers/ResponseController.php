<?php
/**
 *  12.05.2009
 *  lunatic@jetstyle.ru
 */

Finder::useClass("controllers/Controller");
class ResponseController extends Controller {
    protected $params_map = array(
        array('404', array('404'=>'404')),
        array('403', array('403'=>'403')),
    );

    function handle_404($config) {
        Finder::useLib('http');
        Http::status(404);
    }

    function handle_403($config) {
        Finder::useLib('http');
        Http::status(403);

        $prp = Locator::get('principal', true);

        // not logged in
        if ($prp === null || !$prp->security('noguests')) {
            Locator::get('tpl')->set('login_form', true);

            $retPath .= $_SERVER['HTTPS'] ? 'https://' : 'http://';
            $retPath .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            $retPath = urlencode($retPath);

            Finder::useClass("forms/Form");

            $formConfig = array();
            $formConfig['action'] = RequestInfo::$baseUrl.Router::linkTo('Users::login').'?retpath='.$retPath;
            $form = new Form('login', $formConfig);

            Locator::get('tpl')->set('Form', $form->handle());
        }
    }
}
?>
