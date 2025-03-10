export const getCurrentPaymentMethod = () => {
	const el = document.querySelector(
		'input[name="payment_method"]:checked'
	) as HTMLInputElement | null;
	if ( ! el ) {
		return null;
	}

	return el.value;
};
