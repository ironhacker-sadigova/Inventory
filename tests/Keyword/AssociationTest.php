<?php
use Keyword\Domain\AssociationCriteria;
use Keyword\Domain\Keyword;
use Keyword\Domain\KeywordRepository;
use Keyword\Domain\Predicate;
use Keyword\Domain\PredicateRepository;

/**
 * @todo Améliorer ça
 * @author matthieu.napoli
 */
class Keyword_Test_AssociationTest extends Core_Test_TestCase
{
    public function setUp()
    {
        parent::setUp();

        $predicate = new Predicate('predicate', 'etaciderp');
        /** @var PredicateRepository $predicateRepository */
        $predicateRepository = $this->get('Keyword\Domain\PredicateRepository');
        $predicateRepository->add($predicate);

        $keyword1 = new Keyword('foo');
        $keyword2 = new Keyword('bar');
        /** @var KeywordRepository $keywordRepository */
        $keywordRepository = $this->get('Keyword\Domain\KeywordRepository');
        $keywordRepository->add($keyword1);
        $keywordRepository->add($keyword2);

        $this->entityManager->flush();

        $keyword1->addAssociationWith($predicate, $keyword2);

        $this->entityManager->flush();
    }

    public function testAssociationCriteria()
    {
        $repository = $this->entityManager->getRepository('Keyword\Domain\Association');

        $criteria = new AssociationCriteria();
        $associations = $repository->matching($criteria);
        $this->assertCount(1, $associations);

        $criteria = new AssociationCriteria();
        $criteria->subjectRef->contains('fo');
        $associations = $repository->matching($criteria);
        $this->assertCount(1, $associations);

        // TODO continuer les tests quand 2.7 mergée sur cette branche
        $criteria = new AssociationCriteria();
        $criteria->objectRef->eq('foo');
        $associations = $repository->matching($criteria);
//        $this->assertCount(0, $associations);
    }

    public function tearDown()
    {
        parent::tearDown();

        /** @var KeywordRepository $keywordRepository */
        $keywordRepository = $this->get('Keyword\Domain\KeywordRepository');
        foreach ($keywordRepository->getAll() as $keyword) {
            $keywordRepository->remove($keyword);
        }

        $this->entityManager->flush();

        /** @var PredicateRepository $predicateRepository */
        $predicateRepository = $this->get('Keyword\Domain\PredicateRepository');
        foreach ($predicateRepository->getAll() as $predicate) {
            $predicateRepository->remove($predicate);
        }

        $this->entityManager->flush();
    }
}