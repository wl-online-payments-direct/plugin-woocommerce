// eslint-disable-next-line import/no-extraneous-dependencies
import { useEffect, useRef, useState } from 'react';
import CurrencyFactory from '@woocommerce/currency';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { TokenizerOptions } from './types/tokenizer';
import { SURCHARGE_STORE_KEY, SurchargeStore } from './surcharge-store';

declare let wp;
declare let wc;
declare let wcSettings;
declare let Tokenizer;
declare let WlopHtConfig;

addEventListener( 'DOMContentLoaded', () => {
	const HostedTokenizationForm = ( {
		eventRegistration,
		emitResponse,
		billing,
		token,
	} ) => {
		const { onPaymentSetup } = eventRegistration;
		const { responseTypes } = emitResponse;

		const surcharge = useSelect(
			( select ) =>
				(
					select( SURCHARGE_STORE_KEY ) as SurchargeStore
				 ).getSurcharge(),
			[]
		);
		const { setSurcharge } = useDispatch( SURCHARGE_STORE_KEY );

		const [ initialized, setInitialized ] = useState( false );

		const tokenizer = useRef< typeof Tokenizer >( null );

		useEffect( () => {
			setInitialized( false );

			const options: TokenizerOptions = {
				hideCardholderName: false,
				hideTokenFields: false,
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

			tokenizer.current = new Tokenizer(
				WlopHtConfig.url,
				WlopHtConfig.wrapper.id,
				options
			);
			tokenizer.current.initialize().then( () => setInitialized( true ) );

			// clean up when component destroyed
			return () => {
				try {
					setInitialized( false );
					tokenizer.current.destroy();
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
			tokenizer.current.setAmount( cents, billing.currency.code );
		}, [
			billing.cartTotal.value,
			billing.currency.code,
			billing.currency.minorUnit,
		] );

		useEffect( () => {
			if ( ! initialized ) {
				return;
			}

			if ( token ) {
				const tokenKey = WlopHtConfig.tokens[ token ];
				tokenizer.current.useToken( tokenKey );
			} else {
				tokenizer.current.useToken();
			}
		}, [ token, initialized ] );

		useEffect(
			() =>
				onPaymentSetup( () => {
					async function handlePaymentProcessing() {
						const response =
							await tokenizer.current.submitTokenization();

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
									wlop_color_depth:
										window.screen.colorDepth.toString(),
									wlop_java_enabled:
										navigator.javaEnabled().toString(),
									wlop_timezone_offset:
										new Date().getTimezoneOffset().toString()
								},
							},
						};
					}

					return handlePaymentProcessing();
				} ),
			[ onPaymentSetup, responseTypes ]
		);

		useEffect( () => {
			return () => {
				setSurcharge( 0 );
			};
		}, [ setSurcharge ] );

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
	wp.hooks.addFilter(
		WlopHtConfig.gateway.id + '_saved_token_fields',
		'wlop/ht/checkout',
		( components ) => {
			components.push( HostedTokenizationForm );

			return components;
		}
	);
} );

addEventListener( 'DOMContentLoaded', () => {
	const ExperimentalOrderMeta = wc.blocksCheckout.ExperimentalOrderMeta;
	const { registerPlugin } = wp.plugins;

	const OrderSummarySurcharge = ( { cart } ) => {
		const surcharge = useSelect(
			( select ) =>
				(
					select( SURCHARGE_STORE_KEY ) as SurchargeStore
				 ).getSurcharge(),
			[]
		);

		if ( ! cart?.cartTotals || ! surcharge ) {
			return null;
		}

		const cartTotal = parseFloat( cart.cartTotals.total_price );
		const totalWithSurcharge = cartTotal + surcharge;
		const currencyFactory = CurrencyFactory( wcSettings.currency );

		const formattedSurcharge = currencyFactory.formatAmount(
			surcharge / WlopHtConfig.currency.centFactor
		);
		const formattedTotalWithSurcharge = currencyFactory.formatAmount(
			totalWithSurcharge / WlopHtConfig.currency.centFactor
		);

		return (
			<>
				<div className="wp-block-woocommerce-checkout-order-summary-fee-block wc-block-components-totals-wrapper">
					<div className="wc-block-components-totals-item">
						<span className="wc-block-components-totals-item__label">
							{ __( 'Surcharge', 'worldline-for-woocommerce' ) }
						</span>
						<span className="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-item__value">
							{ formattedSurcharge }
						</span>
						<div className="wc-block-components-totals-item__description"></div>
					</div>
				</div>

				<div className="wc-block-components-totals-wrapper">
					<div className="wc-block-components-totals-item wc-block-components-totals-footer-item">
						<span className="wc-block-components-totals-item__label">
							{ __( 'Total With Surcharge', 'worldline-for-woocommerce' ) }
						</span>
						<div className="wc-block-components-totals-item__value">
							<span className="wc-block-formatted-money-amount wc-block-components-formatted-money-amount wc-block-components-totals-footer-item-tax-value">
								{ formattedTotalWithSurcharge }
							</span>
						</div>
						<div className="wc-block-components-totals-item__description"></div>
					</div>
				</div>
			</>
		);
	};

	const RenderOrderSummarySurcharge = () => {
		return (
			<ExperimentalOrderMeta>
				{ /*// @ts-ignore*/ }
				<OrderSummarySurcharge />
			</ExperimentalOrderMeta>
		);
	};

	registerPlugin( 'wlop-ht-surcharge-summary', {
		scope: 'woocommerce-checkout',
		render: RenderOrderSummarySurcharge,
	} );
} );
