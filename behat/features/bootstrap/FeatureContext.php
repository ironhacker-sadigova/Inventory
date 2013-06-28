<?php
/**
 * @author matthieu.napoli
 */

use Behat\Behat\Context\Step;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use WebDriver\Exception;

define('APPLICATION_ENV', 'developpement');
define('RUN', false);

require_once __DIR__ . '/../../../application/init.php';

require_once 'DatabaseFeatureContext.php';
require_once 'DatagridFeatureContext.php';
require_once 'PopupFeatureContext.php';

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
    use DatabaseFeatureContext;
    use DatagridFeatureContext;
    use PopupFeatureContext;

    /**
     * @Given /^(?:|I )am logged in$/
     */
    public function assertLoggedIn()
    {
        return [
            new Step\Given('I am on the homepage'),
            new Step\Given('I fill in "email" with "admin"'),
            new Step\Given('I fill in "password" with "myc-53n53"'),
            new Step\Given('I press "connection"'),
            new Step\Given('I wait for page to finish loading'),
        ];
    }

    /**
     * @Then /^the following message is shown and closed: "(?P<message>(?:[^"]|\\")*)"$/
     */
    public function assertMessageShown($message)
    {
        return [
            new Step\Then('the "#messageZone" element should contain "' . $message . '"'),
            new Step\Then('I click element "#messageZone button.close"'),
            new Step\Then('I wait for 0.5 seconds'),
            new Step\Then('the "#messageZone" element should not contain "' . $message . '"'),
        ];
    }

    /**
     * @When /^(?:|I )wait for (?:|the )page to finish loading$/
     */
    public function waitForPageToFinishLoading()
    {
        $jqueryOK = '0 === jQuery.active';
        $datagridOK = '$(".yui-dt-message:contains(\"Chargement\"):visible").length == 0';
        $maskOK = '$("#loadingMask:visible").length == 0';

        // Timeout de 6 secondes
        $this->getSession()->wait(6000, "($jqueryOK) && ($datagridOK) && ($maskOK)");
    }

    /**
     * @When /^(?:|I )wait (?:|for )(?P<seconds>\d+) seconds$/
     */
    public function wait($seconds)
    {
        $this->getSession()->wait($seconds * 1000);
    }

    /**
     * @Then /^the field "(?P<field>[^"]*)" should have error: "(?P<error>(?:[^"]|\\")*)"$/
     */
    public function assertFieldHasError($field, $error)
    {
        $field = $this->fixStepArgument($field);
        $error = $this->fixStepArgument($error);

        $node = $this->assertSession()->fieldExists($field);
        $fieldId = $node->getAttribute('id');

        $expression = '$("#' . $fieldId . '").parents(".controls").children(".errorMessage").text()';

        $errorMessage = $this->getSession()->evaluateScript("return $expression;");

        if ($errorMessage != $error) {
            throw new ExpectationException("No error message '$error' for field '$field'.\n"
                . "Error message found: '$errorMessage'.\n"
                . "Javascript expression: '$expression'.", $this->getSession());
        }
    }

    /**
     * Clicks a button or link with specified id|title|alt|text.
     *
     * @When /^(?:|I )click "(?P<name>(?:[^"]|\\")*)"$/
     */
    public function click($name)
    {
        $name = $this->fixStepArgument($name);
        $node = $this->findLinkOrButton($name);
        $node->click();

        $this->waitForPageToFinishLoading();
    }

    /**
     * Clicks an element found using CSS selectors.
     *
     * @When /^(?:|I )click element "(?P<selector>(?:[^"]|\\")*)"$/
     */
    public function clickElement($selector)
    {
        $node = $this->findElement($selector);
        $node->click();

        $this->waitForPageToFinishLoading();
    }

    /**
     * Open a collapse with specified text.
     *
     * @When /^(?:|I )open collapse "(?P<collapse>(?:[^"]|\\")*)"$/
     */
    public function openCollapse($label)
    {
        $label = $this->fixStepArgument($label);
        $node = $this->getSession()->getPage()->find(
            'css',
            'legend:contains("' . $label . '")'
        );
        $node->click();

        $this->waitForPageToFinishLoading();
    }

    /**
     * Open a collapse with specified text.
     *
     * @When /^(?:|I )open tab "(?P<label>(?:[^"]|\\")*)"$/
     */
    public function openTab($label)
    {
        $label = $this->fixStepArgument($label);
        $node = $this->getSession()->getPage()->find(
            'css',
            '.nav-tabs a:contains("' . $label . '")'
        );
        $node->click();

        $this->waitForPageToFinishLoading();
    }

    /**
     * Finds link or button with specified locator.
     *
     * @param string $locator link id, title, text or image alt
     *
     * @throws ExpectationException Not found
     * @return NodeElement|null
     */
    private function findLinkOrButton($locator)
    {
        /** @var NodeElement[] $nodes */
        $nodes = $this->getSession()->getPage()->findAll(
            'named',
            array(
                 'link_or_button',
                 $this->getSession()->getSelectorsHandler()->xpathLiteral($locator)
            )
        );

        if (count($nodes) === 0) {
            throw new ExpectationException("No link or button with text, id or title '$locator' found.",
                $this->getSession());
        }

        array_filter($nodes, function(NodeElement $node) {
                return $node->isVisible();
            });

        if (count($nodes) === 0) {
            throw new ExpectationException("No link or button with text, id or title '$locator' is visible.",
                $this->getSession());
        }

        if (count($nodes) > 1) {
            $nb = count($nodes);
            throw new ExpectationException("Too many ($nb) links or buttons with text, id or title '$locator' are visible.",
                $this->getSession());
        }

        return current($nodes);
    }

    /**
     * Finds element with specified selector.
     *
     * @param string $selector
     *
     * @throws Behat\Mink\Exception\ExpectationException
     * @return NodeElement|null
     */
    private function findElement($selector)
    {
        /** @var NodeElement[] $nodes */
        $nodes = $this->getSession()->getPage()->findAll('css', $selector);

        if (count($nodes) === 0) {
            throw new ExpectationException("No element matches selector '$selector'.",
                $this->getSession());
        }

        array_filter($nodes, function(NodeElement $node) {
                return $node->isVisible();
            });

        if (count($nodes) === 0) {
            throw new ExpectationException("No element matching '$selector' is visible.",
                $this->getSession());
        }

        if (count($nodes) > 1) {
            $nb = count($nodes);
            throw new ExpectationException("Too many ($nb) elements matching '$selector' are visible.",
                $this->getSession());
        }

        return current($nodes);
    }
}
