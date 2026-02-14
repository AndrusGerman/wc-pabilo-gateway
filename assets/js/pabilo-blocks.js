/**
 * Pabilo Payment Method for WooCommerce Blocks
 */
const settings = window.wc.wcSettings.getSetting( 'pabilo_gateway_data', {} );
const label = settings.title || 'Pago Móvil / Transferencia (Pabilo)';
const Content = () => {
    return window.wp.element.createElement(
        'div',
        { className: 'wc-block-components-payment-method-content' },
        settings.description || ''
    );
};

const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    
    // Create an image element if the icon is present
    const iconElement = settings.icon ? window.wp.element.createElement( 'img', {
        src: settings.icon,
        alt: label,
        style: { marginRight: '10px', verticalAlign: 'middle', maxHeight: '24px' }
    } ) : null;
    
    // We can just render a span with the icon (if any) and text
    return window.wp.element.createElement(
        'span',
        { style: { display: 'flex', alignItems: 'center', width: '100%' } },
        iconElement,
        label
    );
};

const PabiloPaymentMethod = {
    name: 'pabilo_gateway',
    label: window.wp.element.createElement( Label, null ),
    content: window.wp.element.createElement( Content, null ),
    edit: window.wp.element.createElement( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports || []
    }
};

window.wc.wcBlocksRegistry.registerPaymentMethod( PabiloPaymentMethod );
