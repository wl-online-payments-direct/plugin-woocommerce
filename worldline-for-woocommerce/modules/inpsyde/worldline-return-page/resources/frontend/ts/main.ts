import '../scss/main.scss';

type WoocommerceParams = {
	ajax_url: string;
};

type ReturnPageResponse = {
	data: {
		status: string;
		canCheckAgain: boolean;
		timeout: number;
		timesThePaymentStatusWasChecked: number;
		message: string;
		loading: string;
	};
};

interface ReturnPageConfig extends DOMStringMap {
	timeout: string;
	retryCount: string;
	action: string;
}

interface ReturnPageHTMLElement extends HTMLElement {
	dataset: ReturnPageConfig;
}

/* eslint-disable camelcase */
declare let woocommerce_params: WoocommerceParams;
document.addEventListener( 'DOMContentLoaded', () => {
	const paymentStatusElement =
		( document.querySelector(
			'.syde-return-page-order-payment-status'
		) as ReturnPageHTMLElement ) || null;
	if (
		! paymentStatusElement ||
		paymentStatusElement.classList.contains( 'done' )
	) {
		return;
	}
	startChecking( paymentStatusElement );
} );

async function startChecking( returnPageElement: ReturnPageHTMLElement ) {
	const formData = new FormData();
	const config = returnPageElement.dataset;

	formData.append( 'action', config.action );

	const urlParams = new URLSearchParams( window.location.search );
	const wcOrderKey = urlParams.get( 'key' );
	if ( wcOrderKey ) {
		formData.append( 'wcOrderKey', wcOrderKey );
	}

	await updateOrderStatus( config, formData, returnPageElement );

	returnPageElement.classList.add( 'done' );
	document.body.classList.remove( 'syde-return-page-active' );
}

async function updateOrderStatus(
	config: ReturnPageConfig,
	formData: FormData,
	returnPageElement: ReturnPageHTMLElement,
	forceUpdate: boolean = false,
	timesRetried: number = 1
) {
	try {
		formData.set( 'forceUpdate', forceUpdate ? 'true' : 'false' );

		const response = await fetch( woocommerce_params.ajax_url, {
			method: 'POST',
			body: formData,
		} );

		const orderStatus: ReturnPageResponse = await response.json();

		switch ( orderStatus.data.status ) {
			case 'pending':
				const maxRetryCount = parseInt( config.retryCount );
				const timeout = parseInt( config.timeout );

				if ( timesRetried < maxRetryCount ) {
					return new Promise( ( resolve ) =>
						setTimeout( () => {
							resolve(
								updateOrderStatus(
									config,
									formData,
									returnPageElement,
									timesRetried + 1 === maxRetryCount,
									timesRetried + 1
								)
							);
						}, timeout )
					);
				}
				break;
			case 'cancelled':
			case 'failed':
				// normally cancellation should not occur during ajax updates, so just reloading in case it somehow happened
				// also reloading for failed to use the standard WC page
				location.reload();
				break;
		}

		returnPageElement.innerHTML = orderStatus.data.message;
	} catch ( err ) {
		/* eslint-disable no-console */
		console.error( err );
	}
}
