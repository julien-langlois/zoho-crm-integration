  ______     _              _____ _____  __  __   _____       _                       _   _
 |___  /    | |            / ____|  __ \|  \/  | |_   _|     | |                     | | (_)
    / / ___ | |__   ___   | |    | |__) | \  / |   | |  _ __ | |_ ___  __ _ _ __ __ _| |_ _  ___  _ __
   / / / _ \| '_ \ / _ \  | |    |  _  /| |\/| |   | | | '_ \| __/ _ \/ _` | '__/ _` | __| |/ _ \| '_ \
  / /_| (_) | | | | (_) | | |____| | \ \| |  | |  _| |_| | | | ||  __/ (_| | | | (_| | |_| | (_) | | | |
 /_____\___/|_| |_|\___/   \_____|_|  \_\_|  |_| |_____|_| |_|\__\___|\__, |_|  \__,_|\__|_|\___/|_| |_|
                                                                       __/ |
                                                                      |___/

Zoho CRM Integration uses the Zoho PHP SDK to provide integration to Zoho CRM Rest API.
You can create your custom forms and use the SDK and Rest API to communicate with your Zoho CRM account.

GETTING STARTED
---------------------

1. Access the module configuration page and follow the instructions to get your Client IDs.
   See: https://www.zoho.com/crm/developer/docs/php-sdk/clientapp.html
2. Fill all fields and give all the permissions you need on Scopes.
3. After you have saved the form with your Zoho data, get authorization by clicking on the button that will appear near the save button.
   Note: After getting an authorization if you change options like Scopes, you will need to get a new authorization.
4. On your custom module or form_alter hook, load the service zoho_crm_integration.auth provided by the module to init the SDK.
   Ex: \Drupal::service('zoho_crm_integration.auth')->initialize(); or pass it by Dependency Injection.

LINKS
---------------------
Project page: https://drupal.org/project/zoho_crm_integration
Example repository: https://github.com/adrianopulz/zoho_crm_example
Submit bug reports, feature suggestions: https://drupal.org/project/issues/zoho_crm_integration

MAINTAINERS
---------------------
adrianopulz - https://drupal.org/u/adrianopulz
leonardopost - https://drupal.org/u/leonardopost

