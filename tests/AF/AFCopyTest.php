<?php
use Unit\UnitAPI;

/**
 * Test de la copie d'un AF
 */
class AFCopyTest extends Core_Test_TestCase
{
    public function setUp()
    {
        parent::setUp();

        foreach (AF_Model_AF::loadList() as $af) {
            $af->delete();
        }
        $this->entityManager->flush();
    }

    public function testCopyAF()
    {
        $oldAF = new AF_Model_AF('old_ref');
        $oldAF->setLabel('label');
        $oldAF->setDocumentation('documentation');
        $oldAF->save();

        $component1 = new AF_Model_Component_Numeric();
        $component1->setRef('component1');
        $component1->setUnit(new UnitAPI('m'));
        $component1->save();
        $oldAF->addComponent($component1);

        $this->entityManager->flush();

        $newAF = clone $oldAF;

        $this->assertInstanceOf(get_class($oldAF), $newAF);

        $this->assertNull($newAF->getId());
        $this->assertEquals($oldAF->getRef(), $newAF->getRef());
        $this->assertEquals($oldAF->getLabel(), $newAF->getLabel());
        $this->assertEquals($oldAF->getDocumentation(), $newAF->getDocumentation());
        $this->assertNull($newAF->getCategory());

        // Root group
        $this->assertNotSame($oldAF->getRootGroup(), $newAF->getRootGroup());
        $this->assertInstanceOf(get_class($oldAF->getRootGroup()), $newAF->getRootGroup());
        $this->assertNull($newAF->getRootGroup()->getId());
        $this->assertSame($newAF, $newAF->getRootGroup()->getAf());
        $this->assertSameSize($oldAF->getRootGroup()->getSubComponentsRecursive(),
            $newAF->getRootGroup()->getSubComponentsRecursive());
        foreach ($newAF->getRootGroup()->getSubComponentsRecursive() as $newSubComponent) {
            $this->assertSame($newAF, $newSubComponent->getAf());
        }

        // Algos
        $this->assertNotSame($oldAF->getMainAlgo(), $newAF->getMainAlgo());
        $this->assertInstanceOf(get_class($oldAF->getMainAlgo()), $newAF->getMainAlgo());
        $this->assertNull($newAF->getMainAlgo()->getId());
        foreach ($oldAF->getAlgos() as $oldAlgo) {
            $newAlgo = $newAF->getAlgoByRef($oldAlgo->getRef());
            $this->assertNotSame($oldAlgo, $newAlgo);
            $this->assertInstanceOf(get_class($oldAlgo), $newAlgo);
        }

        $oldAF->delete();
        $this->entityManager->flush();
    }
}
