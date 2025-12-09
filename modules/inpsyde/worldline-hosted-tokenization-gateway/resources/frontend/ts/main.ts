import { getCurrentPaymentMethod } from './utils/woo-classic-checkout';
import { setVisible } from '#shared/utils/visibility';
import CurrencyFactory from '@woocommerce/currency';
import { __, sprintf } from '@wordpress/i18n';
import { TokenizerOptions } from './types/tokenizer';

/// <reference types="jquery" />
/// <reference types="jquery.blockui" />

declare let Tokenizer;
declare let WlopHtConfig;
addEventListener( 'DOMContentLoaded', () => {
	let tokenizer;
	let initialized: boolean = false;
	let lastSurchargeAmount = 0;

	const tokenRadiosSelector =
		'.payment_method_worldline-hosted-tokenization input.woocommerce-SavedPaymentMethods-tokenInput';
	const orderSummaryTableSelector =
		'.shop_table.woocommerce-checkout-review-order-table';
	let originalOrderSummaryTableHTML = '';

	const storeOriginalOrderSummaryTable = () => {
		const orderSummaryTable = document.querySelector(
			orderSummaryTableSelector
		);
		if ( orderSummaryTable ) {
			originalOrderSummaryTableHTML = orderSummaryTable.innerHTML;
		}
	};

	const restoreOriginalOrderSummaryTable = () => {
		if ( ! originalOrderSummaryTableHTML ) {
			return;
		}
		const table = document.querySelector( orderSummaryTableSelector );
		if ( table ) {
			table.innerHTML = originalOrderSummaryTableHTML;
		}
	};

	const applySurchargeToOrderSummaryTable = ( surcharge: number ) => {
		const table = document.querySelector( orderSummaryTableSelector );
		if ( ! table ) {
			return;
		}

		const surchargeValue = surcharge / WlopHtConfig.currency.centFactor;

		const tfoot = table.querySelector( 'tfoot' );
		if ( ! tfoot ) {
			return;
		}

		const baseTotalValue =
			WlopHtConfig.total / WlopHtConfig.currency.centFactor;
		const newTotalValue = baseTotalValue + surchargeValue;
		const currencyFactory = CurrencyFactory( WlopHtConfig.currency );
		const formattedNewTotal = currencyFactory.formatAmount( newTotalValue );
		const formattedSurcharge =
			currencyFactory.formatAmount( surchargeValue );

		let surchargeRow = tfoot.querySelector( '.wlop-order-surcharge' );
		if ( ! surchargeRow ) {
			surchargeRow = document.createElement( 'tr' );
			surchargeRow.classList.add( 'wlop-order-surcharge' );
			surchargeRow.innerHTML = `
				<th>${ __( 'Surcharge', 'worldline-for-woocommerce' ) }</th>
				<td>
					<strong>
						<span class="woocommerce-Price-amount amount"><bdi>${ formattedSurcharge }</bdi></span>
					</strong>
				</td>
			`;
			const orderTotalRow = tfoot.querySelector( '.order-total' );
			if ( orderTotalRow ) {
				tfoot.insertBefore( surchargeRow, orderTotalRow );
			} else {
				tfoot.appendChild( surchargeRow );
			}
		} else {
			const surchargeAmountEl = surchargeRow.querySelector(
				'.woocommerce-Price-amount bdi'
			);
			if ( surchargeAmountEl ) {
				surchargeAmountEl.textContent = formattedSurcharge;
			}
		}

		const totalEl = tfoot.querySelector(
			'.order-total .woocommerce-Price-amount bdi'
		);
		if ( totalEl ) {
			totalEl.textContent = formattedNewTotal;
		}
	};

	const iframeWrapper = (): HTMLElement | null => {
		return document.querySelector( '#' + WlopHtConfig.wrapper.id );
	};

	function initTokenizer() {
		const wrapper = iframeWrapper();

		if ( ! wrapper ) {
			return;
		}
		if ( tokenizer ) {
			tokenizer.destroy();
		}

		wrapper.innerHTML = '';

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
					}
					lastSurchargeAmount = amount;
					updateSurcharge( amount );
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
	}

	const htWrapper = iframeWrapper();
	if ( ! htWrapper ) {
		return;
	}

	const updateVisibility = () => {
		const wrapper = iframeWrapper();
		if ( ! wrapper ) {
			return;
		}

		const isHTGateway =
			getCurrentPaymentMethod() === WlopHtConfig.gateway.id;
		setVisible( wrapper, isHTGateway );

		if ( ! isHTGateway ) {
			restoreOriginalOrderSummaryTable();
		} else if ( lastSurchargeAmount > 0 ) {
			applySurchargeToOrderSummaryTable( lastSurchargeAmount );
		}
	};

	const updateAmount = () => {
		if ( ! tokenizer || ! initialized ) {
			return;
		}

		tokenizer.setAmount( WlopHtConfig.total, WlopHtConfig.currency.code );
	};

	const updateSurcharge = ( surcharge: number ) => {
		if ( ! WlopHtConfig.surcharge ) {
			return;
		}
		const element = document.getElementById(
			WlopHtConfig.surcharge.wrapper.id
		);
		if ( ! element ) {
			return;
		}

		if ( surcharge <= 0 ) {
			element.innerHTML = '';
			restoreOriginalOrderSummaryTable();
			return;
		}

		const currencyFactory = CurrencyFactory( WlopHtConfig.currency );
		const formattedSurcharge = currencyFactory.formatAmount(
			surcharge / WlopHtConfig.currency.centFactor
		);

		element.innerHTML = sprintf(
			/**
			 * translators: %s the surcharge amount, like $0.23
			 */
			__( 'Includes surcharge of %s', 'worldline-for-woocommerce' ),
			formattedSurcharge
		);

		if ( getCurrentPaymentMethod() === WlopHtConfig.gateway.id ) {
			applySurchargeToOrderSummaryTable( surcharge );
		} else {
			restoreOriginalOrderSummaryTable();
		}
	};

	const initIframe = async () => {
		const wrapper = iframeWrapper();
		if ( ! wrapper ) {
			throw new Error( 'Hosted tokenization iframe wrapper not found.' );
		}
		if ( wrapper.childNodes.length > 0 ) {
			// Already rendered.
			return;
		}

		const methodsElement = wrapper.closest(
			'.wc_payment_methods'
		) as HTMLElement | null;
		const columnWidth = methodsElement?.offsetWidth;
		if ( columnWidth && columnWidth > 0 && columnWidth < 450 ) {
			wrapper.classList.add( 'wlop-ht-narrow' );
		}

		if ( initialized ) {
			try {
				tokenizer.destroy();
			} catch ( err ) {
				// ignore
			}
		}

		await tokenizer.initialize();

		setupButtonHandler();

		updateVisibility();

		initialized = true;

		updateAmount();

		const tokenRadios = document.querySelectorAll( tokenRadiosSelector );
		tokenRadios.forEach( ( chk ) => {
			chk.addEventListener( 'click', updateTokenState );
		} );
		updateTokenState();
	};

	const resetIframe = async () => {
		[
			'wlop_hosted_tokenization_id',
			'wlop_screen_height',
			'wlop_screen_width',
			'wlop_color_depth',
			'wlop_java_enabled',
			'wlop_timezone_offset',
		].forEach( ( name ) => {
			document.querySelector( `[name="${ name }"]` )?.remove();
		} );
		initTokenizer();
		await initIframe();
	};

	const setupButtonHandler = () => {
		const placeOrderButton = document.querySelector(
			'#place_order'
		) as HTMLButtonElement | null;
		if ( ! placeOrderButton ) {
			throw new Error( 'Place order button not found.' );
		}

		if ( placeOrderButton.dataset.wlopHtHandlerAdded ) {
			return;
		}

		let canSubmit = false;
		placeOrderButton.dataset.wlopHtHandlerAdded = 'true';

		placeOrderButton.addEventListener( 'click', async ( e ) => {
			if ( canSubmit ) {
				return;
			}
			if ( ! initialized ) {
				return;
			}
			if ( getCurrentPaymentMethod() !== WlopHtConfig.gateway.id ) {
				return;
			}

			e.preventDefault();
			e.stopImmediatePropagation();

			const response = await tokenizer.submitTokenization();

			if ( response.success ) {
				const hostedTokenizationId = response.hostedTokenizationId;

				placeOrderButton.insertAdjacentHTML(
					'afterend',
					`<input type="hidden" name="wlop_hosted_tokenization_id" value="${ hostedTokenizationId }"/>`
				);

				placeOrderButton.insertAdjacentHTML(
					'afterend',
					`<input type="hidden" name="wlop_screen_height" value="${ window.screen.height }"/>`
				);
				placeOrderButton.insertAdjacentHTML(
					'afterend',
					`<input type="hidden" name="wlop_screen_width" value="${ window.screen.width }"/>`
				);

				placeOrderButton.insertAdjacentHTML(
					'afterend',
					`<input type="hidden" name="wlop_color_depth" value="${ window.screen.colorDepth }"/>`
				);

				const javaEnabled = navigator.javaEnabled() ? 'true' : 'false';
				placeOrderButton.insertAdjacentHTML(
					'afterend',
					`<input type="hidden" name="wlop_java_enabled" value="${ javaEnabled }"/>`
				);

				const timezoneOffset = new Date().getTimezoneOffset();
				placeOrderButton.insertAdjacentHTML(
					'afterend',
					`<input type="hidden" name="wlop_timezone_offset" value="${ timezoneOffset }"/>`
				);

				canSubmit = true;
				placeOrderButton.click();
			} else {
				// eslint-disable-next-line no-alert
				alert( response.error.message );
			}
		} );
	};

	const updateTokenState = () => {
		const selectedRadio = document.querySelector(
			tokenRadiosSelector + ':checked'
		) as HTMLInputElement | null;
		if ( ! selectedRadio ) {
			return;
		}

		const value = selectedRadio.value;
		if ( value === 'new' ) {
			lastSurchargeAmount = 0;
			updateSurcharge( 0 );
			tokenizer.useToken();
		} else {
			const token = WlopHtConfig.tokens[ value ];
			tokenizer.useToken( token );
		}
	};

	const reloadConfig = async ( withoutUrl = false ) => {
		const data = await jQuery.ajax( {
			type: 'post',
			dataType: 'json',
			url: WlopHtConfig.ajax,
			data: {
				action: 'wlop_hosted_tokenization_config',
				withoutUrl,
			},
		} );

		const newWlopHtConfig = data.data;
		if ( withoutUrl ) {
			newWlopHtConfig.url = WlopHtConfig.url;
			newWlopHtConfig.tokens = WlopHtConfig.tokens;
		}
		WlopHtConfig = newWlopHtConfig;
	};

	initTokenizer();

	initIframe();

	jQuery( document.body ).on( 'updated_checkout', async () => {
		storeOriginalOrderSummaryTable();
		await reloadConfig( true );

		await initIframe();

		updateAmount();
	} );

	jQuery( document.body ).on(
		'updated_checkout payment_method_selected',
		() => {
			updateVisibility();
		}
	);

	jQuery( document.body ).on( 'checkout_error', async () => {
		if ( getCurrentPaymentMethod() !== WlopHtConfig.gateway.id ) {
			return;
		}

		await reloadConfig();

		await resetIframe();
	} );

	const checkoutForm = document.querySelector(
		'form.checkout'
	) as HTMLFormElement | null;
	if ( checkoutForm ) {
		// Prevent form submission on Enter for our card gateway.
		const inputs = checkoutForm.querySelectorAll( 'input' );
		inputs.forEach( ( inp ) => {
			inp.addEventListener( 'keydown', ( e ) => {
				if (
					e.key === 'Enter' &&
					getCurrentPaymentMethod() === WlopHtConfig.gateway.id
				) {
					e.preventDefault();
				}
			} );
		} );
	}

	setTimeout( () => {
		jQuery( document.body ).trigger( 'wc-credit-card-form-init' );
	}, 0 );
} );
