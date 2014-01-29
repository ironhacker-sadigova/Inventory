<?php
/**
 * @author  matthieu.napoli
 * @package AF
 */

use AF\Domain\AF;
use AF\Domain\AFDeletionService;
use AF\Domain\Category;
use AF\Domain\Component\SubAF;
use AF\Domain\Output\OutputElement;
use Core\Annotation\Secure;

/**
 * Controller de l'arbre des AF
 * @package AF
 */
class AF_Tree_AfTreeController extends UI_Controller_Tree
{
    /**
     * @Inject
     * @var AFDeletionService
     */
    private $afDeletionService;

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Tree::getnodesAction()
     * @Secure("editAF")
     */
    public function getnodesAction()
    {
        // Chargement des catégories racines
        if ($this->idNode === null) {
            $categories = Category::loadRootCategories();
            $currentCategory = null;
        } else {
            /** @var $currentCategory \AF\Domain\Category */
            $currentCategory = $this->fromTreeId($this->idNode);
            $categories = $currentCategory->getChildCategories();
        }

        foreach ($categories as $category) {
            $this->addNode($this->getTreeId($category), $category->getLabel(), false, null, false, true);
        }

        if ($currentCategory) {
            foreach ($currentCategory->getAFs() as $af) {
                $this->addNode($this->getTreeId($af), $af->getLabel(), true, null, false, true);
            }
        }

        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Tree::getlistparentsAction()
     * @Secure("editAF")
     */
    public function getlistparentsAction()
    {
        $this->addElementList('', '');

        // Ajoute l'élément "Racine"
        $this->addElementList("root", __('AF', 'formTree', 'rootCategoryLabel'));

        /** @var Category[] $categories */
        $categories = Category::loadList();
        foreach ($categories as $category) {
            $this->addElementList($this->getTreeId($category), $category->getLabel());
        }
        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Tree::getlistsiblingsAction()
     * @Secure("editAF")
     */
    public function getlistsiblingsAction()
    {
        $node = $this->fromTreeId($this->idNode);

        // Détermine le parent
        $sameParent = false;
        $idParent = $this->getParam('idParent');
        if ($idParent == "root") {
            $parentNode = null;
        } elseif ($idParent) {
            $parentNode = $this->fromTreeId($idParent);
        } else {
            // Pas de parent défini, on prend le parent du noeud sélectionné
            $sameParent = true;
            if ($node instanceof Category) {
                $parentNode = $node->getParentCategory();
            } else {
                /** @var \AF\Domain\AF $node */
                $parentNode = $node->getCategory();
            }
        }

        // Charge les siblings
        if ($parentNode == null) {
            $siblings = Category::loadRootCategories();
        } else {
            if ($node instanceof Category) {
                $siblings = $parentNode->getChildCategories();
            } else {
                $siblings = $parentNode->getAFs();
            }
        }

        foreach ($siblings as $sibling) {
            // Exclut le noeud courant
            if ($sibling === $node) {
                continue;
            }
            // On ne veut pas ajouter le noeud derrière l'élément où il est déjà
            if ($sameParent && $sibling->getPosition() == $node->getPosition() - 1) {
                continue;
            }
            $this->addElementList($this->getTreeId($sibling), $sibling->getLabel());
        }

        $this->send();
    }

    /**
     * Fonction récupérant les informations d'édition pour le formulaire.
     * @Secure("editAF")
     */
    public function getinfoeditAction()
    {
        $node = $this->fromTreeId($this->idNode);

        $this->data['label'] = $node->getLabel();
        if ($node instanceof Category) {
            $this->data['title'] = __('AF', 'formTree', 'editCategoryPanelTitle');
            $this->data['htmlComplement'] = '';
        } else {
            $this->data['title'] = __('AF', 'formTree', 'editAFPanelTitle');
            $htmlComplement = '';
            $htmlComplement .= '<a href="af/edit/menu/id/' . $node->getId() . '">';
            $htmlComplement .= '<i class="fa fa-external-link"> </i> ';
            $htmlComplement .= __('UI', 'name', 'configuration');
            $htmlComplement .= '</a>';
            $htmlComplement .= '<br />';
            $htmlComplement .= '<a href="af/af/test/id/' . $node->getId() . '">';
            $htmlComplement .= '<i class="fa fa-external-link"> </i> ';
            $htmlComplement .= __('UI', 'name', 'test');
            $htmlComplement .= '</a>';
            $this->data['htmlComplement'] = $htmlComplement;
        }

        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Tree::addnodeAction()
     * @Secure("editAF")
     */
    public function addnodeAction()
    {
        // Validate the form
        $label = $this->getAddElementValue('label');
        if ($label == '') {
            $this->setAddFormElementErrorMessage('label', __('UI', 'formValidation', 'emptyRequiredField'));
            $this->send();
            return;
        }

        $category = new Category();
        $category->setLabel($label);
        $category->save();

        $this->entityManager->flush();

        $this->message = __('UI', 'message', 'added');
        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Tree::editnodeAction()
     * @Secure("editAF")
     */
    public function editnodeAction()
    {
        $node = $this->fromTreeId($this->idNode);

        $newParent = $this->_form[$this->id . '_changeParent']['value'];
        $newPosition = $this->_form[$this->id . '_changeOrder']['value'];
        $afterElement = $this->_form[$this->id . '_changeOrder']['children'][$this->id . '_selectAfter_child']['value'];
        $newLabel = $this->getEditElementValue('labelEdit');

        // Label
        if ($newLabel == '') {
            $this->setEditFormElementErrorMessage('labelEdit', __('UI', 'formValidation', 'emptyRequiredField'));
            $this->send();
            return;
        }
        if ($newLabel !== $node->getLabel()) {
            $node->setLabel($newLabel);
        }

        // Parent
        if ($newParent != null) {
            if ($newParent === 'root') {
                $parent = null;
            } else {
                /** @var $parent Category */
                $parent = $this->fromTreeId($newParent);
            }

            if ($node instanceof Category) {
                $node->setParentCategory($parent);
            } elseif ($node instanceof AF) {
                if ($parent === null) {
                    throw new Core_Exception("AF can't be at root");
                }
                $node->setCategory($parent);
            }
        }

        // Position
        if ($newPosition == 'first') {
            $node->setPosition(1);
        } elseif ($newPosition == 'last') {
            $node->setPosition($node->getLastEligiblePosition());
        } elseif ($newPosition == 'after') {
            $previousNode = $this->fromTreeId($afterElement);
            $node->moveAfter($previousNode);
        }

        $node->save();
        $this->entityManager->flush();

        $this->message = __('UI', 'message', 'updated');
        $this->send();
    }

    /**
     * Suppression d'un noeud
     * @Secure("editAF")
     */
    public function deletenodeAction()
    {
        $node = $this->fromTreeId($this->idNode);

        try {
            if ($node instanceof Category) {
                $node->delete();
                $this->entityManager->flush();
            } else {
                $this->afDeletionService->deleteAF($node);
            }
        } catch (Core_ORM_ForeignKeyViolationException $e) {
            if ($e->isSourceEntityInstanceOf(SubAF::class)
                && $e->getSourceField() == 'calledAF') {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByOtherAF');
            }
            if ($e->isSourceEntityInstanceOf(Orga_Model_CellsGroup::class)) {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByOrga');
            }
            if ($e->isSourceEntityInstanceOf(OutputElement::class)) {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByInput');
            }
            throw new Core_Exception_User('AF', 'formTree', 'categoryHasChild');
        }

        $this->message = __('UI', 'message', 'deleted');
        $this->send();
    }

    /**
     * @param \AF\Domain\Category|\AF\Domain\AF $object
     * @throws Core_Exception
     * @return string ID
     */
    private function getTreeId($object)
    {
        if ($object instanceof Category) {
            return 'c_' . $object->getId();
        } elseif ($object instanceof AF) {
            return 'a_' . $object->getId();
        }
        throw new Core_Exception("Unknown object type");
    }

    /**
     * @param string $id
     * @throws Core_Exception
     * @return Category|\AF\Domain\AF
     */
    private function fromTreeId($id)
    {
        list($type, $id) = explode('_', $id);
        if ($type === 'c') {
            return Category::load($id);
        } elseif ($type === 'a') {
            return AF::load($id);
        }
        throw new Core_Exception("Unknown object type");
    }

}
