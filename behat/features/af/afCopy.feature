@dbFull
Feature: AF copy feature

  Background:
    Given I am logged in

  @javascript
  Scenario: Copy of the combustion form, correct input, and test of the copied form scenario
    Given I am on "af/library/view/id/1"
    And I wait for the page to finish loading
    Then I should see the "listAF" datagrid
    And the "listAF" datagrid should contain 8 row
    And the row 1 of the "listAF" datagrid should contain:
      | label                                               |
      | Combustion de combustible, mesuré en unité de masse |
    When I click "Dupliquer" in the row 1 of the "listAF" datagrid
    Then I should see the popup "Libellé et identifiant du nouveau formulaire (copie)"
    When I fill in "label" with "Copie combustion de combustible, mesuré en unité de masse"
    And I click element "#submit:contains('Dupliquer')"
    Then the following message is shown and closed: "Ajout effectué"
    And the "listAF" datagrid should contain 9 row
    And the row 9 of the "listAF" datagrid should contain:
      | label                                                     |
      | Copie combustion de combustible, mesuré en unité de masse |
    When I click "Test" in the row 9 of the "listAF" datagrid
    And I fill in "quantite_combustible" with "10"
    And I click element "select[name='nature_combustible'] [value='0']"
  # Formulaire copié : aperçu des résultats
    And I click "Aperçu des résultats"
    And I wait 5 seconds
    Then I should see "Total : 33,3 t équ. CO2"
  # Formulaire copié : enregistrement de la saisie
    When I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué (saisie complète)."
  # Formulaire copié : accès détails calculs et vérification calculs corrects
    When I open tab "Détails calculs"
    And I open collapse "Formulaire maître"
    Then I should see "emissions_combustion Émissions liées à la combustion"
    When I open collapse "emissions_amont"
    Then I should see "Type : Expression"
    And I should see "quantite_combustible * fe_amont"
    Then I should see "Valeur : 2,54 t équ. CO2 ± 20 %"

  @javascript @readOnly
  Scenario: Copy of the combustion form, incorrect input
    Given I am on "af/library/view/id/1"
    And I wait for the page to finish loading
  # Essai de duplication avec libellé vide
    And I click "Dupliquer" in the row 1 of the "listAF" datagrid
    And I click element "#submit:contains('Dupliquer')"
    Then the following message is shown and closed: "Merci de renseigner ce champ."

  @javascript
  Scenario: Copy of the forfait emission fonction marque form and test of the copied form scenario
    Given I am on "af/library/view/id/1"
    And I wait for the page to finish loading
    Then I should see the "listAF" datagrid
    And the "listAF" datagrid should contain 8 row
    And the row 8 of the "listAF" datagrid should contain:
      | label                                      |
      | Forfait émissions en fonction de la marque |
    When I click "Dupliquer" in the row 8 of the "listAF" datagrid
    Then I should see the popup "Libellé et identifiant du nouveau formulaire (copie)"
    When I fill in "label" with "Copie forfait émissions en fonction de la marque"
    And I click element "#submit:contains('Dupliquer')"
    Then the following message is shown and closed: "Ajout effectué"
    And the "listAF" datagrid should contain 9 row
    And the row 9 of the "listAF" datagrid should contain:
      | label                                            |
      | Copie forfait émissions en fonction de la marque |
    When I click "Test" in the row 9 of the "listAF" datagrid
  # Saisie et enregistrement
    When I fill in "sans_effet" with "0"
    # Nécéssaire pour que Angular détecte le changement.
    And I click element "select[name='sans_effet_unit'] [value='euro']"
    And I click element "select[name='sans_effet_unit'] [value='kiloeuro']"
    And I click "Enregistrer"
    And I open tab "Résultats"
    Then I should see "Total : 1 t équ. CO2"
  # Détails calculs
    When I open tab "Détails calculs"
    And I open collapse "Formulaire maître"
    And I open collapse "algo_numerique_forfait_marque"
    Then I should see "Marque : marque A"

  @javascript
  Scenario: Copy of the formulaire avec tout type de champ form and test of the copied form scenario
    Given I am on "af/library/view/id/1"
    And I wait for the page to finish loading
    Then I should see the "listAF" datagrid
    And the "listAF" datagrid should contain 8 row
    And the row 5 of the "listAF" datagrid should contain:
      | label                              |
      | Formulaire avec tout type de champ |
    When I click "Dupliquer" in the row 5 of the "listAF" datagrid
    Then I should see the popup "Libellé et identifiant du nouveau formulaire (copie)"
    When I fill in "label" with "Copie formulaire avec tout type de champ"
    And I click element "#submit:contains('Dupliquer')"
    Then the following message is shown and closed: "Ajout effectué"
    And the "listAF" datagrid should contain 9 row
    And the row 9 of the "listAF" datagrid should contain:
      | label                                    |
      | Copie formulaire avec tout type de champ |
    When I click "Test" in the row 9 of the "listAF" datagrid
    And I fill in "c_n" with "10"
    And I select "kg_co2e.bl^-1" from "c_n_unit"
    And I select "Option 1" from "c_s_s_liste"
    # On est obligé de passer par "click" à cause d'Angular :(
    And I click element "[name='c_s_s_bouton'][value='opt_2']"
    And I click element "[name='c_s_m_checkbox'][value='opt_3']"
    And I click element "[name='c_s_m_liste'][value='opt_4']"
    And I check "c_b"
    And I fill in "c_t_c" with "Bla"
    And I fill in "c_t_l" with "BlaBla"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué (saisie complète)."

  @javascript
  Scenario: Copy of the formulaire avec sous-formulaire repete contenant tout type de champ form and test of the copied form scenario
    Given I am on "af/library/view/id/1"
    And I wait for the page to finish loading
    Then I should see the "listAF" datagrid
    And the "listAF" datagrid should contain 8 row
    And the row 6 of the "listAF" datagrid should contain:
      | label                                                               |
      | Formulaire avec sous-formulaire répété contenant tout type de champ |
    When I click "Dupliquer" in the row 6 of the "listAF" datagrid
    Then I should see the popup "Libellé et identifiant du nouveau formulaire (copie)"
    When I fill in "label" with "Copie formulaire avec sous-formulaire répété contenant tout type de champ"
    And I click element "#submit:contains('Dupliquer')"
    Then the following message is shown and closed: "Ajout effectué"
    And the "listAF" datagrid should contain 9 row
    And the row 9 of the "listAF" datagrid should contain:
      | label                                                                     |
      | Copie formulaire avec sous-formulaire répété contenant tout type de champ |
    When I click "Test" in the row 9 of the "listAF" datagrid
    And I click "Ajouter"
    And I click "Ajouter"
    And I fill in "s_f_r_t_t_c__1__c_n" with "10"
    And I select "kg_co2e.bl^-1" from "s_f_r_t_t_c__1__c_n_unit"
    And I select "Option 1" from "s_f_r_t_t_c__1__c_s_s_liste"
    # On est obligé de passer par "click" à cause d'Angular :(
    And I click element "[name='s_f_r_t_t_c__1__c_s_s_bouton'][value='opt_2']"
    And I click element "[name='s_f_r_t_t_c__1__c_s_m_checkbox'][value='opt_3']"
    And I click element "[name='s_f_r_t_t_c__1__c_s_m_liste'][value='opt_4']"
    And I check "s_f_r_t_t_c__1__c_b"
    And I fill in "s_f_r_t_t_c__1__c_t_c" with "Bla"
    And I fill in "s_f_r_t_t_c__1__c_t_l" with "BlaBla"
    And I click "Enregistrer"
    Then the following message is shown and closed: "Enregistrement effectué, saisie incomplète. Vous pouvez renseigner les zones obligatoires manquantes maintenant ou plus tard."
