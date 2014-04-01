<?php

namespace UI\Datagrid\Column;

use MyCLabs\MUIH\GenericTag;
use MyCLabs\MUIH\GenericVoidTag;
use UI\Datagrid\Datagrid;
use UI_Form_Element_Text;

/**
 * Classe représentant une colonne contenant des textes.
 *
 * @author valentin.claras
 */
class TextColumn extends GenericColumn
{
    /**
     * Définition du mot clef du filtre pour l'égalité.
     *
     * @var string
     */
    public $keywordFilterEqual;


    public function __construct($id = null, $label = null)
    {
        parent::__construct($id, $label);
        // Définition des pseudo-constantes pouvant être redéfinies.
        $this->valueAlignment = self::DISPLAY_TEXT_LEFT;
        $this->keywordFilterEqual = __('UI', 'datagridFilter', 'ColTextEqual');
        $this->criteriaFilterOperator = 'contains';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter(Datagrid $datagrid)
    {
        return <<<JS
if (typeof(sData) != "object") {
    content = sData;
} else {
    if (sData.content != null) {
        content = sData.content;
    } else {
        content = sData.value;
    }
}
JS;
    }

    /**
     * {@inheritdoc}
     */
    public function getEditorValue(Datagrid $datagrid)
    {
        return <<<JS
this.onEventShowCellEditor(oArgs);
if ((typeof(sData) == "undefined") || (sData == null)) {
    var content = '';
} else if (typeof(sData) != "object") {
    var content = sData.toString();
} else {
    var content = sData.value.toString();
}
column.editor.textbox.value = content;
JS;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterFormElement(Datagrid $datagrid, $defaultValue = null)
    {
        $filterFormElement = new UI_Form_Element_Text($this->getFilterFormId($datagrid));
        $filterFormElement->setLabel($this->getFilterFormLabel());
        $filterFormElement->getElement()->addPrefix($this->keywordFilterEqual);

        // Récupération des valeurs par défaut.
        if (isset($defaultValue[$this->criteriaFilterOperator])) {
            $filterFormElement->setValue($defaultValue[$this->criteriaFilterOperator]);
        }

        $filterFormElement->getElement()->addSuffix($this->getResetFieldFilterFormSuffix($datagrid));

        return $filterFormElement;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddFormElement(Datagrid $datagrid)
    {
        $colWrapper = new GenericTag('div');
        $colWrapper->addClass('form-group');

        $colLabel = new GenericTag('label', $this->getAddFormElementLabel());
        $colLabel->setAttribute('for', $this->getAddFormElementId($datagrid));
        $colLabel->addClass('col-sm-2');
        $colLabel->addClass('control-label');
        $colLabel->addClass('field-label');
        $colWrapper->appendContent($colLabel);

        $textWrapper = new GenericTag('div');
        $textWrapper->addClass('col-sm-10');

        $textInput = new GenericVoidTag('input');
        $textInput->setAttribute('type', 'text');
        $textInput->setAttribute('name', $this->getAddFormElementId($datagrid));
        $textInput->setAttribute('id', $this->getAddFormElementId($datagrid));
        $textInput->setAttribute('value', $this->defaultAddValue);
        $textInput->addClass('form-control');
        $textWrapper->appendContent($textInput);

        $colWrapper->appendContent($textWrapper);

        return $colWrapper;
    }
}
