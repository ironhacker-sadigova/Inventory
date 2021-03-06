@dbFull
Feature: Input typed in data feature

  Background:
    Given I am logged in

  @javascript @readOnly
  Scenario: Correct interpretation of the difference between no input, value 0, and empty chain
    Given I am on "af/af/test/id/2"
    And I wait for the page to finish loading
  # Saisie " " dans champ obligatoire
    When I fill in "chiffre_affaire" with " "
        # Nécéssaire pour que Angular détecte le changement.
    And I click element "select[name='chiffre_affaire_unit'] [value='euro']"
    And I click element "select[name='chiffre_affaire_unit'] [value='kiloeuro']"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué, saisie incomplète. Vous pouvez renseigner les zones obligatoires manquantes maintenant ou plus tard."
    And the field "chiffre_affaire" should have error: "Champ obligatoire pour atteindre le statut : complet."
  # Saisie "0" dans champ obligatoire
    When I fill in "chiffre_affaire" with "0"
        # Nécéssaire pour que Angular détecte le changement.
    And I click element "select[name='chiffre_affaire_unit'] [value='euro']"
    And I click element "select[name='chiffre_affaire_unit'] [value='kiloeuro']"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué (saisie complète)."
  # Saisie vide dans champ obligatoire
    When I fill in "chiffre_affaire" with ""
        # Nécéssaire pour que Angular détecte le changement.
    And I click element "select[name='chiffre_affaire_unit'] [value='euro']"
    And I click element "select[name='chiffre_affaire_unit'] [value='kiloeuro']"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué, saisie incomplète. Vous pouvez renseigner les zones obligatoires manquantes maintenant ou plus tard."
    And the field "chiffre_affaire" should have error: "Champ obligatoire pour atteindre le statut : complet."

