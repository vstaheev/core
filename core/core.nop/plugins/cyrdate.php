<?php

/**
 * ����������� ���� ����� CyrDate
 * 
 * ���������
 *	 data		- ������ ����
 *	 iformat - ������ ������ ����
 *	 oformat - ������ ���� �� ������
 */

$date = $params['date'];
$ifmt = $params['iformat'];
$ofmt = $params['oformat'];

$rh->useClass('CyrDate');
$d = CyrDate::newFromStr($ifmt, $date);
if (isset($d))
	echo $d->format($ofmt);
else
	echo $date;

?>
