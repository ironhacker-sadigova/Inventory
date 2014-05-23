<?php

namespace AF\Domain;

use Account\Domain\Account;
use Core\Translation\TranslatedString;
use Core_Model_Entity;
use Core_Model_Filter;
use Core_Model_Query;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use MyCLabs\ACL\Model\CascadingResource;
use MyCLabs\ACL\Model\EntityResource;

/**
 * Bibliothèque d'AF.
 *
 * @author matthieu.napoli
 */
class AFLibrary extends Core_Model_Entity implements EntityResource, CascadingResource
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Account
     */
    protected $account;

    /**
     * @var TranslatedString
     */
    protected $label;

    /**
     * @var Category[]|Collection
     */
    protected $categories;

    /**
     * @var AF[]|Collection
     */
    protected $afList;

    /**
     * @var bool
     */
    protected $public = false;

    /**
     * @param Account          $account
     * @param TranslatedString $label
     * @param bool             $public
     */
    public function __construct(Account $account, TranslatedString $label, $public = false)
    {
        $this->account = $account;
        $this->label = $label;
        $this->public = $public;

        $this->categories = new ArrayCollection();
        $this->afList = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return TranslatedString
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param int|null $count
     * @param int|null $offset
     * @return Collection|AF[]
     */
    public function getAFList($count = null, $offset = null)
    {
        $criteria = Criteria::create();
        $criteria->setMaxResults($count);
        $criteria->setFirstResult($offset);

        return $this->afList->matching($criteria);
    }

    public function addAF(AF $af)
    {
        $this->afList->add($af);
    }

    public function removeAF(AF $af)
    {
        $this->afList->removeElement($af);
    }

    public function addCategory(Category $category)
    {
        $this->categories->add($category);
    }

    public function removeCategory(Category $category)
    {
        $this->categories->removeElement($category);
    }

    /**
     * @return Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return Category[]
     */
    public function getRootCategories()
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->isNull('parentCategory'));

        return $this->categories->matching($criteria);
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return bool Est-ce que la bibliothèque est publique ?
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * Rend publique (ou non) la bibliothèque.
     *
     * @param bool $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * {@inheritdoc}
     */
    public function getParentResources(EntityManager $entityManager)
    {
        return [ $this->account ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSubResources(EntityManager $entityManager)
    {
        return [];
    }

    /**
     * @param Account $account
     * @return AFLibrary[]
     */
    public static function loadByAccount(Account $account)
    {
        $query = new Core_Model_Query();
        $query->filter->addCondition('account', $account);

        return self::getEntityRepository()->loadList($query);
    }

    /**
     * Renvoie toutes les librairies utilisables dans le compte donné.
     * Cela inclut les librairies du compte, mais également les librairies publiques.
     * @param Account $account
     * @return AFLibrary[]
     */
    public static function loadUsableInAccount(Account $account)
    {
        $query = new Core_Model_Query();
        $query->filter->condition = Core_Model_Filter::CONDITION_OR;
        $query->filter->addCondition('account', $account);
        $query->filter->addCondition('public', true);

        return self::getEntityRepository()->loadList($query);
    }
}
