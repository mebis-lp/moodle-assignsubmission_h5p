@assignsubmission @assignsubmission_h5p
Feature: Basic tests for H5P Submission

  @javascript
  Scenario: Plugin assignsubmission_h5p appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "H5P Submission"
    And I should see "assignsubmission_h5p"
