<?php
/**
 * Test de l'API Unit.
 * @author valentin.claras
 * @author Hugo.charbonnier
 * @author Yoann.croizer
 * @package Calc
 */

/**
 * @package Calc
 */
class Calc_Test_ValueTest
{
    /**
     * Lance les autre classe de tests.
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();
        $suite->addTestSuite('Calc_Test_Calculation_ValueSetUp');
        $suite->addTestSuite('Calc_Test_Calculation_ValueOthers');
        return $suite;
    }

}

/**
 * ValueSetUpTest
 * @package Calc
 */
class Calc_Test_Calculation_ValueSetUp extends PHPUnit_Framework_TestCase
{
    /**
     * Test du constructeur d'un Calc_Calculation_Value.
     */
    function testConstructCalculation()
    {
        $o = new Calc_Calculation_Value();
        $this->assertInstanceOf('Calc_Calculation_Value', $o);
    }

    /**
     * Test du constructeur d'une Calc_Value.
     */
    function testConstructValue()
    {
        $o = new Calc_Value();
        $this->assertInstanceOf('Calc_Value', $o);
        $this->assertEquals($o->digitalValue, 0);
        $this->assertEquals($o->relativeUncertainty, null);
    }

}

/**
 * Calculation_ValuetLogiqueMetierTest
 * @package Calc
 */
class Calc_Test_Calculation_ValueOthers extends PHPUnit_Framework_TestCase
{

    /**
     * Test de la méthode calculate.
     */
    function testCalculate()
    {
         // Test somme OK
         $value1 = new Calc_Value();
         $value1->digitalValue = 2;
         $value1->relativeUncertainty = 0.1;

         $value2 = new Calc_Value();
         $value2->digitalValue = 5;
         $value2->relativeUncertainty = 0.3;

         $calcValue = new Calc_Calculation_Value();
         $calcValue->setOperation(Calc_Calculation::ADD_OPERATION);
         $calcValue->addComponents($value1, Calc_Calculation::SUM);
         $calcValue->addComponents($value2, Calc_Calculation::SUBSTRACTION);

         $result = $calcValue->calculate();
         $this->assertEquals(-3, $result->digitalValue);

         // Test multiplication OK
         $calcValue1 = new Calc_Calculation_Value();
         $calcValue1->setOperation(Calc_Calculation::MULTIPLY_OPERATION);
         $calcValue1->addComponents($value1, Calc_Calculation::PRODUCT);
         $calcValue1->addComponents($value2, Calc_Calculation::DIVISION);

         $result = $calcValue1->calculate();

         $this->assertEquals(0.4, $result->digitalValue);
    }

    /**
     * Test des exceptions
     */
    public function testExceptions()
    {
        $value = new Calc_Calculation_Value();
        try {
            $value->calculate();
        } catch (Core_Exception_InvalidArgument $e) {
            $this->assertEquals('Unknow operation', $e->getMessage());
        }
        try {
            $value->setOperation(2);
        } catch (Core_Exception_InvalidArgument $e) {
            $this->assertEquals('The operation must be a class constant', $e->getMessage());
        }
    }

}