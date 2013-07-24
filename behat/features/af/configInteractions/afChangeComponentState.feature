@dbFull
Feature: AF change component state interaction feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Creation of a change component state interaction scenario
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Interactions"
    And I open collapse "Modifications de l'état de composants"
    Then I should see the "actionsSetState" datagrid
  # Popup d'ajout
    When I click "Ajouter"
    Then I should see the popup "Ajout d'une action de modification de l'état d'un composant"
  # Ajout, sans rien préciser (champs présélectionnés par défaut)
    When I click "Valider"
  # TODO…


    Then the following message is shown and closed: "Ajout effectué."

  @javascript
  Scenario: Edition of a change component state interaction scenario
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Interactions"
    And I open collapse "Modifications de l'état de composants"
    Then I should see the "actionsSetState" datagrid
    And the row 1 of the "actionsSetState" datagrid should contain:
      | targetComponent                  | typeState | condition                                                   |
      | Champ numérique cible activation | Activer   | condition_elementaire_interactions_utilisee_action_setstate |
  # Modification type d'action
    When I set "Masquer" for column "typeState" of row 1 of the "actionsSetState" datagrid with a confirmation message
  # Modification condition
    And I set "condition_elementaire_interactions_utilisee_action_setvalue" for column "condition" of row 1 of the "actionsSetState" datagrid with a confirmation message
  # Vérification valeurs modifiées
    Then the row 1 of the "actionsSetState" datagrid should contain:
      | targetComponent                  | typeState | condition                                                   |
      | Champ numérique cible activation | Masquer   | condition_elementaire_interactions_utilisee_action_setvalue |



  @javascript
  Scenario: Deletion of a change component state interaction scenario
    Given I am on "af/edit/menu/id/4"
    And I wait for the page to finish loading
    And I open tab "Interactions"
    And I open collapse "Modifications de l'état de composants"
    Then I should see the "actionsSetState" datagrid
    And the "actionsSetState" datagrid should contain 1 row
  # Suppression sans obstacle
    When I click "Supprimer" in the row 1 of the "actionsSetState" datagrid
    Then I should see the popup "Demande de confirmation"
    When I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
    And the "actionsSetState" datagrid should contain 0 row
