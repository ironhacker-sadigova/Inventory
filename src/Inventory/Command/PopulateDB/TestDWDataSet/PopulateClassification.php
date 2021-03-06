<?php

namespace Inventory\Command\PopulateDB\TestDWDataSet;

use Account\Domain\Account;
use Classification\Domain\ClassificationLibrary;
use Core\Translation\TranslatedString;
use Doctrine\ORM\EntityManager;
use Inventory\Command\PopulateDB\Base\AbstractPopulateClassification;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Remplissage de la base de données avec des données de test
 *
 * @Injectable(lazy=true)
 */
class PopulateClassification extends AbstractPopulateClassification
{
    /**
     * @Inject
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @Inject("account.myc-sense")
     * @var Account
     */
    private $publicAccount;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(OutputInterface $output)
    {
        $output->writeln('  <info>Populating Classification</info>');

        $library = new ClassificationLibrary(
            $this->publicAccount,
            new TranslatedString('Classification My C-Sense', 'fr'),
            true
        );
        $library->save();

        // Création des axes.
        // Params : ref, label
        // OptionalParams : Axis parent=null

        $axis_poste_article_75 = $this->createAxis($library, 'poste_article_75', 'Poste article 75');
        $axis_scope = $this->createAxis($library, 'scope', 'Scope', $axis_poste_article_75);

        // Création des membres.
        // Params : Axis, ref, label
        // OptionalParams : [Member] parents=[]

        $member_scope_1 = $this->createMember($axis_scope, '1', '1');
        $member_scope_2 = $this->createMember($axis_scope, '2', '2');

        // Création des indicateurs.
        // Params : ref, label, unitRef
        // OptionalParams : ratioUnitRef=unitRef

        $indicator_ges = $this->createIndicator($library, 'ges', 'GES', 't_co2e', 'kg_co2e');

        // Création des contextes.
        // Params : ref, label

        // $context_general = $this->createContext('general', 'Général');

        // Création des contexte-indicateurs.
        // Params : Context, Indicator
        // OptionalParams : [Axis]=[]

        // $contextIndicator_ges_general = $this->createContextIndicator($context_general, $indicator_ges, [$axis_gaz, $axis_poste_article_75]);

        $this->entityManager->flush();
    }
}
