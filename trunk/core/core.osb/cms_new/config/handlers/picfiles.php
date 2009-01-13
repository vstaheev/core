<?
	$rh->HeadersNoCache();
	
	$mode = $path->path_trail=='list' ? 1 : 0;
	if($mode==1)
		$suffix = '_lists';
	
	$rh->UseClass('Upload');
	$upload =& new Upload($rh,$rh->front_end->file_dir);
	
	$table_topics = $rh->project_name.'_picfiles'.$suffix.'_topics';
	$table_picts = $rh->project_name.'_picfiles'.$suffix;
	
	$rh->UseClass('DBDataView');
	
	//load topics, render topics select
	$topics = new DBDataView( $rh, $table_topics, array('id','title'), '_state=0', 'title ASC');
	$topics->Load();
	$nt = count($topics->ITEMS);
	for($i=0;$i<$nt;$i++)
		$topics_options .= "<option value='".$topics->ITEMS[$i]['id']."'>".$topics->ITEMS[$i]['title']."\n";
	
	//������ ��������, ������������ �� ID � topic_id
	$picts = new DBDataView( $rh, $table_picts, array('id','title','descr','topic_id'), 'topic_id>0 AND _state=0', 'topic_id ASC, title ASC');
	$picts->Load();
	$n = count($picts->ITEMS);
	for($i=0;$i<$n;$i++){
		$r = (object)$picts->ITEMS[$i];
		$_fname = 'picfile'.$suffix.'_'.$r->id;
		if( $file_small = $upload->GetFile($_fname) ){
			//��������� ������ ��� ��������
			$src_small = $rh->path_rel.'pic_file/picfiles'.( $mode ? '_lists' : '' ).'/'.$r->id;
			$BY_TOPICS[$r->topic_id][] = array( $r->id, htmlspecialchars($r->title) );
			$_array = "['".$src_small."','".str_replace("'","\'",$r->title)."','".$file_small->size."kb, ".$file_small->format."','".str_replace("'","\'",$r->descr)."']";
			$rv_str .= "arrrv[".$r->id."] = ".$_array.";\n";
			$BY_TOPICS_1[$r->topic_id][] = $_array;
		}
	}
	
	//�������� js-������ �� ��������
	for($i=0;$i<$nt;$i++){
		$r = (object)$topics->ITEMS[$i];
		$arrv = $arrt = "";
		$A =& $BY_TOPICS[ $r->id ];
		$n = count($A);
		for($j=0;$j<$n;$j++){
			$arrt .= ( $j ? ', ' : '' ).'"'.$A[$j][1].'"';
			$arrv .= ( $j ? ', ' : '' ).'"'.$A[$j][0].'"';
		}
		$titles_str .= "arrt[".$r->id."] = new Array(".$arrt.");\n";
		$values_str .= "arrv[".$r->id."] = new Array(".$arrv.");\n";
		$topics_rv_str .= "arrrvt[".$r->id."] = [\n   ".( is_array($BY_TOPICS_1[ $r->id ]) ? implode(",\n   ",$BY_TOPICS_1[ $r->id ]) : '')."\n];\n";
	}
	
	//��������� � ������
	$tpl->Assign( 'TOPICS', $topics_options );
	$tpl->Assign( 'TITLES', $titles_str );
	$tpl->Assign( 'VALUES', $values_str );
	$tpl->Assign( 'RETURN_VALUES', $rv_str );
	$tpl->Assign( 'TOPICS_RETURN_VALUES', $topics_rv_str );
	
	$template = 'htmlarea/insert_file.html';
	
	//�������� ���������� ������ ��
	$tpl->Parse( $template.($mode == 1 ? ':onOk_2' : ':onOk_1' ) , 'onOk' );
	
	$tpl->Assign( 'Page_TITLE', $mode == 1 ? '�������� ������ ������' : '�������� ����' );
	
	//���������� ������
	echo $tpl->parse($template);
	
?>