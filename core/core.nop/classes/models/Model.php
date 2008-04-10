<?php

/**
 * Класс Model -- базовый класс для моделей данных
 *
 *
 * Протокол модели
 *
 * // инициализация
 * $o  =& new Model($rh);
 * if ($o->inititalize())
 * {
 *		// теперь можем выполнять операции модели
 *		$o->some_action();
 * }
 * // очистка ресурсов
 * $o->finalize();
 *
 *
 * Почему так [все сложно]
 *
 * q: Инициализащию модели можно проводить в конструкторе. Тогда на одно действие 
 * будет меньше ($o->inititalize()).
 *
 * a: Во-первых, тот кто создает объект не всегда располагает полным набором 
 * параметров модели, требуемых в каждом конкретном случае. Например фабричные методы.
 * Во-вторых, в процессе инициализации может обнаружится ошибка (обычно -- нарушение 
 * инварианта модели). Конструктор не может сигнализировать об ошибке, т.к. не возвращает 
 * ничего осмысленного.
 * В-третьих не все созданные объекты реально используются. Откладывая инициализацию (обычно
 * что-то сложное, жрущее ресурсы и память) до последнего момента, вводится некая "ленивость"
 * вычислений.
 *
 *
 * Конфиги и параметры модели
 *
 * Возможно модели понадобится конфиг или установка дополнительных атрибутов-параметров
 * $o  =& new Model($rh);
 * $o->config = $model_config;
 * $o->limit = 111;
 * if ($o->inititalize())
 * {
 *		$o->some_action();
 *	}
 *
 *	q: В чем отличие атрибутов объекта от параметров конфигурации, которые 
 *	передаются через конфиг?
 * a: Атрибуты объекта принадлежат конкретному объекту, к тому же они могут 
 * изменяться в процессе вычислений. 
 * Конфиг это набор констант для семейства объектов.
 *
 * При инициализации объекта, параметры, указанные в конфиге, должны иметь
 * больший приоритет, нежели заданные атрибутами.
 *
 *
 * Observable
 *
 * Модель должна поддерживать какой-нибудь ассихронный протокол обмена информацией.
 * И быть в достаточной мере _общительной_.
 *
 * Когда структура данных модели усложняется это становится весьма полезным. 
 * Сложные "черные ящики", которые делают втихоря кучу работы -- это 
 * неконтролируемый кошмар. :[
 * 
 * Часто изменение требований проще описываются эволюторами (то как их видит Серега),
 * чем изменением иерархии обхъектов (с использованеим наследования и т.п.)
 */
class Model
{
	var $observers = array();
	var $config = array();

	function Model(&$rh=null)
	{
		$this->rh=& $rh;
		if ($rh)
			$this->initialize($rh);
	}

	function initialize(&$ctx, $config=NULL) 
	{ 
		$this->rh =& $ctx; 
		if (is_array($config) && is_array($this->config)) 
			$this->config = array_merge($this->config, $config);
		return True;
	}

	function finalize() { }

	function registerObservers($event, $actions)
	{
		if (is_array($actions)) foreach ($actions as $k=>$v)
			$this->observers[$event][] = &$actions[$k];
	}
	function registerObserver($event, $action)
	{
		if (isset($action))
			$this->observers[$event][] = &$action;
	}
	function notify($event, $params)
	{
		$actions = &$this->observers[$event];
		if (is_array($actions))
			foreach ($actions as $action)
				call_user_func_array($action, $params);
	}

}  

?>
