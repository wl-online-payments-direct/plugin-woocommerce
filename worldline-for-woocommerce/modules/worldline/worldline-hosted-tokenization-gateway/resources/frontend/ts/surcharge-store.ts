import { createReduxStore, register } from '@wordpress/data';

export const SURCHARGE_STORE_KEY = 'wlop-ht-surcharge';

const DEFAULT_STATE = {
	surcharge: 0,
};

const actions = {
	setSurcharge( amount ) {
		return {
			type: 'SET_SURCHARGE',
			payload: amount,
		};
	},
};

function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case 'SET_SURCHARGE':
			return {
				...state,
				surcharge: action.payload,
			};
		default:
			return state;
	}
}

const selectors = {
	getSurcharge( state ) {
		return state.surcharge;
	},
};

const store = createReduxStore( SURCHARGE_STORE_KEY, {
	reducer,
	actions,
	selectors,
} );

register( store );

export type SurchargeStore = {
	getSurcharge: () => number;
	setSurcharge: ( amount: number ) => void;
};
