import '../scss/main.scss';

type WoocommerceParams = {
	ajax_url: string;
};

type ReturnPageResponse = {
	success?: boolean;
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
			'.worldline-return-page-order-payment-status'
		) as ReturnPageHTMLElement ) || null;
	if (
		! paymentStatusElement ||
		paymentStatusElement.classList.contains( 'done' )
	) {
		/*
		 * Nothing to poll, so reveal the page. The body class is added server-side
		 * whenever the payment still looks undecided, and this script is the only
		 * thing that ever removes it - normally through stopPolling(). Returning
		 * without doing so leaves the page permanently blank in every case where
		 * no polling happens: a status that resolved between the two server-side
		 * checks, or WooCommerce skipping the thank-you content entirely, as it
		 * does when a guest has to verify their email first.
		 */
		document.body.classList.remove( 'worldline-return-page-active' );
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

	stopPolling( returnPageElement );
}

function stopPolling( returnPageElement: ReturnPageHTMLElement ) {
	returnPageElement.classList.add( 'done' );
	document.body.classList.remove( 'worldline-return-page-active' );
}

function showError(
	returnPageElement: ReturnPageHTMLElement,
	message?: string
) {
	if ( ! message ) {
		return;
	}

	returnPageElement.textContent = message;
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

		if ( ! response.ok || orderStatus?.success === false ) {
			stopPolling( returnPageElement );

			if ( response.status !== 429 ) {
				showError( returnPageElement, orderStatus?.data?.message );
			}
			return;
		}

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
