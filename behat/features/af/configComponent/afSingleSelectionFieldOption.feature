@dbFull
Feature: AF single selection field option feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Creation of a single selection field option scenario, correct input
  # Accès au popup des options
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Options"
    And I should see the "optionDatagrid" datagrid
  # Popup d'ajout
    When I click "Ajout d'une option"
    Then I should see the popup "Ajout d'une option"
  # Ajout, saisie correcte
    When I fill in "optionDatagrid_label_addForm" with "AAA"
    And I fill in "optionDatagrid_ref_addForm" with "aaa"
    And I click "Valider"
  # Option ajoutée apparaît en dernier
    Then the row 3 of the "optionDatagrid" datagrid should contain:
      | label | ref |
      | AAA   | aaa |
    When I click element "#selectSingleFieldDatagrid_options_popup .close:contains('×')"
    # When I click "×" (ne marche pas)
    Then the following message is shown and closed: "Ajout effectué."

  @javascript
  Scenario: Creation of a single selection field option scenario, incorrect input
  # Accès au popup des options
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Options"
    And I should see the "optionDatagrid" datagrid
  # Popup d'ajout
    When I click "Ajout d'une option"
    Then I should see the popup "Ajout d'une option"
  # Ajout, identifiant vide
    When I click "Valider"
  # Then the field "optionDatagrid_label_addForm" should have error: "Merci de renseigner ce champ."
    And the field "optionDatagrid_ref_addForm" should have error: "Merci de renseigner ce champ."
  # Ajout, identifiant avec caractères non autorisés
    When I fill in "optionDatagrid_ref_addForm" with "bépo"
    And I click "Valider"
    Then the field "optionDatagrid_ref_addForm" should have error: "Merci d'utiliser seulement les caractères : \"a..z\", \"0..9\", et \"_\"."
  # Ajout, identifiant déjà utilisé
    When I fill in "optionDatagrid_ref_addForm" with "opt_1"
    And I click "Valider"
    Then the field "optionDatagrid_ref_addForm" should have error: "Merci de choisir un autre identifiant, celui-ci est déjà utilisé."

  @javascript
  Scenario: Edition of a single selection field option scenario, correct input
  # Accès au popup des options
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Options"
  # Contenu première ligne
    And I should see the "optionDatagrid" datagrid
    And the row 1 of the "optionDatagrid" datagrid should contain:
      | label    | ref   |
      | Option 1 | opt_1 |
  # Modification du libellé
    When I set "Option 1 modifiée" for column "label" of row 1 of the "optionDatagrid" datagrid
    And I click element "#selectSingleFieldDatagrid_options_popup .close:contains('×')"
    Then the following message is shown and closed: "Modification effectuée."
  # Modification de l'identifiant, saisie correcte
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    And I set "option_1_modifiee" for column "ref" of row 1 of the "optionDatagrid" datagrid
    And I click element "#selectSingleFieldDatagrid_options_popup .close:contains('×')"
    Then the following message is shown and closed: "Modification effectuée."
  # Vérification modifications effectuées
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then the row 1 of the "optionDatagrid" datagrid should contain:
      | label             | ref               |
      | Option 1 modifiée | option_1_modifiee |

  @javascript
  Scenario: Edition of a single selection field option scenario, incorrect input
  # Accès au popup des options
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Options"
  # Modification de l'identifiant, identifiant vide
    When I set "" for column "ref" of row 1 of the "optionDatagrid" datagrid
    And I click element "#selectSingleFieldDatagrid_options_popup .close:contains('×')"
    Then the following message is shown and closed: "Merci de renseigner ce champ."
  # Modification de l'identifiant, identifiant avec caractères non autorisés
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    And I set "bépo" for column "ref" of row 1 of the "optionDatagrid" datagrid
    And I click element "#selectSingleFieldDatagrid_options_popup .close:contains('×')"
    Then the following message is shown and closed: "Merci d'utiliser seulement les caractères : \"a..z\", \"0..9\", et \"_\"."
  # Modification de l'identifiant, identifiant déjà utilisé
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    And I set "opt_2" for column "ref" of row 1 of the "optionDatagrid" datagrid
    And I click element "#selectSingleFieldDatagrid_options_popup .close:contains('×')"
    Then the following message is shown and closed: "Merci de choisir un autre identifiant, celui-ci est déjà utilisé."

  @javascript
  Scenario: Deletion of a single selection field option scenario
  # Accès au datagrid des champs de sélection simple
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
  # Ajout d'une option "valeur par défaut" pour tester la suppression
    When I set "Option 1" for column "defaultValue" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
  # Accès au popup des options
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Options"
    And I should see the "optionDatagrid" datagrid
  # Suppression sans obstacle
    When I click "Supprimer" in the row 2 of the "optionDatagrid" datagrid
    Then I should see the popup "Demande de confirmation"
    When I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # On doit rouvrir le popup des options, car les deux popups sont fermés d'un coup
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then the "optionDatagrid" datagrid should contain 1 row
  # Suppression option joue rôle de valeur par défaut
    When I click "Supprimer" in the row 1 of the "optionDatagrid" datagrid
    Then I should see the popup "Demande de confirmation"
    When I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # On doit rouvrir le popup des options, car les deux popups sont fermés d'un coup
    When I click "Options" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then the "optionDatagrid" datagrid should contain 0 row
  # TODO : bloquer une telle suppression ? (car quand on l'effectue l'option reste visible dans le datagrid tant que pas rafraîchi).
