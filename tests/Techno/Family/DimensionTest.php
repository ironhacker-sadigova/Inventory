<?php

use Core\Test\TestCase;
use Doctrine\ORM\UnitOfWork;
use Keyword\Domain\KeywordRepository;
use Techno\Domain\Family\Dimension;
use Techno\Domain\Meaning;
use Techno\Domain\Tag;
use Techno\Domain\Component;

class Techno_Test_Family_DimensionTest
{
    /**
     * Creation of the test suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTestSuite('Techno_Test_Family_DimensionSetUp');
        $suite->addTestSuite('Techno_Test_Family_DimensionMetier');
        return $suite;
    }

    /**
     * Génere un objet dérivé prêt à l'emploi pour les tests.
     * @return Dimension
     */
    public static function generateObject()
    {
        // Fixtures
        $family = Techno_Test_Family_CoeffTest::generateObject();
        $meaning = Techno_Test_MeaningTest::generateObject();
        $o = new Dimension($family, $meaning, Dimension::ORIENTATION_HORIZONTAL);
        $o->save();
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
        return $o;
    }

    /**
     * Deletion of an object created with generateObject
     * @param Dimension $o
     */
    public static function deleteObject($o)
    {
        $o->getFamily()->removeDimension($o);
        $o->delete();
        Techno_Test_Family_CoeffTest::deleteObject($o->getFamily());
        Techno_Test_MeaningTest::deleteObject($o->getMeaning());
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
    }
}

class Techno_Test_Family_DimensionSetUp extends TestCase
{
    public static function setUpBeforeClass()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = Zend_Registry::get('EntityManagers')['default'];
        // Vérification qu'il ne reste aucun objet en base, sinon suppression
        foreach (Component::loadList() as $o) {
            $o->delete();
        }
        foreach (Tag::loadList() as $o) {
            $o->delete();
        }
        foreach (Meaning::loadList() as $o) {
            $o->delete();
        }
        /** @var KeywordRepository $keywordRepository */
        $keywordRepository = $entityManager->getRepository('\Keyword\Domain\Keyword');
        if ($keywordRepository->count() > 0) {
            foreach ($keywordRepository->getAll() as $o) {
                $keywordRepository->remove($o);
            }
        }
        $entityManager->flush();
    }

    /**
     * @return Dimension
     */
    public function testConstruct()
    {
        // Fixtures
        $family = Techno_Test_Family_CoeffTest::generateObject();
        $meaning = Techno_Test_MeaningTest::generateObject();

        $o = new Dimension($family, $meaning, Dimension::ORIENTATION_HORIZONTAL);

        $this->assertSame($family, $o->getFamily());
        $this->assertSame($meaning, $o->getMeaning());
        $this->assertEquals(Dimension::ORIENTATION_HORIZONTAL, $o->getOrientation());

        $o->save();
        $this->entityManager->flush();

        $this->assertInstanceOf('Techno\Domain\Family\Family', $o->getFamily());
        $this->assertEquals($family->getRef(), $o->getFamily()->getRef());
        $this->assertInstanceOf('Techno\Domain\Meaning', $o->getMeaning());
        $this->assertEquals($meaning->getKey(), $o->getMeaning()->getKey());
        return $o;
    }

    /**
     * @depends testConstruct
     * @param Dimension $o
     * @return Dimension
     */
    public function testLoad($o)
    {
        $this->entityManager->clear();
        /** @var $oLoaded Dimension */
        $oLoaded = Dimension::load($o->getKey());

        $this->assertInstanceOf('Techno\Domain\Family\Dimension', $oLoaded);
        $this->assertNotSame($o, $oLoaded);
        $this->assertEquals($o->getKey(), $oLoaded->getKey());
        // getFamily
        $this->assertInstanceOf('Techno\Domain\Family\Family', $oLoaded->getFamily());
        $this->assertEquals($o->getFamily()->getRef(), $oLoaded->getFamily()->getRef());
        // getMeaning
        $this->assertInstanceOf('Techno\Domain\Meaning', $oLoaded->getMeaning());
        $this->assertEquals($o->getMeaning()->getKey(), $oLoaded->getMeaning()->getKey());
        return $oLoaded;
    }

    /**
     * @depends testLoad
     * @param Dimension $o
     */
    public function testDelete($o)
    {
        $o->delete();
        $this->assertEquals(UnitOfWork::STATE_REMOVED, $this->entityManager->getUnitOfWork()->getEntityState($o));
        // Remove from the family to avoid cascade problems
        $o->getFamily()->removeDimension($o);
        // Delete fixtures
        Techno_Test_Family_CoeffTest::deleteObject($o->getFamily());
        Techno_Test_MeaningTest::deleteObject($o->getMeaning());
        $this->entityManager->flush();
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->entityManager->getUnitOfWork()->getEntityState($o));
    }
}

class Techno_Test_Family_DimensionMetier extends TestCase
{
    public static function setUpBeforeClass()
    {
        /** @var \Doctrine\ORM\EntityManager $entityManager */
        $entityManager = Zend_Registry::get('EntityManagers')['default'];
        // Vérification qu'il ne reste aucun objet en base, sinon suppression
        foreach (Dimension::loadList() as $o) {
            $o->delete();
        }
        foreach (Component::loadList() as $o) {
            $o->delete();
        }
        foreach (Tag::loadList() as $o) {
            $o->delete();
        }
        foreach (Meaning::loadList() as $o) {
            $o->delete();
        }
        /** @var KeywordRepository $keywordRepository */
        $keywordRepository = $entityManager->getRepository('\Keyword\Domain\Keyword');
        if ($keywordRepository->count() > 0) {
            foreach ($keywordRepository->getAll() as $o) {
                $keywordRepository->remove($o);
            }
        }
        $entityManager->flush();
    }

    /**
     * Teste l'association à sa famille
     */
    public function testBidirectionalFamilyAssociation()
    {
        // Fixtures
        $family = Techno_Test_Family_CoeffTest::generateObject();
        $meaning = Techno_Test_MeaningTest::generateObject();

        // Charge la collection pour éviter le lazy-loading en dessous
        // (le lazy loading entrainerait le chargement depuis la BDD et donc la prise en compte
        // de l'association BDD même si elle n'était pas faite au niveau PHP)
        $family->getDimensions();

        $o = new Dimension($family, $meaning, Dimension::ORIENTATION_HORIZONTAL);

        // Vérifie que l'association a été affectée bidirectionnellement
        $this->assertTrue($family->hasDimension($o));

        Techno_Test_Family_CoeffTest::deleteObject($family);
        Techno_Test_MeaningTest::deleteObject($meaning);
    }

    /**
     * Teste la persistence en cascade depuis la famille
     */
    public function testCascadeFromFamily()
    {
        // Fixtures
        $family = Techno_Test_Family_CoeffTest::generateObject();
        $meaning = Techno_Test_MeaningTest::generateObject();

        $o = new Dimension($family, $meaning, Dimension::ORIENTATION_HORIZONTAL);

        // Vérification de la cascade de la persistence
        $family->save();
        $this->entityManager->flush();
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->entityManager->getUnitOfWork()->getEntityState($o));

        // Vérification de la cascade de la suppression
        Techno_Test_Family_CoeffTest::deleteObject($family);
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->entityManager->getUnitOfWork()->getEntityState($o));

        Techno_Test_MeaningTest::deleteObject($meaning);
    }

    /**
     * Test de la position
     */
    public function testPosition()
    {
        // Fixtures
        $family = Techno_Test_Family_CoeffTest::generateObject();
        $meaning1 = Techno_Test_MeaningTest::generateObject();
        $meaning2 = Techno_Test_MeaningTest::generateObject();
        $meaning3 = Techno_Test_MeaningTest::generateObject();
        $meaning4 = Techno_Test_MeaningTest::generateObject();

        $o1 = new Dimension($family, $meaning1, Dimension::ORIENTATION_HORIZONTAL);
        $o1->save();
        $o2 = new Dimension($family, $meaning2, Dimension::ORIENTATION_HORIZONTAL);
        $o2->save();
        $o3 = new Dimension($family, $meaning3, Dimension::ORIENTATION_VERTICAL);
        $o3->save();
        $o4 = new Dimension($family, $meaning4, Dimension::ORIENTATION_VERTICAL);
        $o4->save();
        $this->entityManager->flush();

        $this->assertEquals(1, $o1->getPosition());
        $this->assertEquals(2, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // setPosition
        $o2->setPosition(1);
        $o2->save();
        $this->entityManager->flush();
        $this->assertEquals(2, $o1->getPosition());
        $this->assertEquals(1, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // up
        $o1->goUp();
        $o1->save();
        $this->entityManager->flush();
        $this->assertEquals(1, $o1->getPosition());
        $this->assertEquals(2, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // down
        $o1->goDown();
        $o1->save();
        $this->entityManager->flush();
        $this->assertEquals(2, $o1->getPosition());
        $this->assertEquals(1, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // Delete
        $o2->delete();
        $this->assertEquals(1, $o1->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        Techno_Test_Family_CoeffTest::deleteObject($family);
        Techno_Test_MeaningTest::deleteObject($meaning1);
        Techno_Test_MeaningTest::deleteObject($meaning2);
        Techno_Test_MeaningTest::deleteObject($meaning3);
        Techno_Test_MeaningTest::deleteObject($meaning4);
    }
}
