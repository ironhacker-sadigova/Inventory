<?php

namespace AF\Domain\Action\SetValue;

use AF\Domain\Action\SetValue;
use UI_Form_Action_SetValue;
use AF\Domain\AFGenerationHelper;

/**
 * @author matthieu.napoli
 * @author yoann.croizer
 */
class SetCheckboxValue extends SetValue
{
    /**
     * @var bool
     */
    protected $checked = false;

    /**
     * {@inheritdoc}
     */
    public function getUIAction(AFGenerationHelper $generationHelper)
    {
        $uiAction = new UI_Form_Action_SetValue($this->id);
        if (!empty($this->condition)) {
            $uiAction->condition = $generationHelper->getUICondition($this->condition);
        }
        $uiAction->value = $this->checked;
        return $uiAction;
    }

    /**
     * @return bool
     */
    public function getChecked()
    {
        return $this->checked;
    }

    /**
     * @param bool $checked
     */
    public function setChecked($checked)
    {
        $this->checked = (bool) $checked;
    }
}