<?php
/*
 � ������� ������ ���� ����:

 inserted datetime NOT NULL default '0000-00-00 00:00:00',
 year int(11) NOT NULL default '0',
 month int(11) NOT NULL default '0',

 ���� ���������� ����������������.
 ���������� ����� �� ������� ����������� �����.
 */

Finder::useClass('ListSimple');

class ListNews extends ListSimple
{
	protected $template = 'list_news.html';
	protected $template_calendar = 'list_news.html:calendar';

	protected $pages; //������ ������������ ����������

	protected $year = 0;
	protected $month = 0;

	public function __construct( &$config )
	{
		//������������� ������
		if(!$config['order_by'])
		{
			$config['order_by'] = 'inserted DESC';
		}

		//�� �����
		parent::__construct( $config );

        //var_dump($this->config['table']);die;

		$this->prefix = $config['module_name'].'_tree_';
		$this->defineDate();
	}

	public function handle()
	{
		$db = &$this->db;

		//assign some
		$this->tpl->set('prefix', $this->prefix);

		//�������� ������ �� �����
		//������
		//������ �������� ������������� �� �������
		$M = array();
		$rs = $db->execute("
	    	SELECT DISTINCT month
	    	FROM ??".$this->config['table']."
	    	WHERE year='".$this->year."' AND _state <= 1 ".($this->config['where'] ? " AND ".$this->config['where'] : "" )
		);
		while($row = $db->getRow())
		{
			$M[ $row['month'] ] = true;
		}

		$MONTHES_NOMINATIVE = array("","������","�������","����","������","���","����","����","������","��������","�������","������","�������");

		for($i=1;$i<=12;$i++)
		{
			$month_options .= "<option value='$i' ".( $i==$this->month ? "selected='true'" : '' ).' '.( $M[$i] ? "style='background-color:#eeeeee'" : '' ).">".$MONTHES_NOMINATIVE[$i]."</option>";
		}

		$this->tpl->set( '_month_options', $month_options );

		//����
		$rs = $db->execute("
	    	SELECT DISTINCT year
	    	FROM ??".$this->config['table']."
	    	WHERE _state <= 1 ".($this->config['where'] ? " AND ".$this->config['where'] : "" ) . "
	    	ORDER BY year ASC
	    ");

		$year_options = '';
		if ($rs)
		{
			while ($r = $db->getRow($rs))
			{
				$year_options .= "<option value='".$r['year']."' ".( $r['year'] == $this->year ? "selected='true'" : '' ).">".$r['year']."</option>";
			}
		}

		$this->tpl->set( '_year_options', $year_options );
		$this->tpl->parse( $this->template_calendar, '__calendar' );

		//�� �����
		parent::handle();
	}

	public function load()
	{
		parent::load("year='".$this->year."' AND month='".$this->month."'");
	}

	protected function defineDate()
	{
		$db = &$this->db;

		$this->year = intval(RequestInfo::get('year'));
		$this->month = intval(RequestInfo::get('month'));

		if (!$this->year || !$this->month)
		{
			$rs = $db->queryOne("SELECT id, year, month FROM ??".$this->config['table']." WHERE _state<=1 ".($this->config['where'] ? " AND ".$this->config['where'] : "" )." ORDER BY inserted DESC");
			if($rs['id'])
			{
				$this->year = $rs['year'];
				$this->month = $rs['month'];
			}
			else
			{
				$this->year = date('Y');
				$this->month = date('m');
			}
		}

		RequestInfo::set('year', $this->year);
		RequestInfo::set('month', $this->month);
	}
}

?>
