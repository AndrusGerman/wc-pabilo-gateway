=== Pasarela de Pago Pabilo for WooCommerce ===
Contributors: Pabilo
Donate link: https://pabilo.app
Tags: woocommerce, payment gateway, venezuela, banco de venezuela, mercantil, banesco, provincial, bolivares, pago movil, transferencia
Requires at least: 5.0
Tested up to: 6.9
Stable tag: 1.0.4
Requires PHP: 7.4
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Acepta pagos vía Pago Móvil y Transferencia Bancaria desde bancos de Venezuela (Banco de Venezuela, Mercantil, Banesco, Provincial) de forma fácil y segura usando Pabilo.

== Description ==

La Pasarela de Pago Pabilo permite a las tiendas WooCommerce aceptar pagos de los principales bancos de Venezuela a través de un proceso simplificado y verificado.

Este plugin actúa como interfaz para el servicio de pagos [Pabilo](https://pabilo.app). Al configurar la API Key, consientes el envío de datos de orden (monto, descripción, ID de orden) al servicio Pabilo para generar enlaces de pago y verificar transacciones. Consulta los [Términos de Uso de Pabilo](https://pabilo.app/terms) para más información sobre el tratamiento de datos.

Simplemente configura tu API Key, selecciona tu cuenta bancaria receptora y comienza a aceptar pagos vía:

*   **Pago Móvil**
*   **Transferencia Bancaria**

Bancos soportados:
*   Banco de Venezuela
*   Mercantil
*   Banesco
*   Provincial
*   Y otros bancos locales soportados por Pabilo.

El plugin genera un enlace de pago seguro para cada orden y verifica el pago automáticamente vía webhooks (con validación ante la API de Pabilo), asegurando una experiencia de compra fluida para tus clientes.

== Installation ==

1.  Sube los archivos del plugin al directorio `/wp-content/plugins/pabilo-payment-gateway-for-woocommerce`, o instálalo directamente desde la pantalla de plugins de WordPress.
2.  Activa el plugin desde la pantalla de 'Plugins' en WordPress.
3.  Ve a WooCommerce > Ajustes > Pagos > Pasarela de Pago Pabilo para configurarlo.
4.  Ingresa tu **API Key** obtenida de tu panel de Pabilo.
5.  Selecciona la cuenta bancaria donde deseas recibir los fondos.

== Frequently Asked Questions ==

= ¿Necesito una cuenta en Pabilo? =

Sí, necesitas una cuenta registrada en [pabilo.app](https://pabilo.app) para obtener tus credenciales API.

= ¿Qué bancos son soportados? =

Soportamos transferencias y pago móvil de los principales bancos de Venezuela, incluyendo Banco de Venezuela, Mercantil, Banesco y Provincial.

== Screenshots ==

1.  Página de configuración del plugin en WooCommerce.
2.  Opción de pago en el checkout.

== Changelog ==

= 1.0.4 =
*   Nueva funcionalidad: Soporte nativo para WooCommerce Blocks Checkout.
*   Seguridad: Verificación mejorada de webhooks (comprobación de ID de Link de Pago).
*   Seguridad: Validación de propiedad del link de pago contra la cuenta de administrador.
*   Corrección: Prevenido error fatal en Blocks checkout durante el procesamiento del pago.

= 1.0.3 =
*   Verificación de webhook ante la API de Pabilo antes de marcar pago como completado (mitigación de seguridad).
*   Compatibilidad con WordPress.org: Requires Plugins, Requires PHP, Tested up to 6.9.
*   Documentación de privacidad y enlace a Términos de Uso de Pabilo.

= 1.0.2 =
*   Traducción completa al español.
*   Mención explícita de Pago Móvil y Transferencia.

= 1.0.1 =
*   Mejoras en descripciones e iconos.
*   Lógica de verificación de webhook actualizada.

= 1.0.0 =
*   Lanzamiento inicial.
