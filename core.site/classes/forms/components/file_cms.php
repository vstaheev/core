<?php
/*

  ����-���������
  * see http://in.jetstyle.ru/rocket/rocketforms

  FormComponent_file( &$config )
      - $field -- $field->config instance-a ����

  -------------------

  * model       : ��������� �� ����: ���������� ����� � �������� ������� � ������ ��� ����� � ��
  * interface   : ����� ���� �������� �����
  * validator   : ���������� �� ������ �����? ������ �����?

  -------------------

  ����� � �������

  * file_size = "8" -- max size in Kilobytes
  * file_ext  = array( "gif", "jpg", etc. )
  * file_dir  = -- ����, ���� ������ �����.
  * file_random_name = false (true)

  -------------------

  // ������. �������� � ������� � ����������
  * Model_DbInsert( &$fields, &$values )
  * Model_DbUpdate( $data_id, &$fields, &$values )

  // ���������
  * Validate()

  // ��������� (������� � ��������� ������)
  * Interface_Parse()
  * Interface_PostToArray( $post_data )

================================================================== v.0 (kuso@npj)
*/
Finder::UseClass( "forms/components/model_plain" );

class FormComponent_file_cms extends FormComponent_model_plain
{
    // MODEL ==============================================================================
    function Model_DbInsert( &$fields, &$values )
    {
        //if ($this->file_uploaded)
        //{
        //  $fields[] = $this->field->name;
        //  $values[] = $this->model_data;
        //}
    }

    function Model_DbUpdate( $data_id, &$fields, &$values )
    {
        //return $this->Model_DbInsert( $fields, $values );
    }

    function Model_DbAfterInsert($data_id)
    {
        if ($this->field->config['variants'] )
        {
            foreach ($this->field->config['variants'] as $key => $variant)
            {
                $this->field->config['variants'][$key]['file_name'] = str_replace('*', $data_id, $this->field->config['variants'][$key]['file_name']);
            }
        }
        $this->_UploadFile($data_id);
    }

    function Model_DbAfterUpdate($data_id)
    {
        $this->Model_DbAfterInsert($data_id);
    }

   // VALIDATOR ==============================================================================
    function Validate()
    {
        parent::Validate();

        if (!$this->valid) return $this->valid; // ==== strip one

        if ($this->field->config['validator_params']['not_empty'] && !$_FILES['_'.$this->field->name]['name'])
            $this->_Invalidate( "file_empty", "���� �� ������" );

        $this->file_size = $_FILES[ '_'.$this->field->name ]['size'];
        $this->file_ext = substr($_FILES[ '_'.$this->field->name ]['name'], strrpos($_FILES[ '_'.$this->field->name ]['name'], '.')+1);

        if ($this->file_size)
            if (isset( $this->field->config["file_size"]))
                if ($this->file_size > $this->field->config["file_size"]*1024)
                    $this->_Invalidate( "file_size", "������� ������� ����" );

        if ($this->file_ext)
            if (isset( $this->field->config["file_ext"]))
                if (!in_array($this->file_ext,$this->field->config["file_ext"]))
                    $this->_Invalidate( "file_ext", "������������ ��� �����" );

        if ($this->file_size)
            if (@$this->field->config["validator_func"]) {
                if ($result = call_user_func( $this->field->config["validator_func"],
                                              $this->field->model->Model_GetDataValue(),
                                              $this->field->config ))
            $this->_Invalidate( "func", $result );
        }

        if ((isset( $this->field->config["min_width"]) || isset( $this->field->config["min_height"])) && $_FILES['_'.$this->field->name]['tmp_name'])
        {
            $imageSize = getimagesize($_FILES['_'.$this->field->name]['tmp_name']);
            if ($imageSize[0] < $this->field->config["min_width"] || $imageSize[1] < $this->field->config["min_height"])
                $this->_Invalidate( "file_size", "������� ��������� ��������" );
        }

        return $this->valid;
   }
   // quick pre-validation
   function _CheckExtSize( $ext, $size )
   {
     if (isset( $this->field->config["file_size"]))
       if ($size > $this->field->config["file_size"]*1024)
         return false;
     if (isset( $this->field->config["file_ext"]))
       if (!in_array($ext,$this->field->config["file_ext"]))
         return false;
     return true;
   }

    // INTERFACE ==============================================================================
    // ������� ����� ����������
    function Interface_Parse()
    {
        $tpl = Locator::get('tpl');
        parent::Interface_Parse();

        $file = $this->field->model->Model_GetDataValue();

        if (!$file || !$file['name_full'])
        {
            $tpl->Set("interface_file", false);
        }
        else
        {
            $tpl->Set("interface_file", $file );
        }

        $ret = $tpl->Parse( $this->field->form->config["template_prefix_interface"].
                $this->field->config["interface_tpl"] );

        return $ret;
    }

    function Model_GetDataValue()
    {
        $result = null;
        $id = $this->field->form->data_id;

        $result = FileManager::getFile($this->field->config["config_key"], $id);

        if ($this->field->config['variants'])
        {
            foreach ($this->field->config['variants'] AS $variant_name=>$variant)
            {
                
                if ($variant['show'] || count($this->field->config['variants'])==1 )
                {
                    
                    $ret = FileManager::getFile($this->field->config["config_key"]."/".$variant_name, $id);
                    //var_dump($this->field->config["config_key"]."/".$variant_name, $id, $ret);
                    $ret['original'] = $result;
                    return $ret;
                }
            }
        }

        return $result;
    }

    // �������������� �� ����� � ������ ��� �������� �������
    function Interface_PostToArray( $post_data )
    {
        if ($value === false) return array(); // no data here
        $a = array(
            $this->field->name => $value,
        );
        return $a;
    }

   // ---------------------------------------------------------------------------
   // UPLOAD specific handlers
   function _GetSize( $file_name )
   {
     $full_name = $this->field->config["file_dir"].$file_name;
     if (file_exists($full_name))
       return filesize($full_name);
     else return false;
   }

    function _UploadFile($data_id)
    {
        //$file = FileManager::getFile($this->configKey.':'.$conf['key'], $objId, $isId);
        // News/items:picture , 43, false
        $upload = Locator::get('upload');

        $file = FileManager::getFile($this->field->config['config_key'], $data_id);

        if ($this->field->config['variants'])
        {
            foreach ($this->field->config['variants'] as $variant)
            {
                if ($_POST['_'.$this->field->name.'_del'])
                {

                    $file = FileManager::getFile($this->field->config['config_key'], $data_id);
                    $file->deleteLink();
                }
                //$result = $upload->uploadFile('_'.$this->field->name, $this->field->config['file_dir'].'/'.$variant['file_name'], false, $variant['params']);

                //$file = FileManager::getFile($this->field->config['config_key'], $data_id);
            }
            //$file->upload($_FILES['_'.$this->field->name]);
        }

        if ($_FILES['_'.$this->field->name][tmp_name])
        {
            //var_dumP($this->field->config['config_key'], $data_id);die();

            $file->upload($_FILES['_'.$this->field->name]);
        }

        return $result;
    }


}

?>

