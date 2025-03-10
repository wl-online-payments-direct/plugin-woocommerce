// eslint-disable-next-line import/no-extraneous-dependencies
import { useEffect, useState } from 'react';
import CurrencyFactory from '@woocommerce/currency';
import { __, sprintf } from '@wordpress/i18n';
import { TokenizerOptions } from './types/tokenizer';

declare let wp;
declare let wcSettings;
declare let Tokenizer;
declare let WlopHtConfig;

addEventListener( 'DOMContentLoaded', () => {
	let tokenizer;

	const HostedTokenizationForm = ( {
		eventRegistration,
		emitResponse,
		billing,
	} ) => {
		const { onPaymentSetup } = eventRegistration;
		const { responseTypes } = emitResponse;

		const [ surcharge, setSurcharge ] = useState( 0 );

		useEffect( () => {
			const options: TokenizerOptions = {
				hideCardholderName: false,
			};
			if ( WlopHtConfig.surcharge ) {
				options.surchargeCallback = ( result ) => {
					if ( result.surcharge.success ) {
						const amount =
							result.surcharge.result.surchargeAmount.amount;
						if ( ! Number.isInteger( amount ) ) {
							// eslint-disable-next-line no-console
							console.error(
								'Invalid surcharge amount received. ' + amount
							);
							return;
						}
						setSurcharge( amount );
					} else {
						// eslint-disable-next-line no-console
						console.error( result.surcharge.error );
					}
				};
			}

			tokenizer = new Tokenizer(
				WlopHtConfig.url,
				WlopHtConfig.wrapper.id,
				options
			);
			tokenizer.initialize();

			// clean up when component destroyed
			return () => {
				try {
					tokenizer.destroy();
				} catch ( err ) {
					// ignore
				}
			};
		}, [] );
		useEffect( () => {
			const decimal =
				billing.cartTotal.value / 10 ** billing.currency.minorUnit;
			const cents = Math.round(
				decimal * WlopHtConfig.currency.centFactor
			);
			tokenizer.setAmount( cents, billing.currency.code );
		}, [
			billing.cartTotal.value,
			billing.currency.code,
			billing.currency.minorUnit,
		] );

		useEffect(
			() =>
				onPaymentSetup( () => {
					async function handlePaymentProcessing() {
						const response = await tokenizer.submitTokenization();

						if ( ! response.success ) {
							return {
								type: responseTypes.ERROR,
								message: response.error.message,
							};
						}

						const htId = response.hostedTokenizationId;

						return {
							type: responseTypes.SUCCESS,
							meta: {
								paymentMethodData: {
									wlop_hosted_tokenization_id: htId,
									wlop_screen_height:
										window.screen.height.toString(),
									wlop_screen_width:
										window.screen.width.toString(),
								},
							},
						};
					}

					return handlePaymentProcessing();
				} ),
			[ onPaymentSetup, responseTypes ]
		);

		return (
			<>
				<div
					id={ WlopHtConfig.wrapper.id }
					className={ 'wlop-ht-wrapper' }
				></div>
				<SurchargeNote surcharge={ surcharge } />
			</>
		);
	};

	const SurchargeNote = ( { surcharge } ) => {
		if ( surcharge <= 0 ) {
			return null;
		}

		const currencyFactory = CurrencyFactory( wcSettings.currency );
		const formattedSurcharge = currencyFactory.formatAmount(
			surcharge / WlopHtConfig.currency.centFactor
		);

		return (
			<div className={ 'wlop-surcharge-note' }>
				{ sprintf(
					/**
					 * translators: %s the surcharge amount, like $0.23
					 */
					__(
						'Includes surcharge of %s',
						'worldline-for-woocommerce'
					),
					formattedSurcharge
				) }
			</div>
		);
	};

	wp.hooks.addFilter(
		WlopHtConfig.gateway.id + '_checkout_fields',
		'wlop/ht/checkout',
		( components ) => {
			components.push( HostedTokenizationForm );

			return components;
		}
	);
} );
