<?php
	$this->class_name = 'FormSimple';
	$this->table_name = 'toolbar';
	$this->SELECT_FIELDS = array('id','title','href','_state','main');
	$this->RENDER = array( array('_state','checkbox'), array('main','checkbox') );
?>