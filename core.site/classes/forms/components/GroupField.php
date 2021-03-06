<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  ������������ ������ �����

  FormComponent_group( &$config )
      - $field -- $field->config instance-a ����

  -------------------

  * model       : ������� ��, ��� �����, ������ ����� � ����
  * validator   : ������� ��, ��� �����, ������ ����� � ����
  * wrapper     : - ���
  * view        : ����������� ��� ��������� ���� � readonly � �������� �� ������
  * interface   : �������� ������ ����� ������
                  ������� ��, ��� �����, ������ ����� � ����

  -------------------

  // ���������� �����:

  1) � ������������:      $config["fields"] = array{ name=>field_config/field_object, ... }
  2) ����� ������������:  $group->model->Model_AddField( "title", array( ...<field_config>... ) );

  * &Model_AddField( $field_name, $config ) -- ������ ���� � ��������� ���
  * &_AddField( &$field_object ) -- ��� ����������� ������������� � AddField

  // ��������� � ����������
  * Event_Register() -- ����������� � �����. ����������� ����� ����� ������ ��� �����

================================================================== v.0 (nikolay@jetstyle)
*/
Finder::useClass('FormField');

class GroupField extends FormField
{
    var $childs = array();

    // ���������� �����
    function &Model_AddField( $field_name, $config )
    {
        if ($config['extends_from'])
        {
            $className = $config['extends_from'];
            if (Finder::findScript('classes/forms/components', $className))
            {
                Finder::useClass('forms/components/'.$className);
                $className = $className;
            }
            else
            {
                $className = 'FormField';
            }
        }
        else
        {
            $className = 'FormField';
        }
        $f = new $className( $this, $field_name, $config );    
        return $this->_AddField($f);
    }

    function &_AddField( &$field_object )
    {
        $this->childs[] = $field_object;
        $field_object->_LinkToForm( $this->form );
        return $field_object;
    }

    public function &getFieldByName($name)
    {
        $resultField = null;
        foreach ($this->childs AS $k => $field)
        {
            if ($field->name == $name)
            {
                $resultField = $this->childs[$k];
                break;
            }
            elseif ($resultField = &$field->getFieldByName($name))
            {
                break;
            }
        }
        return $resultField;
    }

    // ����������� � �����
    function Event_Register()
    {
        // NB: ���� ������������, ������ ���� �������� ���
        if ($this->name)
        {
            parent::Event_Register();
        }
        // ������������ ���� ����� ������
        foreach ($this->childs as $k=>$v)
        {
            $this->form->hash[ $v->name ] = &$this->childs[$k];
        }
    }

    // MODEL ==============================================================================
    // ����� �������� � "�������� ��-���������"
    function Model_SetDefault()
    {
        foreach ($this->childs as $k=>$v)
        {
            $this->childs[$k]->Model_SetDefault();    
        }
    }
    
    // ������� �������� � ���� "�����" ��� "�����"
    function Model_GetDataValue()
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->Model_GetDataValue();
    }
    
    // dbg purposes: dump
    function Model_Dump()
    {
        $dump = array();
        foreach ($this->childs as $k=>$v)
        $dump[] = $this->childs[$k]->Model_Dump();
        return implode( "; ", $dump );
    }
    
    // ---- ������ ----
    function Model_ToSession( &$session_storage )
    {
        foreach ($this->childs as $k=>$v)
        {
            $this->childs[$k]->ToSession( $session_storage );   
        }
    }
    
    function Model_FromSession( &$session_storage )
    {
        foreach ($this->childs as $k=>$v)
        {
            $this->childs[$k]->FromSession( $session_storage );    
        }
    }
    
    // ---- ������ � ���������� � ������� ----
    function Model_LoadFromArray( $a )
    {
        foreach ($this->childs as $k=>$v)
        {
            $this->childs[$k]->LoadFromArray( $a );    
        }
    }
    
    function Model_ToArray( &$a )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->Model_ToArray( $a );
    }
    
    // ---- ������ � �� ----
    function Model_DbLoad( $db_row )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbLoad( $db_row );
    }
    
    function Model_DbInsert( &$fields, &$values )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbInsert( $fields, $values );
    }
    
    function Model_DbUpdate( $data_id, &$fields, &$values )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbUpdate( $data_id, $fields, $values );
    }
    
    function Model_DbAfterInsert( $data_id )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbAfterInsert( $data_id );
    }
    
    function Model_DbAfterUpdate( $data_id )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbAfterUpdate( $data_id );
    }
    
    function Model_DbDelete( $data_id )
    {
        foreach ($this->childs as $k=>$v)
        $this->childs[$k]->DbDelete( $data_id );
    }


    // VALIDATOR ==============================================================================
    // ���������
    // ��� ������� ������ �������� ��� ����� ����� ����������
    function Validate()
    {
        $this->valid = true;

        foreach ($this->childs as $k=>$v)
        {
            $this->valid = $this->childs[$k]->Validate() && $this->valid; // ������� �����
        }

        if (!$this->valid)
        {
            $this->_Invalidate( "have_errors", "���� ����, ���������� ������ � ����������" );
        }

        return $this->valid;
    }

    // VIEW ==============================================================================
    // ������� readonly ��������
    function View_Parse()
    {
        return $this->_ParseChilds( "readonly" );
    }

    // INTERFACE ==============================================================================
    // ������� ����� ����������
    function Interface_Parse()
    {
        Locator::get('tpl')->set('group_title', $this->config['group_title']);
        Locator::get('tpl')->set('interface_tpl_params', $this->config['interface_tpl_params']);
        return $this->_ParseChilds();
    }
    
    // ��������������� ��������� ��� �������� �����/��������
    function _ParseChilds( $readonly=false )
    {
        $result = array();
        foreach($this->childs as $child)
        {
            $result[] = array(
                  "child" => $child->name,
                  "field" => $child->Parse( $readonly ) 
            );
        }
        
        $tpl = &Locator::get('tpl');
        $tpl->set( "parent", $this->field->name );
        $tpl->set( "fields", $result);
        
        return $tpl->parse($this->form->config["template_prefix_group"].$this->config["group_tpl"]);
    }
    
    // �������������� �� ����� � ������ ��� �������� �������
    function Interface_PostToArray( $post_data )
    {
        $result = array();
        foreach ($this->childs as $k=>$v)
        {
            $result = array_merge( $result, $this->childs[$k]->Interface_PostToArray( $post_data ));   
        }
        return $result;
    }

    // EOC{ FormComponent_group }
}  


?>