import {sprintf, __} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';

console.log('global settings', inpsydeGateways)

inpsydeGateways.forEach((name) => {
    const settings = getSetting(`${name}_data`, {});
    console.log('gateway settings:' + name, settings)

    const defaultLabel = __(
        'Inpsyde Dummy Payments',
        'woo-gutenberg-products-block'
    );

    const label = decodeEntities(settings.title) || defaultLabel;
    /**
     * Content component
     */
    const Content = () => {
        return decodeEntities(settings.description || '');
    };
    /**
     * Label component
     *
     * @param {*} props Props from payment API.
     */
    const Label = (props) => {
        const {PaymentMethodLabel} = props.components;
        return <PaymentMethodLabel text={label} />;
    };

    /**
     * Dummy payment method config object.
     */
    const Dummy = {
        name: name,
        label: <Label />,
        content: <Content />,
        edit: <Content />,
        canMakePayment: () => true,
        ariaLabel: label,
        supports: {
            features: settings.supports,
        },
    };

    if (settings.placeOrderButtonLabel) {
        Dummy.placeOrderButtonLabel = settings.placeOrderButtonLabel;
    }

    registerPaymentMethod(Dummy);

})

