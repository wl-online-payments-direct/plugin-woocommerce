import '../scss/main.scss';
import { setVisibleByClass, setVisible } from '#shared/utils/visibility';

interface CopyHTMLButtonElement extends HTMLButtonElement {
	disabled: boolean;
	dataset: {
		copy: string;
		copiedMessage: string;
	};
}

function getFieldRow( selector: string ): HTMLElement | null {
	const el = document.querySelector( selector );
	return el?.closest( 'tr' ) ?? null;
}

document.addEventListener( 'DOMContentLoaded', () => {
	const chkLiveMode = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_live_mode'
	) as HTMLInputElement | null;

	const lstAuthorizationMode = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_authorization_mode'
	) as HTMLInputElement | null;

	const chkIs3dsAuthentication = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_enable_3ds'
	) as HTMLInputElement | null;

	function updateLiveTestFields() {
		if ( ! chkLiveMode ) {
			return;
		}

		const isLive = chkLiveMode.checked;

		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_live_api_key'
			),
			isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_live_api_secret'
			),
			isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_live_api_endpoint'
			),
			isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_live_webhook_id'
			),
			isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_live_webhook_secret_key'
			),
			isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_api_key'
			),
			! isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_api_secret'
			),
			! isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_api_endpoint'
			),
			! isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_webhook_id'
			),
			! isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_webhook_secret_key'
			),
			! isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow( '#wlopLiveAccountNote' ),
			isLive,
			'wlop-hidden'
		);
		setVisible( '#wlopTestCreateAccountLink', ! isLive );
		setVisible( '#wlopLiveCreateAccountLink', isLive );
		setVisible( '#wlopTestViewAccountLink', ! isLive );
		setVisible( '#wlopLiveViewAccountLink', isLive );
	}

	function updateAuthorizationFields() {
		if ( ! lstAuthorizationMode ) {
			return;
		}

		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_credit_card_authorization_mode'
			),
			lstAuthorizationMode.value === 'authorization',
			'wlop-hidden'
		);
	}

	function update3dsFields() {
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_enforce_3dsv2'
			),
			chkIs3dsAuthentication?.checked,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_request_3ds_exemption'
			),
			chkIs3dsAuthentication?.checked,
			'wlop-hidden'
		);
	}

	function initCopyButtons() {
		const copyButtons: NodeListOf< CopyHTMLButtonElement > =
			document.querySelectorAll( '.wlop-button-copy' );

		if ( copyButtons.length === 0 ) {
			return;
		}

		copyButtons.forEach( ( button ) => {
			const copySelector = button.dataset.copy;
			const fieldToCopy: HTMLInputElement | null =
				document.querySelector( copySelector );

			if ( ! fieldToCopy ) {
				// eslint-disable-next-line no-console
				console.error(
					"Can't copy the value from field. The field doesn't exist."
				);
				return;
			}

			button.addEventListener( 'click', () =>
				copyOnButtonClick( button, fieldToCopy )
			);
		} );
	}
	async function copyOnButtonClick(
		button: CopyHTMLButtonElement,
		fieldToCopy: HTMLInputElement
	) {
		try {
			await navigator.clipboard.writeText( fieldToCopy.value );
			const buttonTextContent = button.textContent;
			button.textContent = button.dataset.copiedMessage;
			button.disabled = true;
			setTimeout( () => {
				button.disabled = false;
				button.textContent = buttonTextContent;
			}, 1000 );
		} catch ( err ) {
			// eslint-disable-next-line no-alert
			alert(
				'Failed to copy, please select the address and copy it manually.'
			);
		}
	}

	chkLiveMode?.addEventListener( 'click', updateLiveTestFields );
	lstAuthorizationMode?.addEventListener(
		'change',
		updateAuthorizationFields
	);
	chkIs3dsAuthentication?.addEventListener( 'click', update3dsFields );

	updateLiveTestFields();
	updateAuthorizationFields();
	update3dsFields();
	initCopyButtons();
} );
