<?php
/**
 * @author valentin.claras
 * @author hugo.charbonnier
 * @author yoann.croizer
 * @package    Calc
 * @subpackage Calculation
 */

/**
 * @package    Calc
 * @subpackage Calculation
 */
class Calc_Calculation_Value extends Calc_Calculation
{
    /**
     * Vérifie que le tableau de component est bien homogène.
     *
     * @throws Core_Exception_InvalidArgument
     */
    public function checkComponent()
    {
        foreach ($this->components as $component) {
            if (!($component['operand'] instanceof Calc_Value)) {
                throw new Core_Exception_InvalidArgument('Array of components is not coherent.');
            }
        }
    }

    /**
     * Effectue une somme ou un produit de valeurs.
     *
     * @return Calc_Value $result
     */
    public function calculate()
    {
        $this->checkComponent();

        if ($this->operation == Calc_Calculation::ADD_OPERATION) {
            return $this->calculateSum();
        } else if ($this->operation == Calc_Calculation::MULTIPLY_OPERATION) {
            return $this->calculateProduct();
        }

        throw new Core_Exception_InvalidArgument('Unknow operation');
    }

    /**
     * Effectue la somme de valeurs.
     *
     * @return Calc_Value
     */
    protected function calculateSum()
    {
        $result = new Calc_Value();
        $result->digitalValue = 0;

        foreach ($this->components as $value) {
            // Selon le signe du calcul on additionne ou on soustrait.
            if ($value['signExponent'] == Calc_Calculation::SUBSTRACTION) {
                $result->digitalValue += - $value['operand']->digitalValue;
            } else {
                $result->digitalValue += $value['operand']->digitalValue;
            }
        }
        $result->relativeUncertainty = $this->sumUncertaintyCalculation();

        return $result;
    }

    /**
     * Permet de calculer l'incertitude lors d'une somme ou d'une soustraction.
     *
     * @return float
     */
    protected function sumUncertaintyCalculation()
    {
        $valueSum = 0;
        // Somme des carrés
        $squareSum = 0;
        $emptyUncertainty = true;

        // On regarde si les incertitudes sont vide ou non
        foreach ($this->components as $value) {
            if ($value['operand']->relativeUncertainty !== null) {
                $emptyUncertainty = false;
                break;
            }
        }
        // Si toutes les incertitudes sont vide alors on renvoi null
        if ($emptyUncertainty == true) {
            return null;
        } else {
            // Sinon on calcul les incertitudes
            foreach ($this->components as $value) {
                $squareSum  += pow($value['operand']->digitalValue * $value['operand']->relativeUncertainty, 2);
                $valueSum += $value['operand']->digitalValue;
            }
            if (abs($valueSum) * sqrt($squareSum) == 0) {
                $result = 0;
            } else {
                $result = sqrt($squareSum) / abs($valueSum);
            }

            return $result;
        }
    }


    /**
     * Effectue la multiplication de valeurs.
     *
     * @return Calc_Value
     */
    protected function calculateProduct()
    {
        $result = new Calc_Value();
        $result->digitalValue = 1;

        foreach ($this->components as $value) {
            $result->digitalValue *= pow($value['operand']->digitalValue, $value['signExponent']);
        }
        $result->relativeUncertainty = $this->productUncertaintyCalculation();

        return $result;
    }


    /**
     * Permet de calculer les incertitudes d'une multiplication ou division
     *
     * @return Float
     */
    protected function productUncertaintyCalculation()
    {
        $result = 0;
        $emptyUncertainty = true;

        // On regarde si les incertitudes sont vides ou non
        foreach ($this->components as $value) {
            if ($value['operand']->relativeUncertainty !== null) {
                $emptyUncertainty = false;
                break;
            }
        }
        // Si toutes les incertitudes sont vides alors on renvoi null
        if ($emptyUncertainty == true) {
            return null;
        } else {
            // Sinon on calcul l'incertitude
            foreach ($this->components as $value) {
                // Si une valeur est null on ne calcul pas l'incertitude relative mais on renvoi 0
                if ($value['operand']->digitalValue == 0) {
                    return 0;
                }
                $result += pow($value['operand']->relativeUncertainty, 2);
            }
            $result = sqrt($result);

            return $result;
        }
    }

}