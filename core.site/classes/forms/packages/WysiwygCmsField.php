<?php
  $config = array(
    "wrapper_tpl"   => "wrapper.html:CmsWYSIWYGStringWrapper",
    "wrapper_title" => "[textarea title]",
    
    "interface_tpl" => "string.html:WYSIWYG",
    "model"=> "model_filters",
    "model_filters"=>array(
          "a"=> "typografica",
          "b"=> "editor_objects"
          ),
    "model_filtered"=>array(
          "a"=> "*_pre", 
          "b"=>"*_pre"
          )
  );
?>
