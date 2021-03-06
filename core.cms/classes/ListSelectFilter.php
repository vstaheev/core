<?php
/**
 * ListSelectFilter
 *
 * @author lunatic lunatic@jetstyle.ru
 */
Finder::useClass('ListFilter');

class ListSelectFilter extends ListFilter
{
    protected $getVar = '';
    protected $neededConfigVars = array('model', 'field');

    protected $getVarValue = '';
    protected $data = array();

    protected $template = 'list_select_filter.html';

    public function getValue()
    {
        return $this->getVarValue;
    }

    public function apply(&$model)
    {
        if ($this->getVarValue || $this->getConfig('always_apply'))
        {
            $depends = $this->getConfig('depends');
            if ($depends)
            {
                $filter = $this->getListObj()->getFiltersObject($depends);

                if (!$filter->getValue())
                {
                    return;
                }
            }

            if ($model instanceof DBModel)
            {
                $model->where .= ($model->where ? " AND " : "")." {".$this->getConfig('field')."} = ".DBModel::quote($this->getVarValue);
            }
            else
            {
                $model .= ($where ? " AND " : "")." ".$this->getConfig('field')." = ".DBModel::quote($this->getVarValue);
            }
        }
    }

    public function markSelected(&$model, &$row)
    {
        if ($row['id'] == $this->getVarValue)
        {
            $row['selected'] = true;
        }
    }

    public function collectRows(&$model, &$row)
    {
        $this->data[] = $row;
    }

    protected function applyDependencies(&$model)
    {
        $depends = $this->getConfig('depends');
        if ($depends)
        {
            if (!is_array($depends))
            {
                $depends = array($depends);
            }

            foreach ($depends AS $filterKey)
            {
                $filter = $this->getListObj()->getFiltersObject($filterKey);

                if (!$filter->getValue())
                {
                    $this->disableFilter($model);
                    break;
                }
                else
                {
                    $filter->apply($model);
                }
            }
        }
    }

    protected function constructModel()
    {
        $model = DBModel::factory($this->getConfig('model'));
        $model->registerObserver('row', array($this, 'markSelected'));
        $model->registerObserver('row', array($this, 'collectRows'));

        return $model;
    }

    protected function getTplData()
    {
        $tplData = array(
            'get_var' => $this->getVar,
            'data' => $this->data,
            'title' => $this->getConfig('title')
        );
        
        return $tplData;
    }
    
    protected function init()
    {
        $this->getVar = $this->getConfig('get_var');

        if (!$this->getVar)
        {
            $this->getVar = $this->getConfig('field');
        }

        $this->getVarValue = RequestInfo::get($this->getVar);
    }
}
?>