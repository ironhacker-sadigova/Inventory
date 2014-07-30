<?php

namespace Account\Application\ViewModel;

/**
 * Représentation simplifiée de la vue d'un compte pour un utilisateur.
 *
 * @author matthieu.napoli
 */
class AccountView
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var WorkspaceView[]
     */
    public $workspaces = [];

    /**
     * @var AFLibraryView[]
     */
    public $afLibraries = [];

    /**
     * @var ParameterLibraryView[]
     */
    public $parameterLibraries = [];

    /**
     * @var ClassificationLibraryView[]
     */
    public $classificationLibraries = [];

    /**
     * @var bool
     */
    public $canEdit = false;

    /**
     * @var bool
     */
    public $canAllow = false;

    /**
     * @param int    $id
     * @param string $name
     */
    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }
}
