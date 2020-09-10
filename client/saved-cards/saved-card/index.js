/** @format */
/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import moment from 'moment';

/**
 * Internal dependencies
 */
import './style.scss';
import Chip from 'components/chip';

export const SavedCard = ( {
	paymentMethodId,
	isDefault,
	brand,
	last4,
	expiryMonth,
	expiryYear,
} ) => {
	const formattedBrand = brand.charAt( 0 ).toUpperCase() + brand.slice( 1 );
	const formattedExpiryMonth = moment()
		.month( expiryMonth - 1 )
		.format( 'MMM' );
	return (
		<div className="wcpay-saved-cards__card">
			<span
				className={ `payment-method__brand payment-method__brand--${ brand }` }
			></span>
			<span className="wcpay-saved-cards__card-info">
				<span className="wcpay-saved-cards__card-info--card">
					{ `${ formattedBrand } •••• ${ last4 }` }
				</span>
				<span className="wcpay-saved-cards__card-info--expiry">
					{ `${ __(
						'Expires',
						'woocommerce-payments'
					) } ${ formattedExpiryMonth } ${ expiryYear }` }
				</span>
			</span>
			<span className="wcpay-saved-cards__card-default">
				{ isDefault ? (
					<Chip message={ __( 'Default', 'woocommerce-payments' ) } />
				) : null }
			</span>
			<span className="wcpay-saved-cards__card-id">
				<span className="label">
					{ __( 'Card ID:', 'woocommerce-payments' ) }
				</span>{ ' ' }
				{ paymentMethodId }
			</span>
		</div>
	);
};
export default SavedCard;
