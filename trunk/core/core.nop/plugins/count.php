<?php
    /*

    COUNT magic     {{!count 10 pages}} {{!count total items}}
    -----------

    ������-�����, ������� ����� "10 �������" � ������ ������, 
    ��������� ��������� messageset

    ==================================================== v.0 (kuso@npj)

    $params:   "0"         => ����� ��� ��� ���������� $tpl->Get()
               "1"         => ������������� (optional, default="items"

    */

    $count = $params[0];
    if (!is_numeric($count)) $count = $tpl->Get($count);

    $item_name = isset($params[1]) ? $params[1] : false;
    if (!$item_name) $item_name = "items";

    echo $count."&nbsp;".$tpl->msg->NumberString( $count, $item_name );

?>