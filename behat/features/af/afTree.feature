@dbFull
Feature: AF tree edit feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Creation of an AF category
    Given I am on "af/af/tree"
    And I wait for the page to finish loading
  # Attente pour fonctionnement test sur serveur dédié
    And I wait 5 seconds
  # Ajout d'une catégorie, libellé vide
    When I click "Ajouter une catégorie"
    Then I should see the popup "Ajout d'une catégorie"
    When I click "Valider"
    Then the field "label" should have error: "Merci de renseigner ce champ."
  # Ajout d'une catégorie, libellé non vide
    When I fill in "label" with "Test"
    And I click "Valider"
    Then the following message is shown and closed: "Ajout effectué."
  # Ajout d'une catégorie, libellé non vide, située dans une autre catégorie
  # TODO : permettre l'ajout d'une catégorie dans une autre catégorie.

  @javascript
  Scenario: Edition of an AF category
    Given I am on "af/af/tree"
    And I wait 7 seconds
  # Ouverture popup modification
    When I click "Catégorie vide"
    Then I should see the popup "Édition d'une catégorie"
  # Modification du libellé, libellé vide
    When I fill in "afTree_labelEdit" with ""
    And I click "Confirmer"
    Then the field "afTree_labelEdit" should have error: "Merci de renseigner ce champ."
  # Modification du libellé, saisie correcte
    When I fill in "afTree_labelEdit" with "Catégorie vide modifiée"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
    And I should see "Catégorie vide modifiée"
  # Déplacement dans une autre catégorie
    When I click "Catégorie vide modifiée"
    Then I should see the popup "Édition d'une catégorie"
    When I select "Catégorie contenant une sous-catégorie" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement à la racine
    When I wait 5 seconds
    And I click "Catégorie vide modifiée"
    And I select "Aucun" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement en premier
    When I click "Catégorie vide modifiée"
    And I check "Premier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement après une autre catégorie
    When I click "Catégorie vide modifiée"
    And I check "Après"
    And I select "Catégorie contenant une sous-catégorie" from "afTree_selectAfter"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement en dernier
    When I click "Catégorie vide modifiée"
    And I check "Dernier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."

  @javascript
  Scenario:  Deletion of an AF category
    Given I am on "af/af/tree"
    And I wait 7 seconds
  # Catégorie contenant une sous-catégorie
    And I click "Catégorie contenant une sous-catégorie"
    Then I should see the popup "Édition d'une catégorie"
    When I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Cette catégorie ne peut pas être supprimée, car elle n'est pas vide (elle contient au moins un formulaire ou une autre catégorie)."
    And I should see "Catégorie contenant une sous-catégorie"
  # Catégorie contenant un formulaire
    When I click "Catégorie contenant un formulaire"
    Then I should see the popup "Édition d'une catégorie"
    When I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Cette catégorie ne peut pas être supprimée, car elle n'est pas vide (elle contient au moins un formulaire ou une autre catégorie)."
    And I should see "Catégorie contenant un formulaire"
  # Catégorie vide
    When I click "Catégorie vide"
    Then I should see the popup "Édition d'une catégorie"
    When I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
    And I should not see "Catégorie vide"


  @javascript
  Scenario: Edition of an AF in AF tree edit
    Given I am on "af/af/tree"
    And I wait 7 seconds
  # Modification du libellé, libellé vide
    When I click "Combustion de combustible, mesuré en unité de masse"
    And I fill in "afTree_labelEdit" with ""
    And I click "Confirmer"
    Then the field "afTree_labelEdit" should have error: "Merci de renseigner ce champ."
  # Modification du libellé, libellé non vide
    When I fill in "afTree_labelEdit" with "Combustion (modifiée)"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement dans une autre catégorie
    When I wait 5 seconds
    And I click "Combustion (modifiée)"
    And I select "Catégorie contenant une sous-catégorie" from "afTree_changeParent"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement en premier
    When I wait 5 seconds
    And I click "Formulaire test"
    And I check "Premier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement après
    When I wait 5 seconds
    And I click "Formulaire test"
    And I check "Après"
    And I select "Données générales" from "afTree_selectAfter"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."
  # Déplacement en dernier
    When I wait 5 seconds
    And I click "Formulaire test"
    And I check "Premier"
    And I click "Confirmer"
    Then the following message is shown and closed: "Modification effectuée."

  @javascript
  Scenario: Deletion of an AF in AF tree edit, forbidden
    Given I am on "af/af/tree"
    And I wait 7 seconds
  # Suppression, formulaire utilisé comme sous-formulaire (non répété)
    When I click "	Données générales"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Ce formulaire ne peut pas être supprimé, car il est appelé en tant que sous-formulaire par un autre formulaire."
  # Suppression, formulaire utilisé comme sous-formulaire (répété)
    When I click "Combustion de combustible, mesuré en unité de masse"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Ce formulaire ne peut pas être supprimé, car il est appelé en tant que sous-formulaire par un autre formulaire."

  @javascript
  Scenario: Deletion of an AF in AF tree edit, authorized
    Given I am on "af/af/tree"
    And I wait 7 seconds
  # Suppression sans obstacle, formulaire vide
    When I click "Formulaire vide"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # Suppression sans obstacle, "Formulaire test"
    When I click "Formulaire test"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # Suppression sans obstacle, "Formulaire avec sous-formulaires"
    When I click "Formulaire avec sous-formulaires"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # Suppression sans obstacle, "Données générales"
    When I click "Données générales"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # Suppression sans obstacle, "Combustion de combustible, mesuré en unité de masse"
    When I click "Combustion de combustible, mesuré en unité de masse"
    And I click "Supprimer"
    Then I should see the popup "Demande de confirmation"
    And I click "Confirmer"
    Then the following message is shown and closed: "Suppression effectuée."
  # Vérification suppression effectuée
    When I wait 5 seconds
    Then I should see "Données générales"
    And I should not see "Combustion de combustible, mesuré en unité de masse"
    And I should not see "Données générales"
    And I should not see "Formulaire avec sous-formulaires"
    And I should not see "Formulaire test"
    And I should not see "Formulaire vide"

  @javascript
  Scenario: Link towards configuration view, from AF tree edit
    Given I am on "af/af/tree"
    And I wait 7 seconds
    When I click "Combustion de combustible, mesuré en unité de masse"
    And I click "Configuration"
  # Vérification qu'on est bien sur la page "Configuration"
    And I open tab "Contrôle"
    Then I should see "Combustion de combustible, mesuré en unité de masse"

  @javascript
  Scenario: Link towards test view, from AF tree edit
    Given I am on "af/af/tree"
    And I wait 7 seconds
    When I click "Combustion de combustible, mesuré en unité de masse"
    And I click "Test"
  # Vérification qu'on est bien sur la page "Test"
    Then I should see "Nature du combustible"