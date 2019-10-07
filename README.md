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
  The checkout could be: **redirect** or **modal**. Redirect option will redirect the magento checkout page to wipei. Modal option will show an iframe over the checkout payment page. Both options will redirect in case of fail, cancel or success.
- *Order status*
  The status set by Wipei after a payment interaction.
  
### Use
Wipei payment will be negotiated after an order submit and it will notify the store through the order status.

# Español

### Resumen
Integración de [Wipei](https://www.wipei.com.ar/) para Magento 2.
Este módulo permite el uso del método de pago Wipei para cualquier tienda Magento 2 en Argentina.

### Requerimientos
- Magento 2
  - Probado en versión 2.2.x

### Instalación
El módulo debe sera agregado en ./app/code/Wipei/WipeiPayment y habilitarlo

module:enable ...
setup:upgrade
setup:di:compile
cache:clean

### Configuración
Luego de la instalación del módulo, se lo debe configurar. Para hacer esto, un administrador tiene que ir a:
 
**Stores > Configuration > Sales > Payment Methods**

Hay dos ítems principales a configurar:
- *Credenciales*
  Las credenciales deben ser obtenidas de los administradores de [Wipei](https://www.wipei.com.ar/sellers.html/).
- *Tipo de checkout*
  El checkout puede ser de dos tipos: **redirect** o **modal**. El tipo redirect redirige a la ventana donde se está realizando el pago, mientras que el modal lanza un iframe en forma de modal, sin abandonar la página de Magento. Ambas opciones van a redireccionar a Magento en caso de cancelación, falla o éxito.
- *Estado de órdenes*
  El estado al que pasarán las órdenes luego de ser procesadas por Wipei.
  
### Uso
El método de pago de Wipei va a ser invocado cuando se cierre el carrito de compra. Luego se negociará el pago en la web de Wipei y, dependiendo del resultado, se cambiará el estado de la orden.
El resultado del pago puede ser de 3 tipos: Pendiente, Cancelado o Aprobado. El estado Pendiente es cuando una órden espera el procesamiento del pago, y si el mismo se concreta pasa al estado Aprobado. en caso de que no se concrete, el estado pasa de pendiente a Cancelado.
