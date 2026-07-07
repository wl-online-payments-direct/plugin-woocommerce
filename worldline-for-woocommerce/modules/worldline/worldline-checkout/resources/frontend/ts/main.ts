import '../scss/main.scss';

/// <reference types="jquery" />

const cardButtons: NodeListOf< HTMLButtonElement > = document.querySelectorAll(
	'.wlop-saved-card-button button'
);

cardButtons.forEach( ( btn ) => {
	btn.addEventListener( 'click', () => {
		const tokenId = btn.dataset.token;
		if ( ! tokenId ) {
			return;
		}

		const form = document.querySelector(
			'form.woocommerce-checkout, form#order_review'
		) as HTMLFormElement | null;
		if ( ! form ) {
			return;
		}

		const rbWlop = form.querySelector(
			'#payment_method_worldline-for-woocommerce'
		) as HTMLInputElement | null;
		if ( ! rbWlop ) {
			return;
		}

		// make our method selected, to trigger our handler in the backend
		rbWlop.checked = true;

		// add the token id into the request
		form.insertAdjacentHTML(
			'beforeend',
			`<input type="hidden" name="wlop_token" value="${ tokenId }">`
		);

		// delete the token element after the form submission,
		// we only need it during the form serialization for the ajax request,
		// and it should not be sent in other submissions, e.g. repeating with another method after error
		setTimeout( () => {
			form.querySelectorAll( 'input[name="wlop_token"]' ).forEach(
				( e ) => e.remove()
			);
		}, 0 );
	} );
} );

jQuery( document.body ).on( 'updated_checkout', () => {
	const cardButtonsWrapper = document.querySelector(
		'.wlop-saved-card-buttons-wrapper'
	) as HTMLElement | null;
	if ( ! cardButtonsWrapper ) {
		return;
	}

	const isWlopAvailable =
		document.querySelector(
			'#payment_method_worldline-for-woocommerce'
		) !== null;

	cardButtonsWrapper.style.display = isWlopAvailable ? '' : 'none';
} );
