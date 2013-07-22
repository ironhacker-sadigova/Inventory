@dbFull
Feature: Single selection field feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Creation of a single selection field scenario
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
  # Popup d'ajout
    When I click "Ajouter"
    Then I should see the popup "Ajout d'un champ de sélection simple"
  # Ajout, identifiant vide
    When I click "Valider"
  # Then the field "selectSingleFieldDatagrid_label_addForm" should have error: "Merci de renseigner ce champ."
    And the field "selectSingleFieldDatagrid_ref_addForm" should have error: "Merci de renseigner ce champ."
  # Ajout, identifiant avec caractères non autorisés
    When I fill in "selectSingleFieldDatagrid_ref_addForm" with "bépo"
    And I click "Valider"
    Then the field "selectSingleFieldDatagrid_ref_addForm" should have error: "Merci d'utiliser seulement les caractères : \"a..z\", \"0..9\", et \"_\"."
  # Ajout, identifiant déjà utilisé
    When I fill in "selectSingleFieldDatagrid_ref_addForm" with "champ_numerique"
    And I click "Valider"
    Then the field "selectSingleFieldDatagrid_ref_addForm" should have error: "Merci de choisir un autre identifiant, celui-ci est déjà utilisé."
  # Ajout, saisie correcte
    When I fill in "selectSingleFieldDatagrid_label_addForm" with "AAA"
    And I fill in "selectSingleFieldDatagrid_ref_addForm" with "aaa"
    And I fill in "selectSingleFieldDatagrid_help_addForm" with "h1. Blabla"
    And I click "Valider"
    Then the following message is shown and closed: "Ajout effectué."
  # Champs ordonnés suivant l'ordre de création, vérification des valeurs par défaut
    And the row 2 of the "selectSingleFieldDatagrid" datagrid should contain:
      | label | ref | isVisible | enabled | required    | type             |
      | AAA   | aaa | Visible   | Activé  | Facultatif  | Liste déroulante |
    When I click "Aide" in the row 2 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Aide"
    And I should see a "#selectSingleFieldDatagrid_help_popup .modal-body h1:contains('Blabla')" element

  @javascript
  Scenario: Edition of a single selection field scenario
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
    And the row 1 of the "selectSingleFieldDatagrid" datagrid should contain:
      | label                  | ref                    | isVisible | enabled | required    | defaultValue | type             |
      | Champ sélection simple | champ_selection_simple | Visible   | Activé  | Obligatoire |              | Liste déroulante |
  # Modification du libellé
    When I set "Champ sélection simple modifié" for column "label" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
  # Modification de l'identifiant, identifiant vide
    When I set "" for column "ref" of row 1 of the "selectSingleFieldDatagrid" datagrid
    Then the following message is shown and closed: "Merci de renseigner ce champ."
  # Modification de l'identifiant, identifiant avec caractères non autorisés
    When I set "bépo" for column "ref" of row 1 of the "selectSingleFieldDatagrid" datagrid
    Then the following message is shown and closed: "Merci d'utiliser seulement les caractères : \"a..z\", \"0..9\", et \"_\"."
  # Modification de l'identifiant, identifiant déjà utilisé
    When I set "champ_numerique" for column "ref" of row 1 of the "selectSingleFieldDatagrid" datagrid
    Then the following message is shown and closed: "Merci de choisir un autre identifiant, celui-ci est déjà utilisé."
  # Modification de l'identifiant, saisie correcte
    When I set "champ_selection_simple_modifie" for column "ref" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
  # Modification de l'aide
    When I set "h1. Aide modifiée" for column "help" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
  # Modification des autres attributs
    When I set "Masqué" for column "isVisible" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
    When I set "Désactivé" for column "enabled" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
    When I set "Facultatif" for column "required" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
    When I set "Option 1" for column "defaultValue" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
    When I set "Boutons radio" for column "type" of row 1 of the "selectSingleFieldDatagrid" datagrid with a confirmation message
  # Vérification que les modifications on bien été prises en compte au niveau du datagrid
    Then the row 1 of the "selectSingleFieldDatagrid" datagrid should contain:
      | label                          | ref                            | isVisible | enabled   | required   | defaultValue | type          |
      | Champ sélection simple modifié | champ_selection_simple_modifie | Masqué    | Désactivé | Facultatif | Option 1     | Boutons radio |
    When I click "Aide" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Aide"
    And I should see a "#selectSingleFieldDatagrid_help_popup .modal-body h1:contains('Aide modifiée')" element

  @javascript
  Scenario: Deletion of a single selection field scenario
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Composants"
    And I open collapse "Champs de sélection simple"
    Then I should see the "selectSingleFieldDatagrid" datagrid
    And the "selectSingleFieldDatagrid" datagrid should contain 1 row
  # Suppression sans obstacle
    When I click "Supprimer" in the row 1 of the "selectSingleFieldDatagrid" datagrid
    Then I should see the popup "Demande de confirmation"
    When I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
    And the "selectSingleFieldDatagrid" datagrid should contain 0 row
