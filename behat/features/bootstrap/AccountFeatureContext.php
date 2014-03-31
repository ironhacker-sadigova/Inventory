<?php

use Behat\Mink\WebAssert;

/**
 * @author matthieu.napoli
 */
trait AccountFeatureContext
{
    /**
     * @Given /^I am on the dashboard for account "([^"]*)"$/
     */
    public function iAmOnTheDashboardForAccount($id)
    {
        $this->visit('account/dashboard');
        $this->iSwitchToAccount($id);
    }

    /**
     * @When /^I switch to account "([^"]*)"$/
     */
    public function iSwitchToAccount($account)
    {
        $this->clickElement('#show-shortcut');
        $this->clickElement(".account-button:contains('$account')");
    }

    /**
     * @Then /^I should see the "([^"]*)" AF library$/
     */
    public function iShouldSeeTheAFLibrary($name)
    {
        $this->assertSession()->elementExists('css', ".afLibrary:contains(\"$name\")");
    }

    /**
     * @Then /^I should see the "([^"]*)" parameter library$/
     */
    public function iShouldSeeTheParameterLibrary($name)
    {
        $this->assertSession()->elementExists('css', ".parameterLibrary:contains(\"$name\")");
    }

    /**
     * @Then /^I should see the "([^"]*)" classification library$/
     */
    public function iShouldSeeTheClassificationLibrary($name)
    {
        $this->assertSession()->elementExists('css', ".classificationLibrary:contains(\"$name\")");
    }

    /**
     * @Then /^I create a new "([^"]*)" AF library$/
     */
    public function iCreateANewAFLibrary($name)
    {
        $this->clickElement('#createAFLibrary');
        $this->fillField('label', $name);
        $this->click('Ajouter');
    }

    /**
     * @param string|null $name
     * @return WebAssert
     */
    public abstract function assertSession($name = null);
    /**
     * @param string $page
     */
    public abstract function visit($page);
    public abstract function clickElement($selector);
    public abstract function click($name);
    public abstract function fillField($field, $value);
}
