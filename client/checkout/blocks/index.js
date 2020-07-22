/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';

/**
 * Internal dependencies
 */
import { PAYMENT_METHOD_NAME } from '../constants.js';
import { getConfig } from './../utils.js';
import WCPayAPI from './../api';
import WCPayFields from './fields.js';

// Create an API object, which will be used throughout the checkout.
const api = new WCPayAPI( {
	publishableKey: getConfig( 'publishableKey' ),
	accountId: getConfig( 'accountId' ),
} );

// Add the payment method to the blocks registry.
registerPaymentMethod(
	( PaymentMethodConfig ) => new PaymentMethodConfig( {
		name: PAYMENT_METHOD_NAME,
		content: <WCPayFields api={ api } />,
		edit: <WCPayFields api={ api } />,
		canMakePayment: () => !! api.getStripe(),
		paymentMethodId: PAYMENT_METHOD_NAME,
		label: __( 'Credit Card', 'woocommerce-payments' ),
		ariaLabel: __( 'Credit Card', 'woocommerce-payments' ),
	} )
);
