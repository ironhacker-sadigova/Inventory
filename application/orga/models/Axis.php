<?php
/**
 * Classe Orga_Model_Axis
 * @author valentin.claras
 * @author diana.dragusin
 * @package    Orga
 * @subpackage Model
 */

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Objet métier définissant un axe organisationnel au sein d'un organization.
 * @package    Orga
 * @subpackage Model
 */
class Orga_Model_Axis extends Core_Model_Entity
{
    use Core_Strategy_Ordered {
        Core_Strategy_Ordered::setPositionInternal as setPositionInternalOrdered;
    }
    use Core_Model_Entity_Translatable;

    // Constantes de tris et de filtres.
    const QUERY_TAG = 'tag';
    const QUERY_REF = 'ref';
    const QUERY_LABEL = 'label';
    const QUERY_POSITION = 'position';
    const QUERY_NARROWER = 'directNarrower';
    const QUERY_ORGANIZATION = 'organization';


    /**
     * Identifiant unique de l'axe.
     *
     * @var int
     */
    protected  $id = null;

    /**
     * Référence unique (au sein d'un organization) de l'axe.
     *
     * @var string
     */
    protected  $ref = null;

    /**
     * Label de l'axe.
     *
     * @var string
     */
    protected $label = null;

    /**
     * Organization contenant l'axe.
     *
     * @var Orga_Model_Organization
     */
    protected $organization = null;

    /**
     * Tag identifiant l'axe dans la hiérarchie de l'organization.
     *
     * @var string
     */
    protected $tag = null;

    /**
     * Axe narrower de l'axe courant.
     *
     * @var Orga_Model_Axis
     */
    protected $directNarrower = null;

    /**
     * Collection des Axis broader de l'axe courant.
     *
     * @var Collection|Orga_Model_Axis[]
     */
    protected $directBroaders = null;

    /**
     * Définit si l'Axis courant contextualise les Member.
     *
     * @var bool
     */
    protected $contextualizing = false;

    /**
     * Définit si l'Axis courant permet le positionnement des Member. (ou ordre alphabétique)
     *
     * @var bool
     */
    protected $memberPositionning = false;

    /**
     * Collection des Member de l'Axis courant.
     *
     * @var Collection|Orga_Model_Member[]
     */
    protected $members = null;

    /**
     * Collection des granularités utilisant l'axe.
     *
     * @var Collection|Orga_Model_Granularity[]
     */
    protected $granularities = null;


    /**
     * Constructeur de la classe Axis.
     *
     * @param Orga_Model_Organization $organization
     * @param Orga_Model_Axis $directNarrower
     */
    public function __construct(Orga_Model_Organization $organization, Orga_Model_Axis $directNarrower=null)
    {
        $this->directBroaders = new ArrayCollection();
        $this->members = new ArrayCollection();
        $this->granularities = new ArrayCollection();

        $this->organization = $organization;
        $this->directNarrower = $directNarrower;
        if ($this->directNarrower !== null) {
            $directNarrower->addDirectBroader($this);
        }
        $this->setPosition();
        $this->organization->addAxis($this);
        $this->updateTag();
    }

    /**
     * Renvoi les valeurs du contexte pour l'objet.
     * .
     * @return array
     */
    protected function getContext()
    {
        return array('organization' => $this->organization, 'directNarrower' => $this->directNarrower);
    }

    /**
     * Fonction appelée avant un delete de l'objet (défini dans le mapper).
     */
    public function preDelete()
    {
        $this->deletePosition();
    }

    /**
     * Fonction appelée après un load de l'objet (défini dans le mapper).
     */
    public function postLoad()
    {
        $this->updateCachePosition();
    }

    /**
     * Met à jour les hashKey des membres et des cellules.
     */
    public function updateHashKeys()
    {
        $this->updateTag();
        foreach ($this->getMembers() as $member) {
            $member->updateHashKeys();
        }
        foreach ($this->granularities as $granularity) {
            $granularity->updateRef();
            foreach ($granularity->getCells() as $cell) {
                $cell->updateMembersHashKey();
            }
        }
    }

    /**
     * Renvoie l'id de l'Axis.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Définit la référence de l'axe. Ne peut pas être "global".
     *
     * @param string $ref
     *
     * @throws Core_Exception_InvalidArgument
     */
    public function setRef($ref)
    {
        if ($ref === 'global') {
            throw new Core_Exception_InvalidArgument('An Axis ref cannot be "global".');
        } else if ($this->ref !== $ref) {
            $this->ref = $ref;
            $this->updateHashKeys();
        }
    }

    /**
     * Renvoie la référence de l'axe.
     *
     * @return String
     */
    public function getRef ()
    {
        return $this->ref;
    }

    /**
     * Définit le label de l'axe.
     *
     * @param String $label
     */
    public function setLabel ($label)
    {
        $this->label = $label;
    }

    /**
     * Renvoie le label de l'axe.
     *
     * @return String
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Renvoie le organization de l'axe.
     *
     * @return Orga_Model_Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Mets à jour le tag de l'axe.
     */
    public function updateTag()
    {
        if ($this->directNarrower === null) {
            $this->tag = '/';
        } else {
            $this->tag = $this->directNarrower->tag;
        }
        $this->tag .= $this->getAxisTag() . '/';

        foreach ($this->getDirectBroaders() as $directBroaderAxis) {
            $directBroaderAxis->updateTag();
        }
    }

    /**
     * @return string
     */
    public function getAxisTag()
    {
        return $this->getPosition() . '-' . $this->getRef();
    }

    /**
     * Renvoie le tag de l'axe.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Définit la position de l'objet et renvoi sa nouvelle position.
     *
     * @param int $position
     *
     * @return int Nouvelle position
     *
     * @throws Core_Exception_InvalidArgument Position invalide
     * @throws Core_Exception_UndefinedAttribute La position n'est pas déjà définie
     */
    public function setPositionInternal($position=null)
    {
        $this->setPositionInternalOrdered($position);
        $this->getOrganization()->orderGranularities();
    }

    /**
     * Permet une surcharge facile pour lancer des évents après qu'un objet ait été déplacé.
     */
    protected function hasMove()
    {
        $this->updateHashKeys();
    }

    /**
     * Permet d'ordonner des Axis entre eux.
     *
     * @param Orga_Model_Axis $a
     * @param Orga_Model_Axis $b
     *
     * @return int 1, 0 ou -1
     */
    public static function firstOrderAxes(Orga_Model_Axis $a, Orga_Model_Axis $b)
    {
        return strcmp($a->tag, $b->tag);
    }

    /**
     * Permet d'ordonner des Axis entre eux.
     *
     * @param Orga_Model_Axis $a
     * @param Orga_Model_Axis $b
     *
     * @return int 1, 0 ou -1
     */
    public static function lastOrderAxes(Orga_Model_Axis $a, Orga_Model_Axis $b)
    {
        if (strpos($a->tag, $b->tag) !== false) {
            return -1;
        } else if (strpos($b->tag, $a->tag) !== false) {
            return 1;
        } else {
            return self::firstOrderAxes($a, $b);
        }
    }

    /**
     * Définit l'axe narrower de l'axe courant.
     *
     * @param Orga_Model_Axis $narrowerAxis
     *
     * @throws Core_Exception_InvalidArgument
     */
    public function setDirectNarrower(Orga_Model_Axis $narrowerAxis=null)
    {
        if ($this->directNarrower !== $narrowerAxis) {
            if ($narrowerAxis->isBroaderThan($this)) {
                throw new Core_Exception_InvalidArgument('The given Axis is broader than the current one.');
            }
            if ($this->directNarrower !== null) {
                $this->directNarrower->removeDirectBroader($this);
            }
            $this->deletePosition();
            $this->directNarrower = $narrowerAxis;
            if ($narrowerAxis !== null) {
                $narrowerAxis->addDirectBroader($this);
            }
            $this->setPosition();
        }
    }

    /**
     * Renvoie l'axe narrower de l'axe courant.
     *
     * @return Orga_Model_Axis
     */
    public function getDirectNarrower()
    {
        return $this->directNarrower;
    }

    /**
     * Ajoute un Axis broader à l'axe courant.
     *
     * @param Orga_Model_Axis $broaderAxis
     */
    public function addDirectBroader(Orga_Model_Axis $broaderAxis)
    {
        if (!($this->hasDirectBroader($broaderAxis))) {
            $this->directBroaders->add($broaderAxis);
            $broaderAxis->setDirectNarrower($this);
        }
    }

    /**
     * Vérifie si un Axis donné est un broader de l'axe courant.
     *
     * @param Orga_Model_Axis $broaderAxis
     *
     * @return boolean
     */
    public function hasDirectBroader(Orga_Model_Axis $broaderAxis)
    {
        return $this->directBroaders->contains($broaderAxis);
    }

    /**
     * Retire l'axe donnés .
     *
     * @param Orga_Model_Axis $broaderAxis
     */
    public function removeDirectBroader($broaderAxis)
    {
        if ($this->hasDirectBroader($broaderAxis)) {
            $this->directBroaders->removeElement($broaderAxis);
            $broaderAxis->setDirectNarrower();
        }
    }

    /**
     * Indique si l'Axis possède des broaders directs.
     *
     * @return bool
     */
    public function hasDirectBroaders()
    {
        return !$this->directBroaders->isEmpty();
    }

    /**
     * Retourne l'ensemble des broaders directs de l'Axis.
     *
     * @return Orga_Model_Axis[]
     */
    public function getDirectBroaders()
    {
        $criteria = Doctrine\Common\Collections\Criteria::create();
        $criteria->orderBy(['tag' => 'ASC']);
        return $this->directBroaders->matching($criteria);
    }

    /**
     * Retourne récursivement, tous les broaders de l'Axis dans l'ordre de première exploration.
     *
     * @return Orga_Model_Axis[]
     */
    public function getAllBroadersFirstOrdered()
    {
        $criteria = Doctrine\Common\Collections\Criteria::create();
        $criteria->where(Doctrine\Common\Collections\Criteria::expr()->contains('tag', $this->tag));
        $criteria->orderBy(['tag' => 'ASC']);
        return $this->getOrganization()->getAxes()->matching($criteria)->toArray();
    }

    /**
     * Retourne récursivement, tous les broaders de l'Axis dans l'ordre de dernière exploration.
     *
     * @return Orga_Model_Axis[]
     */
    public function getAllBroadersLastOrdered()
    {
        $broaders = $this->getAllBroadersFirstOrdered();
        @uasort($broaders, ['Orga_Model_Axis', 'lastOrderedAxes']);
        return $broaders;
    }

    /**
     * Définit si l'Axis contextualise ses membres.
     *
     * @param bool $contextualizing
     */
    public function setContextualize($contextualizing)
    {
        if ($this->contextualizing !== $contextualizing) {
            $this->contextualizing = $contextualizing;

            foreach ($this->getMembers() as $member) {
                $member->updateDirectChildrenMembersParentMembersHashKey();
            }
        }
    }

    /**
     * Indique si l'axe contextualise les Member.
     *
     * @return bool
     */
    public function isContextualizing()
    {
        return $this->contextualizing;
    }

    /**
     * Définit si l'Axis permet le positionnement de ses membres.
     *
     * @param bool $memberPositionning
     */
    public function setMemberPositionning($memberPositionning)
    {
        if ($this->memberPositionning !== $memberPositionning) {
            $this->memberPositionning = $memberPositionning;

            foreach ($this->getMembers() as $member) {
                $member->updateHashKeys();
            }
        }
    }

    /**
     * Indique si l'axe permet le positionnement de ses membres.
     *
     * @return bool
     */
    public function isMemberPositionning()
    {
        return $this->memberPositionning;
    }

    /**
     * Ajoute une Member à l'Axis.
     *
     * @param Orga_Model_Member $member
     *
     * @throws Core_Exception_InvalidArgument
     */
    public function addMember(Orga_Model_Member $member)
    {
        if ($member->getAxis() !== $this) {
            throw new Core_Exception_InvalidArgument();
        }

        if (!$this->hasMember($member)) {
            $this->members->add($member);
            foreach ($this->getGranularities() as $granularity) {
                $granularity->generateCellsFromNewMember($member);
            }
        }
    }

    /**
     * Vérifie si le Member passé fait partie de l'Axis.
     *
     * @param Orga_Model_Member $member
     *
     * @return boolean
     */
    public function hasMember(Orga_Model_Member $member)
    {
        return $this->members->contains($member);
    }

    /**
     * Retourne un tableau contenant les members de l'Axis.
     *
     * @param string $completeRef
     *
     * @throws Core_Exception_NotFound
     * @throws Core_Exception_TooMany
     *
     * @return Orga_Model_Member
     */
    public function getMemberByCompleteRef($completeRef)
    {
        $refParts = explode('#', $completeRef);
        $baseRef = (isset($refParts[0]) ? $refParts[0] : '');
        $parentMembersHashKey = (isset($refParts[1]) ? $refParts[1] : null);
        $criteria = \Doctrine\Common\Collections\Criteria::create();
        $criteria->where($criteria->expr()->eq('ref', $baseRef));
        $criteria->andWhere($criteria->expr()->eq('parentMembersHashKey', $parentMembersHashKey));
        $member = $this->members->matching($criteria)->toArray();

        if (empty($member)) {
            throw new Core_Exception_NotFound("No Member matching complete ref " . $completeRef);
        } else {
            if (count($member) > 1) {
                throw new Core_Exception_TooMany("Too many Member matching complete ref " . $completeRef);
            }
        }

        return array_pop($member);
    }

    /**
     * Supprime le Member donné de la collection de l'Axis.
     *
     * @param Orga_Model_Member $member
     */
    public function removeMember(Orga_Model_Member $member)
    {
        if ($this->hasMember($member)) {
            $this->members->removeElement($member);
        }
    }

    /**
     * Vérifie si l'Axis possède des Member.
     *
     * @return bool
     */
    public function hasMembers()
    {
        return !$this->members->isEmpty();
    }

    /**
     * Retourne un tableau contenant les members de l'Axis.
     *
     * @return Collection|Orga_Model_Member[]
     */
    public function getMembers()
    {
        return $this->members;
    }

    /**
     * Ajoute une Granularity à l'Axis ourrant.
     *
     * @param Orga_Model_Granularity $granularity
     *
     * @throws Core_Exception_InvalidArgument
     */
    public function addGranularity(Orga_Model_Granularity $granularity)
    {
        if (!$granularity->hasAxis($this)) {
            throw new Core_Exception_InvalidArgument();
        }

        if (!$this->hasGranularity($granularity)) {
            $this->granularities->add($granularity);
        }
    }

    /**
     * Vérifie si une Granularity donnée fait partit de la collection de celles utilisant l'Axis.
     *
     * @param Orga_Model_Granularity $granularity
     *
     * @return boolean
     */
    public function hasGranularity(Orga_Model_Granularity $granularity)
    {
        return $this->granularities->contains($granularity);
    }

    /**
     * Vérifie que l'Axis possède au moins une Granularity.
     *
     * @return bool
     */
    public function hasGranularities()
    {
        return !$this->granularities->isEmpty();
    }

    /**
     * Renvoie toute les Granularity utilisant l'Axis courant.
     *
     * @return Collection|Orga_Model_Granularity[]
     */
    public function getGranularities()
    {
        return $this->granularities;
    }

    /**
     * Vérifie si l'Axis courant est narrower de l'Axis donné.
     *
     * @param Orga_Model_Axis $axis
     *
     * @return bool
     */
    public function isNarrowerThan($axis)
    {
        $directNarrower = $axis->getDirectNarrower();
        return (($this == $directNarrower) || ((null !== $directNarrower) && $this->isNarrowerThan($directNarrower)));
    }

    /**
     * Vérifie si l'Axis courant est broader de l'Axis donné.
     *
     * @param Orga_Model_Axis $axis
     *
     * @return bool
     */
    public function isBroaderThan($axis)
    {
        return $axis->isNarrowerThan($this);
    }

    /**
     * Vérifie si l'Axis courant n'est ni narrower ni broader d'un des Axis donnés.
     *
     * @param Orga_Model_Axis[] $axes
     *
     * @return bool
     */
    public function isTransverse($axes)
    {
        foreach ($axes as $axis) {
            if ($axis->isBroaderThan($this) ||  $this->isBroaderThan($axis) || ($this === $axis)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return string Représentation textuelle de l'Axis
     */
    public function __toString()
    {
        return $this->getRef();
    }

}