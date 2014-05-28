<?php

namespace AF\Application\Form\Element;

use Core_Exception_InvalidArgument;
use Core_Exception_UndefinedAttribute;
use MyCLabs\MUIH\Collapse;
use Zend_Form_Element;
use Zend_View_Interface;

/**
 * Generate a group wich contains elements
 *
 * @author valentin.claras
 */
class Group extends Zend_Form_Element implements ZendFormElement
{
    /**
     * @var FormElement
     */
    protected $_element;

    /**
     * If false, the Element can't be folded.
     *
     * @var bool = true
     */
    public $foldaway = true;

    /**
     * If true, the Element is folded.
     *
     * @var bool = false
     */
    public $folded = false;

    /**
     * Attributs HTML à ajouter à l'élément généré
     * @var array
     */
    protected $attributes = [];


    /**
     * @param string $name
     *
     * @throws Core_Exception_InvalidArgument if $name is not valid.
     */
    public function __construct($name)
    {
        if (!(is_string($name))) {
            throw new Core_Exception_InvalidArgument('Name is required for the Element');
        }
        parent::__construct($name);
        $this->setLabel($name);
        $this->_element = new FormElement($this);
    }

    public function loadDefaultDecorators()
    {
        $this->addPrefixPath(
            'AF\Application\Form\Decorator',
            dirname(__FILE__) . '/../Decorator/',
            Zend_Form_Element::DECORATOR
        );
        $this->clearDecorators();
        $this->addDecorator('GroupDecorator');
    }

    /**
     * Get the associated Element.
     *
     * @return FormElement
     */
    public function getElement()
    {
        return $this->_element;
    }

    /**
     * Add an child Element in a group
     *
     * @param Zend_Form_Element $element
     */
    public function addElement(Zend_Form_Element $element)
    {
        $this->getElement()->addElement($element);
    }

    /**
     * Indique si un group est hidden (lui ou tous ses éléments.
     *
     * @return boolean
     */
    public function isHidden()
    {
        if (!($this->getElement()->hidden)) {
            foreach ($this->getElement()->children as $zendFormChild) {
                if ((!($zendFormChild->getElement()->hidden))
                    || (($zendFormChild instanceof Group)
                        && (!($zendFormChild->isHidden())))
                ) {
                    return false;
                }
            }
        }
        return (count($this->getElement()->children) > 0);
    }

    /**
     * Render form element
     *
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null)
    {
        if ($this->_isPartialRendering) {
            return '';
        }

        if (null !== $view) {
            $this->setView($view);
        }

        $content = '';

        foreach ($this->_element->children as $child) {
            if ($child instanceof self) {
                $content .= $child->render($view);
            } else {
                $child->getElement()->init($child);
                $content .= $child->render();
            }
        }

        // Help.
        if ($this->getElement()->help) {
            $decorators = $this->getDecorators();
            $this->clearDecorators();
            $this->addDecorator('HelpDecorator', array('escape' => false));
            $this->addDecorators($decorators);
        }
        // Décorators.
        foreach ($this->getDecorators() as $decorator) {
            $decorator->setElement($this);
            $content = $decorator->render($content);
        }

        return $content;
    }

    /**
     * Utilisé par Form pour fournir les scripts javascripts.
     *
     * @return string
     */
    public function getScript()
    {
        $script = '';

        if ($this->foldaway == true) {
            $collapse = new Collapse($this->getId());
            $script .= $collapse->getScript();
        }

        return $script;
    }

    /**
     * @param mixed $value
     * @param mixed $context
     * @return void
     * @throws \Core_Exception_UndefinedAttribute
     */
    public function isValid($value, $context = null)
    {
        throw new Core_Exception_UndefinedAttribute('Zend Form validation can\'t be used with Group');
    }

    /**
     * Fonction permettant d'ajouter des attributs HTML
     *
     * @param string $attributeName  Nom de l'attribut à ajouter.
     * @param string $attributeValue Valeur de l'attribut à ajouter.
     *
     * @return void
     */
    public function addAttribute($attributeName, $attributeValue)
    {
        switch ($attributeName) {
            case 'class':
                if (isset($this->attributes[$attributeName])) {
                    $this->attributes[$attributeName] = $this->attributes[$attributeName] . ' ' . $attributeValue;
                } else {
                    $this->attributes[$attributeName] = $attributeValue;
                }
                break;
            default:
                $this->attributes[$attributeName] = $attributeValue;
                break;
        }
    }

    /**
     * Attributs HTML
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}