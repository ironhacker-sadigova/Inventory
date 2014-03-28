<?php

namespace Orga\ViewModel;

/**
 * Représentation simplifiée de la vue d'une organisation pour un utilisateur.
 *
 * @author matthieu.napoli
 */
class OrganizationView
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var boolean
     */
    public $canBeEdited;

    /**
     * @var GranularityView[]
     */
    public $granularities = [];
}
