<?php
/**
 * 404 Page
 *
 * @author lucky@npj
 */
Finder::useClass('controllers/BasicPage');
class PageNotFoundPage extends BasicPage
{
	protected $title = '404. �������� �� ������';

	function handle()
	{
		Finder::useLib('http');
		Http::status(404);
		$this->rh->site_map_path = '404';
		parent::handle();
	}

	function rend()
	{
		$this->rh->tpl->set('page', $this->rh->url);
		parent::rend();
	}

}
?>
