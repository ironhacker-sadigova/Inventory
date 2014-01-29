<?php

namespace Tests\AF;

use AF\Domain\AF\AF;
use AF\Domain\AF\Component\Checkbox;
use AF\Domain\AF\Component\NumericField;
use AF\Domain\AF\Input\CheckboxInput;
use AF\Domain\AF\Input\NumericFieldInput;
use AF\Domain\AF\InputSet\PrimaryInputSet;
use AF_Service_InputService;
use Core\Test\TestCase;
use Unit\UnitAPI;

class InputServiceTest extends TestCase
{
    /**
     * @var AF_Service_InputService
     */
    private $inputService;
    /**
     * @var AF
     */
    private $af;
    /**
     * @var NumericField
     */
    private $comp1;
    /**
     * @var Checkbox
     */
    private $comp2;
    /**
     * @var Checkbox
     */
    private $comp3;

    public function testEditInputSet()
    {
        $inputSet1 = new PrimaryInputSet($this->af);
        $input1 = new CheckboxInput($inputSet1, $this->comp2);
        $input3 = new NumericFieldInput($inputSet1, $this->comp3);
        $input3->setValue($input3->getValue()->copyWithNewValue(1));
        $input3->setHidden(false);
        $input3->setDisabled(false);

        $inputSet2 = new PrimaryInputSet($this->af);
        $input2 = new NumericFieldInput($inputSet2, $this->comp1);
        $input2->setValue($input2->getValue()->copyWithNewValue(10));
        $input32 = new NumericFieldInput($inputSet2, $this->comp3);
        $input32->setValue($input32->getValue()->copyWithNewValue(2));
        $input32->setHidden(true);
        $input32->setDisabled(true);

        $this->inputService->editInputSet($inputSet1, $inputSet2);

        // La saisie pour le composant 1 a été ajouté
        /** @var NumericFieldInput $newInputForComp1 */
        $newInputForComp1 = $inputSet1->getInputForComponent($this->comp1);
        $this->assertNotNull($newInputForComp1);
        $this->assertEquals(10, $newInputForComp1->getValue()->getDigitalValue());

        // La saisie pour le composant 2 a été supprimée
        $this->assertNull($inputSet1->getInputForComponent($this->comp2));

        // La saisie pour le composant 3 a été remplacée
        /** @var NumericFieldInput $newInputForComp3 */
        $newInputForComp3 = $inputSet1->getInputForComponent($this->comp3);
        $this->assertNotNull($newInputForComp3);
        $this->assertEquals(2, $newInputForComp3->getValue()->getDigitalValue());
        $this->assertTrue($newInputForComp3->isHidden());
        $this->assertTrue($newInputForComp3->isDisabled());

        $this->assertCount(2, $inputSet1->getInputs());
    }

    public function setUp()
    {
        parent::setUp();

        /** @var AF_Service_InputService $inputService */
        $this->inputService = $this->get(AF_Service_InputService::class);

        $this->af = new AF('test');

        $this->comp1 = new NumericField();
        $this->comp1->setAf($this->af);
        $this->comp1->setRef('comp1');
        $this->comp1->setUnit(new UnitAPI('m'));
        $this->af->addComponent($this->comp1);

        $this->comp2 = new Checkbox();
        $this->comp2->setAf($this->af);
        $this->comp2->setRef('comp2');
        $this->af->addComponent($this->comp2);

        $this->comp3 = new NumericField();
        $this->comp3->setAf($this->af);
        $this->comp3->setRef('comp3');
        $this->comp3->setUnit(new UnitAPI('m'));
        $this->af->addComponent($this->comp3);

        $this->af->save();
        $this->entityManager->flush();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->af->delete();
        $this->entityManager->flush();
    }

    public static function setUpBeforeClass()
    {
        if (AF::countTotal() > 0) {
            foreach (AF::loadList() as $o) {
                $o->delete();
            }
            self::getEntityManager()->flush();
        }
    }
}
