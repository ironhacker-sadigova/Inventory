@dbFull
Feature: Cell coordinator feature

  @javascript @readOnly
  Scenario: Coordinator of a single cell
    Given I am on the homepage
    And I wait for the page to finish loading
  # Login
    When I fill in "email" with "coordinateur.zone-marque@toto.com"
    And I fill in "password" with "coordinateur.zone-marque@toto.com"
    And I click "connection"
  # On tombe sur la page de la cellule
    Then I should see "Workspace avec données"
    When I click element "tr.workspace h4 a:contains('Workspace avec données')"
    And I wait for the page to finish loading
    Then I should see "Workspace avec données"
    And I should see "Europe | Marque A"
  # Accès à une saisie et à l'historique des valeurs d'un champ (suite à détection bug droits utilisateur)
    When I wait 5 seconds
    And I click element "div[id='currentGranularity'] a.go-input"
    And I click element "#chiffre_affaireHistory"
    And I wait 2 seconds
    Then I should see "Historique des valeurs"
    And I should see a "code:contains('10 k€ ± 15 %')" element
    And I click element "#chiffre_affaireHistory"
  # Accès à l'onglet "Collectes", édition du statut d'une collecte
    When I click "Quitter"
    Then I should see "Workspace avec données"
    Then I should see "Europe | Marque A"

  @javascript @readOnly
  Scenario: Coordinator of several cells
    Given I am on the homepage
    And I wait for the page to finish loading
  # Login
    When I fill in "email" with "coordinateur.site@toto.com"
    And I fill in "password" with "coordinateur.site@toto.com"
    And I click "connection"
  # On tombe sur le datagrid des cellules
    Then I should see "Workspace avec données"
    When I click element "tr.workspace h4 a:contains('Workspace avec données')"
    And I wait for the page to finish loading
    Then I should see "Workspace avec données"
    And I should see "Coordinateur Annecy"
    And I should see "Coordinateur Chambéry"
  # Accès à une des cellules
    When I click "Coordinateur Annecy"
    Then I should see "Annecy"

  @javascript @readOnly
  Scenario: Coordinator can edit an input
    Given I am logged in as "coordinateur.zone-marque@toto.com"
    Given I am on "orga/cell/input/cell/30/fromCell/3/"
    And I wait 3 seconds
  # On va sur la page de la cellule
    Then I should see "Saisie 2012 | Annecy"
    When I fill in "chiffre_affaire" with "100"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué (saisie complète)."
    When I click "Terminer la saisie"
    Then the following message is shown and closed: "Saisie terminée."