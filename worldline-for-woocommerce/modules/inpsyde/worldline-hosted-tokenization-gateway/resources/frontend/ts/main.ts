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

		setVisible(
			wrapper,
			getCurrentPaymentMethod() === WlopHtConfig.gateway.id
		);
	};

	const updateAmount = () => {
		if ( ! tokenizer || ! initialized ) {
			return;
		}

		tokenizer.setAmount( WlopHtConfig.total, WlopHtConfig.currency.code );
	};

	const updateSurcharge = ( surcharge: number ) => {
		if ( surcharge <= 0 ) {
			return;
		}

		const element = document.getElementById(
			WlopHtConfig.surcharge.wrapper.id
		);
		if ( ! element ) {
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

		updateVisibility();

		initialized = true;

		updateAmount();
	};

	const resetIframe = async () => {
		[
			'wlop_hosted_tokenization_id',
			'wlop_screen_height',
			'wlop_screen_width',
		].forEach( ( name ) => {
			document.querySelector( `[name="${ name }"]` )?.remove();
		} );
		initTokenizer();
		setupButtonHandler();
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

				canSubmit = true;
				placeOrderButton.click();
			} else {
				// eslint-disable-next-line no-alert
				alert( response.error.message );
			}
		} );
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
		}
		WlopHtConfig = newWlopHtConfig;
	};

	initTokenizer();

	setupButtonHandler();

	initIframe();

	jQuery( document.body ).on( 'updated_checkout', async () => {
		await reloadConfig( true );

		setupButtonHandler();
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
} );
