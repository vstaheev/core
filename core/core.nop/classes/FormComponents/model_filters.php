<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_model_filters( &$config )
      - $field -- $field->config instance-a ����  

  -------------------

  * model       : ��������� *_plain, ����� ������������ ������������� ����

  -------------------

  // ������. �������� � ������� � ����������

  * Model_ToArray( &$a ) 

  * Model_DbInsert( &$fields, &$values )
  * Model_DbUpdate( $data_id, &$fields, &$values )

================================================================== v.1 (kuso@npj)
*/
$this->UseClass( "FormComponents/model_plain" );

class FormComponent_model_filters extends FormComponent_model_plain
{
   // MODEL ==============================================================================
   // ---- ������ � ���������� � ������� ----
   function Model_ToArray( &$a )
   {
     $result = $this->_Filter();
     foreach($result as $k=>$v)
       $a[$k] = $v;
   }
   // ---- ������ � �� ----
   function Model_DbInsert( &$fields, &$values )
   {
     $result = $this->_Filter();
     foreach($result as $k=>$v)
     {
       $fields[] = $k;
       $values[] = $v;
     }
   }

   // private method "filtering"
   function _Filter()
   {                   
     $data = $this->model_data;
     $filtered = array();
     $params = array();
     if (!empty($this->field->config["model_filters"]) )
     {
	 foreach( $this->field->config["model_filters"] as $k=>$v )
	 {
	   // ������������. ����� ����� �� �� �����������, � �� ������ �������
	   if (isset($this->field->config["model_filters_from"]) &&
	       isset($this->field->config["model_filters_from"][$k]))
	     $params["_"] = $filtered[ $this->field->config["model_filters_from"][$k] ];
	   else
	     $params["_"] = $data;

	   $filtered[$k] = $data = $this->field->tpl->Action( $v, $params );
	 }
     }
     else
     {
	 $filtered = array( $data );
     }
     
     $result = array();
     FormComponent_model_plain::Model_ToArray( &$result );
     foreach( $this->field->config["model_filtered"] as $k=>$v )
     {
       $v = str_replace("*", $this->field->name, $v);
       $result[$v] = $filtered[$k];
     }

     return $result;
   }




// EOC{ FormComponent_model_filters }
}  
   

?>