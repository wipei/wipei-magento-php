# Wipei - Magento 2 module

### Summary
[Wipei](https://www.wipei.com.ar/) integration for Magento 2.
This module allow an integration with Wipei payment method for any Magento 2 store in Argentina.
### Requirements
- Magento 2 installation
  - Tested on version 2.2.x
- Composer
### Setup

### Configuration
 After the module installation, it must be configured. To achieve this, an administrator user should go to:
 
**Stores > Configuration > Sales > Payment Methods**

There are some topics to be configured:
- *Credentials*
  The credentials must be obtained from [Wipei](https://www.wipei.com.ar/sellers.html/) administrators.
- *Flow type*
  Currently the only allowed flow is **redirect**. A modal one will be a second option.
- *Order status*
  The status set by Wipei after a payment interaction.
  
### Use
Wipei payment will be negotiated after an order submit and it will notify the store through the order status.
