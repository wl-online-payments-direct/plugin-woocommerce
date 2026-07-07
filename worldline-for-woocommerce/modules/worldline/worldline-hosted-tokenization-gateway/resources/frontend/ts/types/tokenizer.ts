export interface TokenizerOptions {
	hideCardholderName?: boolean;
	hideTokenFields?: boolean;
	surchargeCallback?: ( result: any ) => void;
}
