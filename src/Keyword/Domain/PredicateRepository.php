<?php

namespace Keyword\Domain;

use Core\Domain\EntityRepository;
use Core_Exception_NotFound;

/**
 * Gère les Predicate.
 * @author valentin.claras
 */
interface PredicateRepository extends EntityRepository
{
    const QUERY_REF = 'ref';
    const QUERY_LABEL = 'label';

    /**
     * Renoie les messages d'erreur concernant la validation d'une ref.
     *
     * @param string $ref
     *
     * @return mixed string null
     */
    public function getErrorMessageForRef($ref);

    /**
     * Vérifie la disponibilité d'une référence pour un prédicat.
     *
     * @param string $ref
     *
     * @throws \Core_Exception_User
     */
    public function checkRef($ref);

    /**
     * Retourne un Predicate grâce à son ref.
     *
     * @param string $predicateRef
     * @throws \Core_Exception_NotFound
     * @return Predicate
     */
    function getOneByRef($predicateRef);

    /**
     * Retourne un Predicate grâce à son ref inverse.
     *
     * @param string $predicateReverseRef
     * @throws \Core_Exception_NotFound
     * @return Predicate
     */
    function getOneByReverseRef($predicateReverseRef);

    /**
     * @param TranslatableEntity $entity Entité du Repository
     * @param \Core_Locale|null $locale Si null, utilise la locale par défaut
     */
    function changeLocale(TranslatableEntity $entity, \Core_Locale $locale);

}
