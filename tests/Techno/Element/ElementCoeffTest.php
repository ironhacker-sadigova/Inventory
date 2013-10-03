<?php
/**
 * @package Techno
 */

use Techno\Domain\Component;
use Techno\Domain\Element\CoeffElement;
use Unit\UnitAPI;

/**
 * Test Element Coeff
 * @package Techno
 */
class Techno_Test_Element_CoeffTest
{
    /**
     * Creation of the test suite
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTestSuite('Techno_Test_Element_CoeffSetUp');
        return $suite;
    }

    /**
     * Generation of a test object
     * @return CoeffElement
     */
    public static function generateObject()
    {
        $value = new Calc_Value(10, 20);
        $baseUnit = new UnitAPI('m');
        $unit = new UnitAPI('km');
        $o = new CoeffElement();
        $o->setValue($value);
        $o->setBaseUnit($baseUnit);
        $o->setUnit($unit);
        $o->save();
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
        return $o;
    }

    /**
     * Deletion of an object created with generateObject
     * @param CoeffElement $o
     */
    public static function deleteObject($o)
    {
        $o->delete();
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
    }

}

class Techno_Test_Element_CoeffSetUp extends PHPUnit_Framework_TestCase
{

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * Méthode appelée avant les tests
     */
    public static function setUpBeforeClass()
    {
        // Vérification qu'il ne reste aucun objet en base, sinon suppression
        if (Component::countTotal() > 0) {
            foreach (Component::loadList() as $o) {
                $o->delete();
            }
        }
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
    }

    /**
     * Set up
     */
    public function setUp()
    {
        $entityManagers = Zend_Registry::get('EntityManagers');
        $this->entityManager = $entityManagers['default'];
    }

    /**
     * @return CoeffElement
     */
    public function testConstruct()
    {
        // Fixtures
        $value = new Calc_Value(10, 20);
        $baseUnit = new UnitAPI('m');
        $unit = new UnitAPI('km');

        $o = new CoeffElement();

        $this->assertInstanceOf('Calc_Value', $o->getValue());

        $o->setValue($value);
        $o->setBaseUnit($baseUnit);
        $o->setUnit($unit);
        $o->setDocumentation("Documentation");

        $o->save();
        $this->entityManager->flush();

        $this->assertNotEmpty($o->getKey());
        $this->assertSame($value, $o->getValue());
        $this->assertSame($baseUnit, $o->getBaseUnit());
        $this->assertSame($unit, $o->getUnit());
        $this->assertEquals("Documentation", $o->getDocumentation());
        return $o;
    }

    /**
     * @depends testConstruct
     * @param CoeffElement $o
     * @return CoeffElement
     */
    public function testLoad($o)
    {
        $this->entityManager->clear('Techno\Domain\Component');
        /** @var $oLoaded CoeffElement */
        $oLoaded = CoeffElement::load($o->getKey());

        $this->assertInstanceOf('Techno\Domain\Element\CoeffElement', $oLoaded);
        $this->assertNotSame($o, $oLoaded);
        $this->assertEquals($o->getKey(), $oLoaded->getKey());
        // getValue
        $this->assertEquals($o->getValue(), $oLoaded->getValue());
        $this->assertNotSame($o->getValue(), $oLoaded->getValue());
        // getBaseUnit
        $this->assertEquals($o->getBaseUnit(), $oLoaded->getBaseUnit());
        $this->assertNotSame($o->getBaseUnit(), $oLoaded->getBaseUnit());
        // getUnit
        $this->assertEquals($o->getUnit(), $oLoaded->getUnit());
        $this->assertNotSame($o->getUnit(), $oLoaded->getUnit());
        // Documentation
        $this->assertEquals("Documentation", $o->getDocumentation());
        return $oLoaded;
    }

    /**
     * @param CoeffElement $o
     * @depends testLoad
     */
    public function testDelete($o)
    {
        $o->delete();
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_REMOVED,
                            $this->entityManager->getUnitOfWork()->getEntityState($o));
        $this->entityManager->flush();
        $this->assertEquals(\Doctrine\ORM\UnitOfWork::STATE_NEW,
                            $this->entityManager->getUnitOfWork()->getEntityState($o));
    }

}
