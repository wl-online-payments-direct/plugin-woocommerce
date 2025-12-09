type SelectorOrElement = HTMLElement | string | null;

const getElement = (
	selectorOrElement: SelectorOrElement
): HTMLElement | null => {
	if ( typeof selectorOrElement === 'string' ) {
		return document.querySelector( selectorOrElement );
	}
	return selectorOrElement;
};

export const isVisible = ( element: HTMLElement ) => {
	return !! (
		element.offsetWidth ||
		element.offsetHeight ||
		element.getClientRects().length
	);
};

export const setVisible = (
	selectorOrElement: SelectorOrElement,
	show: boolean,
	important: boolean = false
) => {
	const element = getElement( selectorOrElement );
	if ( ! element ) {
		return;
	}

	const currentValue = element.style.getPropertyValue( 'display' );

	if ( ! show ) {
		if ( currentValue === 'none' ) {
			return;
		}

		element.style.setProperty(
			'display',
			'none',
			important ? 'important' : ''
		);
	} else {
		if ( currentValue === 'none' ) {
			element.style.removeProperty( 'display' );
		}

		// still not visible (if something else added display: none in CSS)
		if ( ! isVisible( element ) ) {
			element.style.setProperty( 'display', 'block' );
		}
	}
};

export const setVisibleByClass = (
	selectorOrElement: SelectorOrElement,
	show: boolean,
	hiddenClass: string
) => {
	const element = getElement( selectorOrElement );
	if ( ! element ) {
		return;
	}

	if ( show ) {
		element.classList.remove( hiddenClass );
	} else {
		element.classList.add( hiddenClass );
	}
};

export const hide = (
	selectorOrElement: SelectorOrElement,
	important: boolean = false
) => {
	setVisible( selectorOrElement, false, important );
};

export const show = ( selectorOrElement: SelectorOrElement ) => {
	setVisible( selectorOrElement, true );
};
