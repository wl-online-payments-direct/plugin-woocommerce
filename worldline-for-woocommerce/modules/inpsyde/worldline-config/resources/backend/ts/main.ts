import '../scss/main.scss';
import { setVisibleByClass, setVisible } from '#shared/utils/visibility';

declare let wp;

interface CopyHTMLButtonElement extends HTMLButtonElement {
	disabled: boolean;
	dataset: {
		copy: string;
		copiedMessage: string;
	};
}

interface CustomIcon {
    id: number;
    title: string;
    url: string;
}

const WARNING_MESSAGE = 'Automatic: The URL(s) below will be used for transactions from this store, any webhook URL(s) configured in the merchant portal will be ignored.<br>Manual: You are fully responsible for adding your store webhook URL in the merchant portal. Failure to do so could result in missing or incomplete orders!';

const LAST_WEBHOOK_FIELD_ID = '#woocommerce_worldline-for-woocommerce_additional_webhook_url_4';
const GLOBAL_WEBHOOK_ERROR_MESSAGE = 'Please enter a valid HTTPS URL (max 325 characters)';

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

function getWebhookFields(): NodeListOf<HTMLInputElement> {
	return document.querySelectorAll<HTMLInputElement>('.wlop-additional-webhook-field');
}

function isValidHttpsUrl(url: string): boolean {
	try {
		const SCHEME = 'https:';
		if (!url.startsWith(SCHEME + '//')) {
			return false;
		}

		const urlObj = new URL(url);

		const hostname = urlObj.hostname;

		if (!hostname.includes('.')) {
			return false;
		}

		const parts = hostname.split('.');
		const tld = parts[parts.length - 1];

		return tld.length >= 1;
	} catch (e) {
		return false;
	}
}

function updateGlobalWebhookError(hasError: boolean, message: string) {
	const lastFieldRow = getFieldRow(LAST_WEBHOOK_FIELD_ID);
	if (!lastFieldRow) return;

	let errorRow = document.querySelector('.wlop-global-webhook-error') as HTMLTableRowElement | null;
	const parentTableBody = lastFieldRow.parentElement;

	if (hasError) {
		if (!errorRow) {
			errorRow = document.createElement('tr');
			errorRow.className = 'wlop-global-webhook-error';
			errorRow.innerHTML = `
				<th scope="row"></th>
				<td class="forminp">
					<p class="wlop-webhook-error-message">${message}</p>
				</td>
			`;

			if (parentTableBody) {
				parentTableBody.insertBefore(errorRow, lastFieldRow.nextSibling);
			}
		}
		errorRow.style.display = 'table-row';
		const pElement = errorRow.querySelector('p');
		if (pElement) {
			pElement.textContent = message;
		}
	} else {
		if (errorRow) {
			errorRow.style.display = 'none';
		}
	}
}

function validateWebhookField(event?: Event) {
	const fields = getWebhookFields();
	const saveButton = document.querySelector('.woocommerce-save-button') as HTMLButtonElement | null;
	let globalErrorNeeded = false;

	fields.forEach(field => {
		const url = field.value.trim();
		const isValid = url === '' || isValidHttpsUrl(url);

		if (!isValid) {
			field.classList.add('wlop-input-error');
			globalErrorNeeded = true;
		} else {
			field.classList.remove('wlop-input-error');
		}
	});

	updateGlobalWebhookError(globalErrorNeeded, wp.i18n.__(GLOBAL_WEBHOOK_ERROR_MESSAGE, 'worldline-for-woocommerce'));

	if (saveButton) {
		saveButton.disabled = globalErrorNeeded;
	}
}

function initWebhookValidation() {
	getWebhookFields().forEach(field => {
		field.addEventListener('blur', validateWebhookField);
		field.addEventListener('keyup', validateWebhookField);
		validateWebhookField();
	});
}

function getCustomIcons(): CustomIcon[] {
    const customIconsInput = document.querySelector(
        '#woocommerce_worldline-hosted-tokenization_custom_icons'
    ) as HTMLInputElement | null;

    if (!customIconsInput || !customIconsInput.value) {
        return [];
    }

    try {
        return JSON.parse(customIconsInput.value);
    } catch (err) {
        console.error('Failed to parse custom icons:', err);
        return [];
    }
}

function saveCustomIcons(icons: CustomIcon[]): void {
    const customIconsInput = document.querySelector(
        '#woocommerce_worldline-hosted-tokenization_custom_icons'
    ) as HTMLInputElement | null;

    if (!customIconsInput) {
        return;
    }

    customIconsInput.value = JSON.stringify(icons);
}

function renderCustomIconsGrid(): void {
    const grid = document.getElementById('wlop-custom-icons-grid');
    if (!grid) {
        return;
    }

    const icons = getCustomIcons();
    if (icons.length === 0) {
        grid.innerHTML = '';
        return;
    }

    grid.innerHTML = icons
        .map(
            (icon) =>
                `
					<div class="wlop-custom-icon-item" data-icon-id="${icon.id}">
						<img src="${icon.url}" alt="${icon.title}" class="wlop-custom-icon-image" />
						<button type="button" class="wlop-custom-icon-delete" aria-label="Delete ${icon.title}">
							<span class="dashicons dashicons-no-alt"></span>
						</button>
						<span class="wlop-custom-icon-title">${icon.title}</span>
					</div>
				`
        ).join('');

    const deleteButtons = grid.querySelectorAll('.wlop-custom-icon-delete');
    deleteButtons.forEach((button) => {
        button.addEventListener('click', handleIconDelete);
    });
}

function handleIconDelete(event: Event): void {
    const button = event.currentTarget as HTMLButtonElement;
    const iconItem = button.closest('.wlop-custom-icon-item');
    if (!iconItem) {
        return;
    }

    const iconId = parseInt(iconItem.getAttribute('data-icon-id') || '0');
    if (!iconId) {
        return;
    }

    const icons = getCustomIcons();
    const filteredIcons = icons.filter((icon) => icon.id !== iconId);

    saveCustomIcons(filteredIcons);
    renderCustomIconsGrid();

	const customIconsInput = document.querySelector(
		'#woocommerce_worldline-hosted-tokenization_custom_icons'
	) as HTMLInputElement | null;
	customIconsInput?.dispatchEvent(new Event('change'));
}

function loadCustomIcons(): void {
    jQuery.ajax({
        type: 'post',
        url: '/wp-admin/admin-ajax.php',
        data: {
            action: 'wlop_get_custom_icons',
        },
        success: (response) => {
            if (response.success && response.data.icons) {
                saveCustomIcons(response.data.icons);
                renderCustomIconsGrid();
            }
        },
        error: () => {
            console.error('Failed to load custom icons');
        },
    });
}

function handleIconsUpload(files: FileList): void {
    if (files.length === 0) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'wlop_upload_custom_icon');

    Array.from(files).forEach((file, index) => {
        formData.append(`icon_files[${index}]`, file)
    });

    jQuery.ajax({
        type: 'post',
        url: '/wp-admin/admin-ajax.php',
        data: formData,
        contentType: false,
        processData: false,
        success: (response) => {
            if (response.success && response.data.icons) {
                const currentIcons = getCustomIcons();
                console.log('Current icons before upload:', currentIcons);
                const newIcons = response.data.icons;
                console.log('New icons from backend:', newIcons);

                const allIcons = [...currentIcons, ...newIcons];

                saveCustomIcons(allIcons);
                renderCustomIconsGrid();

				const customIconsInput = document.querySelector(
					'#woocommerce_worldline-hosted-tokenization_custom_icons'
				) as HTMLInputElement | null;
				customIconsInput?.dispatchEvent(new Event('change'));
            } else {
                alert(
                    response.data.message ||
                    'Failed to upload icons.'
                );
            }
        },
        error: () => {
            alert('Failed to upload icons.');
        },
    });
}

function initCustomIconsControls(): void {
    console.log('Initializing custom icons controls...');

    const uploadButton = document.querySelector(
        '.wlop-custom-icons-upload-button'
    );
    const iconsContainer = document.querySelector(
        '.wlop-custom-icons-wrapper'
    );

    if (!uploadButton || !iconsContainer) {
        console.log('Required elements not found');
        return;
    }

    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/png, image/gif, image/jpeg, image/svg+xml';
    fileInput.multiple = true;
    fileInput.style.display = 'none';

    document.body.appendChild(fileInput);

    uploadButton.addEventListener('click', (event) => {
        console.log('Upload button clicked');
        event.preventDefault();
        fileInput.click();
    });

    fileInput.addEventListener('change', (event) => {
        console.log('File input changed');
        const target = event.target as HTMLInputElement;
        if (target.files) {
            handleIconsUpload(target.files);
        }
    });

    console.log('Loading custom icons...');
    loadCustomIcons();
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

	const webhookModeCheckbox = document.querySelector(
		'#woocommerce_worldline-for-woocommerce_webhook_mode_is_automatic'
	) as HTMLSelectElement | null;
	const webhookWarningBox = document.querySelector(
		'#wlop-webhook-mode-warning'
	) as HTMLElement | null;
	const copyButton = document.querySelector(
		'.wlop-button-copy.wlop-manual-mode-only'
	) as HTMLElement | null;

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

	function updateWebhookModeFields() {
		if (!webhookModeCheckbox || !webhookWarningBox) {
			return;
		}

		const isAutomatic = webhookModeCheckbox.checked;

		webhookWarningBox.innerHTML = `
    		<p>${wp.i18n.__(WARNING_MESSAGE, 'worldline-for-woocommerce')}</p>
		`;
		webhookWarningBox.className = 'wlop-warning-field';

		document.querySelectorAll<HTMLElement>('.wlop-additional-webhook-field').forEach(el => {
			setVisibleByClass(
				getFieldRow(`#${el.id}`),
				isAutomatic,
				'wlop-hidden'
			);
		});

		const errorRow = document.querySelector('.wlop-global-webhook-error') as HTMLElement | null;
		if (errorRow) {
			setVisibleByClass(errorRow, isAutomatic, 'wlop-hidden');
		}

		if (copyButton) {
			setVisibleByClass(
				copyButton.closest('button'),
				!isAutomatic,
				'wlop-hidden'
			);
		}
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
	webhookModeCheckbox?.addEventListener('click', updateWebhookModeFields);

	setVisibleByClass(
		getFieldRow('#woocommerce_worldline-for-woocommerce_logo_url'),
		false,
		'wlop-hidden'
	);

	updateLiveTestFields();
	updateAuthorizationFields();
	update3dsFields();
	update3dsExemptionLimit();
	updateWebhookModeFields();
	initCopyButtons();
	initLogoControls();
	fetchLogoPreview();
	initWebhookValidation();
    initCustomIconsControls()
});
