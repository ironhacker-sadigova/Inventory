@dbFull
Feature: Identification feature
  The login form authenticates users.

  @readOnly
  Scenario: Login redirection
    Given I am on the homepage
    And I wait for the page to finish loading
    Then I should be on "user/action/login"
    And I should see "Connexion"

  @javascript @readOnly
  Scenario: Logging in with wrong password
    Given I am on the homepage
    And I wait for the page to finish loading
    When I fill in "email" with "admin@myc-sense.com"
    And I fill in "password" with "blahblah"
    And I click "connection"
    Then I should see "Attention ! Le mot de passe indiqué est invalide."

  @javascript @readOnly
  Scenario: Logging in correctly
    Given I am on the homepage
    And I wait for the page to finish loading
    When I fill in "email" with "admin@myc-sense.com"
    And I fill in "password" with "myc-53n53"
    And I click "connection"
    Then I should see "Tableau de bord"

  @javascript @readOnly
  Scenario: Logging out
    Given I am logged in
    And I am on the homepage
    And I wait for the page to finish loading
    When I click "Déconnexion"
    Then I should see "Connexion"

  @javascript @readOnly
  Scenario: Forgotten password
  # TODO : à tester, pour l'instant l'accès à la page du captcha pose problème (installation des fontes) donc non testé.
    Given I am on the homepage
    And I wait for the page to finish loading
    And I click "Mot de passe oublié ?"
    Then I should see "Réinitialisation de votre mot de passe"

  @javascript @readOnly
  Scenario: Trying to reach an url without being connected
    When I go to "account/dashboard"
    Then the following message is shown and closed: "Vous n'êtes pas connecté."
    And I should see "Connexion"
