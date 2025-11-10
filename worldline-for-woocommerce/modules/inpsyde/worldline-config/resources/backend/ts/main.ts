import '../scss/main.scss';
import { setVisibleByClass, setVisible } from '#shared/utils/visibility';

interface CopyHTMLButtonElement extends HTMLButtonElement {
	disabled: boolean;
	dataset: {
		copy: string;
		copiedMessage: string;
	};
}

function getFieldRow(selector: string): HTMLElement | null {
	const el = document.querySelector(selector);
	return el?.closest('tr') ?? null;
}

/**
 * Updates the logo preview on the page.
 *
 * @param {string} logoUrl
 * @param {boolean} isDefault
 */
function updateLogoPreview(logoUrl: string, isDefault: boolean) {
	const logoPreview = document.querySelector(
		'.wlop-logo-preview'
	) as HTMLImageElement | null;
	const deleteButton = document.querySelector(
		'.wlop-logo-delete'
	) as HTMLElement | null;
	const logoInput = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_logo_url'
	) as HTMLInputElement | null;

	if (!logoPreview || !deleteButton || !logoInput) {
		return;
	}

	logoInput.value = isDefault ? '' : logoUrl;

	if (isDefault) {
		logoPreview.src = logoPreview.dataset.defaultUrl ?? '';
		logoPreview.style.display = 'block';
		deleteButton.style.display = 'none';
	}
	if (!isDefault && logoUrl) {
		logoPreview.src = logoUrl;
		logoPreview.style.display = 'block';
		deleteButton.style.display = 'inline-block';
	}
	if (!isDefault && !logoUrl) {
		logoPreview.src = '';
		logoPreview.style.display = 'none';
		deleteButton.style.display = 'none';
	}
}

/**
 * Fetches the current logo URL from the server.
 */
async function fetchLogoPreview() {
	try {
		const response = await jQuery.ajax({
			type: 'post',
			dataType: 'json',
			url: '/wp-admin/admin-ajax.php',
			data: {
				action: 'wlop_hosted_tokenization_config',
			},
		});

		if (response.success && response.data.logo_url) {
			updateLogoPreview(response.data.logo_url, response.data.is_default);
			const logoInput = document.querySelector('#woocommerce_worldline-for-woocommerce_logo_url') as HTMLInputElement | null;
			if (logoInput) {
				logoInput.value = response.data.logo_url;
			}
		} else {
			updateLogoPreview('', true);
		}
	} catch (err) {
		console.error('Failed to fetch logo URL:', err);
		updateLogoPreview('', true);
	}
}

/**
 * Handles the logo upload via AJAX and updates the UI.
 *
 * @param {File} file
 */
function handleLogoUpload(file: File) {
	const formData = new FormData();
	formData.append('action', 'wlop_hosted_tokenization_config');
	formData.append('logo_file', file);

	jQuery.ajax({
		type: 'post',
		url: '/wp-admin/admin-ajax.php',
		data: formData,
		contentType: false,
		processData: false,
		success: (response) => {
			if (response.success && response.data.logo_url) {
				updateLogoPreview(response.data.logo_url, response.data.is_default);
				const logoInput = document.querySelector('#woocommerce_worldline-for-woocommerce_logo_url') as HTMLInputElement | null;
				if (logoInput) {
					logoInput.value = response.data.logo_url;
					logoInput.dispatchEvent(new Event('change'));
				}
			} else {
				alert(response.data.message);
			}
		},
		error: () => {
			alert('Failed to upload logo.');
		},
	});
}

/**
 * Handles the logo deletion via AJAX and updates the UI.
 */
function handleLogoDeletion() {
	const logoInput = document.querySelector('#woocommerce_worldline-for-woocommerce_logo_url') as HTMLInputElement | null;
	if (logoInput) {
		logoInput.value = '';
		logoInput.dispatchEvent(new Event('change'));
	}

	updateLogoPreview('', true);
}


/**
 * Initializes the logo upload and deletion controls.
 */
function initLogoControls() {
	const uploadButton = document.querySelector('.wlop-logo-upload-button');
	const deleteButton = document.querySelector('.wlop-logo-delete');
	const logoContainer = document.querySelector('.wlop-logo-controls');

	if (!uploadButton || !deleteButton || !logoContainer) {
		return;
	}

	const fileInput = document.createElement('input');
	fileInput.type = 'file';
	fileInput.accept = 'image/png, image/gif, image/jpeg';
	fileInput.style.display = 'none';

	document.body.appendChild(fileInput);

	uploadButton.addEventListener('click', () => {
		fileInput.click();
	});

	fileInput.addEventListener('change', (event) => {
		const target = event.target as HTMLInputElement;
		if (target.files && target.files[0]) {
			handleLogoUpload(target.files[0]);
		}
	});

	deleteButton.addEventListener('click', handleLogoDeletion);
}

document.addEventListener('DOMContentLoaded', () => {
	const chkLiveMode = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_live_mode'
	) as HTMLInputElement | null;

	const lstAuthorizationMode = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_authorization_mode'
	) as HTMLInputElement | null;

	const chkIs3dsAuthentication = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_enable_3ds'
	) as HTMLInputElement | null;

	const chkIs3dsExemption = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_request_3ds_exemption'
	) as HTMLInputElement | null;

	const lst3dsExemptionType = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_3ds_exemption_type'
	) as HTMLInputElement | null;

	const num3dsExemptionLimit = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_3ds_exemption_limit'
	) as HTMLInputElement | null;

	let prevExemptionType: string | null = null;
	let lastTraLimit: number | null = null;

	function updateLiveTestFields() {
		if (!chkLiveMode) {
			return;
		}

		const isLive = chkLiveMode.checked;

		setVisibleByClass(
			getFieldRow('#woocommerce_worldline-for-woocommerce_live_api_key'),
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
			getFieldRow('#woocommerce_worldline-for-woocommerce_test_api_key'),
			!isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_api_secret'
			),
			!isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_api_endpoint'
			),
			!isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_webhook_id'
			),
			!isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_test_webhook_secret_key'
			),
			!isLive,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow('#wlopLiveAccountNote'),
			isLive,
			'wlop-hidden'
		);
		setVisible('#wlopTestCreateAccountLink', !isLive);
		setVisible('#wlopLiveCreateAccountLink', isLive);
		setVisible('#wlopTestViewAccountLink', !isLive);
		setVisible('#wlopLiveViewAccountLink', isLive);
	}

	function updateAuthorizationFields() {
		if (!lstAuthorizationMode) {
			return;
		}

		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_credit_card_authorization_mode'
			),
			lstAuthorizationMode.value === 'authorization',
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow('#woocommerce_worldline-for-woocommerce_capture_mode'),
			lstAuthorizationMode.value === 'authorization',
			'wlop-hidden'
		);
	}

	function update3dsFields() {
		if (!chkIs3dsAuthentication) {
			return;
		}

		setVisibleByClass(
			getFieldRow('#woocommerce_worldline-for-woocommerce_enforce_3dsv2'),
			chkIs3dsAuthentication.checked,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_request_3ds_exemption'
			),
			chkIs3dsAuthentication.checked,
			'wlop-hidden'
		);

		if (!chkIs3dsExemption) {
			return;
		}

		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_3ds_exemption_type'
			),
			chkIs3dsAuthentication.checked && chkIs3dsExemption.checked,
			'wlop-hidden'
		);
		setVisibleByClass(
			getFieldRow(
				'#woocommerce_worldline-for-woocommerce_3ds_exemption_limit'
			),
			chkIs3dsAuthentication.checked && chkIs3dsExemption.checked,
			'wlop-hidden'
		);
		setVisibleByClass(
			'#woocommerce_worldline-for-woocommerce_3ds_exemption_warning',
			chkIs3dsAuthentication.checked && chkIs3dsExemption.checked,
			'wlop-hidden'
		);
	}

	function update3dsExemptionLimit() {
		if (!lst3dsExemptionType || !num3dsExemptionLimit) {
			return;
		}

		const currentType = lst3dsExemptionType.value;
		const currentLimit = parseInt(num3dsExemptionLimit.value);

		const max = currentType === 'low-value' ? 30 : 100;

		num3dsExemptionLimit.setAttribute('max', max.toString());
		if (currentLimit > max) {
			num3dsExemptionLimit.value = max.toString();

			if (
				prevExemptionType === 'transaction-risk-analysis' &&
				currentType === 'low-value'
			) {
				lastTraLimit = currentLimit;
			}
		}

		if (
			prevExemptionType === 'low-value' &&
			currentType === 'transaction-risk-analysis'
		) {
			if (lastTraLimit && lastTraLimit > 30) {
				num3dsExemptionLimit.value = lastTraLimit.toString();
				lastTraLimit = null;
			}
		}

		prevExemptionType = currentType;
	}

	function initCopyButtons() {
		const copyButtons: NodeListOf<CopyHTMLButtonElement> =
			document.querySelectorAll('.wlop-button-copy');

		if (copyButtons.length === 0) {
			return;
		}

		copyButtons.forEach((button) => {
			const copySelector = button.dataset.copy;
			const fieldToCopy: HTMLInputElement | null =
				document.querySelector(copySelector);

			if (!fieldToCopy) {
				// eslint-disable-next-line no-console
				console.error(
					"Can't copy the value from field. The field doesn't exist."
				);
				return;
			}

			button.addEventListener('click', () =>
				copyOnButtonClick(button, fieldToCopy)
			);
		});
	}

	async function copyOnButtonClick(
		button: CopyHTMLButtonElement,
		fieldToCopy: HTMLInputElement
	) {
		try {
			await navigator.clipboard.writeText(fieldToCopy.value);
			const buttonTextContent = button.textContent;
			button.textContent = button.dataset.copiedMessage;
			button.disabled = true;
			setTimeout(() => {
				button.disabled = false;
				button.textContent = buttonTextContent;
			}, 1000);
		} catch (err) {
			// eslint-disable-next-line no-alert
			alert(
				'Failed to copy, please select the address and copy it manually.'
			);
		}
	}

	chkLiveMode?.addEventListener('click', updateLiveTestFields);
	lstAuthorizationMode?.addEventListener(
		'change',
		updateAuthorizationFields
	);

	chkIs3dsAuthentication?.addEventListener('click', update3dsFields);
	chkIs3dsExemption?.addEventListener('click', update3dsFields);
	lst3dsExemptionType?.addEventListener('change', update3dsExemptionLimit);

	setVisibleByClass(
		getFieldRow('#woocommerce_worldline-for-woocommerce_logo_url'),
		false,
		'wlop-hidden'
	);

	updateLiveTestFields();
	updateAuthorizationFields();
	update3dsFields();
	update3dsExemptionLimit();
	initCopyButtons();
	initLogoControls();
	fetchLogoPreview();
});
