<?php
/**
 * Classe AF_Datagrid_Translate_ComponentsController
 * @author valentin.claras
 * @package AF
 * @subpackage Controller
 */

use Core\Annotation\Secure;

/**
 * Classe du controller du datagrid des traductions des components.
 * @package AF
 * @subpackage Controller
 */
class AF_Datagrid_Translate_ComponentsController extends UI_Controller_Datagrid
{
    /**
     * Désactivation du fallback des traductions.
     */
    public function init()
    {
        parent::init();
        Zend_Registry::get('doctrineTranslate')->setTranslationFallback(false);
    }

    /**
     * Fonction renvoyant la liste des éléments peuplant la Datagrid.
     *
     * @Secure("editAF")
     */
    public function getelementsAction()
    {
        foreach (AF_Model_Component::loadList($this->request) as $component) {
            $data = array();
            $data['index'] = $component->getId();
            $data['identifier'] = $component->getId();

            foreach (Zend_Registry::get('languages') as $language) {
                $locale = Core_Locale::load($language);
                $component->reloadWithLocale($locale);
                $data[$language] = $component->getLabel();
            }
            $this->addline($data);
        }
        $this->totalElements = AF_Model_Component::countTotal($this->request);

        $this->send();
    }

    /**
     * Fonction modifiant la valeur d'un élément.
     *
     * @Secure("editAF")
     */
    public function updateelementAction()
    {
        $component = AF_Model_Component::load($this->update['index']);
        $component->setTranslationLocale(Core_Locale::load($this->update['column']));
        $component->setLabel($this->update['value']);
        $this->data = $component->getLabel();

        $this->send(true);
    }
}