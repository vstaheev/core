<?php
$this->useClass('FormFiles');

class FormIframe extends FormFiles
{

	//  var $template_item = 'faq_form.html';

	public function handle()
	{
		$tpl = & $this->rh->tpl;

		//load item
		$this->load();

		//��������� iframe � ��������������� ��������
		if ($this->item[$this->idField])
		{
			if (is_array($this->config->href_for_iframe))
			{
				foreach ($this->config->href_for_iframe as $k => $href_for_iframe)
				{
					$tpl->set("_iframe_number", $k);
					$this->_parseIframe($href_for_iframe);
				}
			} 
			else
			{
				$this->_parseIframe($this->config->href_for_iframe);
			}
		} 
		else
		{
			$tpl->set('_iframe', '<br />');
		}

		//�� �����
		parent :: handle();
	}

	protected function _parseIframe($href_for_iframe)
	{
		if(!$href_for_iframe)
		{
			return;
		}
		$tpl = & $this->rh->tpl;
		$wid = $this->item[$this->idField];

		$vis = isset ($_COOKIE["cf" . $wid]) ? $_COOKIE["cf" . $wid] : !$this->config->closed_iframe;

		$tpl->set('_id', $wid);
		$tpl->set('_class_name_1', ($vis == "true" || $vis === true) ? "visible" : "invisible");
		$tpl->set('_class_name_2', ($vis == "false" || $vis === false) ? "visible" : "invisible");

		$tpl->set('prefix', $this->prefix);
		$tpl->set('__url', $this->rh->base_url . $href_for_iframe . $this->id . '&hide_toolbar=1');
		$tpl->parse('iframe.html', '_iframe', 1);
	}
}
?>