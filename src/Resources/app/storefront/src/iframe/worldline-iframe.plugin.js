import HttpClient from 'src/service/http-client.service';
import Plugin from 'src/plugin-system/plugin.class';

export default class WorldlineIframePlugin extends Plugin {
    init() {
        if (document.getElementById("moptWorldlinePageId") === null) {
            return;
        } else {
            this.page = document.getElementById("moptWorldlinePageId").value;
        }

        this._client = new HttpClient();

        if (this.page === 'cartConfirm') {
            this.changePaymentForm = document.getElementById("changePaymentForm");
            this.changePaymentForm.addEventListener("change", (event)=>{
                event.preventDefault();
                this._changePaymentForm();
            });

            this.moptWorldlineSalesChannel = document.getElementById("moptWorldlineSalesChannelId");
            this.moptWorldlineLocaleId = document.getElementById("moptWorldlineLocaleId");
            this._client.get('/worldline_serverUrl?serverUrl='+document.URL);
            let showIframe = document.getElementById("moptWorldlineShowIframe");
            if (showIframe !== null && showIframe.value) {
                if(this._isRedirectToken() === '1') {
                    this._initRedirectTokenMethod();
                } else {
                    this._initIframe();
                }
            }
            this._setWorldLineOrderFormSubmitOverride();
            //Get rid of chosen card token
            this._client.get('/worldline_cardToken?worldline_cardToken=');
        }

        if (this.page === 'account') {
            this.changeAccountPaymentForm = document.getElementById('moptWorldlinePageId').form;
            this.changeAccountPaymentForm.addEventListener("submit", (event)=>{
                event.preventDefault();
                this._changeAccountPaymentForm();
            });

            //Get rid of chosen card token
            this._client.get('/worldline_accountCardToken?worldline_accountCardToken=');
        }
    }

    _initIframe() {
        this.tokenizationDiv = "div-hosted-tokenization";
        this.savePaymentCardCheckbox = document.getElementById("moptWorldlineSavePaymentCard");
        this.salesChannelId = this.moptWorldlineSalesChannel.value;
        this.localeId = this.moptWorldlineLocaleId.value;
        const token = this._getCurrentToken();
        this._client.get(
            '/worldline_iframe?salesChannelId='+this.salesChannelId+'&token='+token+'&localeId='+this.localeId,
            this._setContent.bind(this),
            'application/json',
            true
        );
    }

    _setContent(data) {
        this.tokenizer = new Tokenizer(
            JSON.parse(data).url,
            this.tokenizationDiv,
            {hideCardholderName: false,
            hideTokenFields: true},
            this._getCurrentToken()
        );
        this.tokenizer.initialize();
    }

    _confirmOrderFormForIFrame() {
        const storeCard = this.savePaymentCardCheckbox ? this.savePaymentCardCheckbox.checked : false;
        this.tokenizer.submitTokenization({ storePermanently:storeCard }).then((result) => {
            if (result.success) {
                this._createHiddenInput(this.confirmForm, "moptWorldlineHostedTokenizationId",  result.hostedTokenizationId);
                this.confirmForm.submit();
            } else {
            }
        });
    }

    _setWorldLineOrderFormSubmitOverride() {
        this.confirmForm = document.getElementById("confirmOrderForm");
        this.confirmForm.addEventListener("submit", (event)=>{
            event.preventDefault();
            this._setHiddenInputs();
            let showIframe = document.getElementById("moptWorldlineShowIframe");
            if (showIframe !== null && showIframe.value) {
                if (this._isRedirectToken() !== '1') {
                    this._confirmOrderFormForIFrame();
                    return;
                }
            }
            this.confirmForm.submit();
        });
    }

    _setHiddenInputs() {
        this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataColorDepth", screen.colorDepth);
        this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataScreenHeight", screen.height);
        this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataScreenWidth", screen.width);
        this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataJavaEnabled", navigator.javaEnabled()); // according to mdn this method always returns false
        this._createHiddenInput(this.confirmForm, "moptWorldlineLocale", this.moptWorldlineLocaleId.value);
        this._createHiddenInput(this.confirmForm, "moptWorldlineUserAgent", navigator.userAgent);
        this._createHiddenInput(this.confirmForm, "moptWorldlineTimezoneOffsetUtcMinutes", new Date().getTimezoneOffset());
    }

    _initRedirectTokenMethod() {
        this.confirmForm = document.getElementById("confirmOrderForm");
        this._createHiddenInput(this.confirmForm, "moptWorldlineRedirectToken", this._getCurrentToken());
    }

    _createHiddenInput(form, name, value)
     {
         let input = document.createElement("input");
         input.setAttribute("type", "hidden");
         input.setAttribute("name", name);
         input.setAttribute("value", value);
         form.appendChild(input);
     }

    //Send saved card token if exist
    _changePaymentForm() {
        let token = this._getCurrentToken();
        this._client.get('/worldline_cardToken?worldline_cardToken='+token);
        let submit = true;
        let showIframe = document.getElementById("moptWorldlineShowIframe");
        if (showIframe !== null && showIframe.value) {
            if (this.savePaymentCardCheckbox !== null) {
                submit = !this.savePaymentCardCheckbox.checked;
            }
        }
        if(submit) {
            this.changePaymentForm.submit();
        }
    }

    _getCurrentToken() {
        let elem = document.querySelector('#changePaymentForm input:checked');
        let rel =  elem ? elem.attributes['rel'] : "";
        return rel ? rel.value : "";
    }

    _isRedirectToken() {
        let elem = document.querySelector('#changePaymentForm input:checked');
        let redirect =  elem ? elem.attributes['redirect'] : "";
        return redirect ? redirect.value : "";
    }

    _getPaymentProductId() {
        let elem = document.querySelector('#changePaymentForm input:checked');
        let product =  elem ? elem.attributes['product'] : "";
        return product ? product.value : "";
    }

    //Send saved card token if exist
    _changeAccountPaymentForm() {
        const token = this._getCurrentAccountToken();

        this._client.get(
            `/worldline_accountCardToken?worldline_accountCardToken=${token}`,
            this._submit.bind(this),
            'application/json',
            true
        );
    }

    _submit(response) {
        this.changeAccountPaymentForm.submit();
    }

    _getCurrentAccountToken() {
        let elem = document.getElementById('moptWorldlinePageId').form.querySelector('input:checked');
        let rel =  elem ? elem.attributes['rel'] : "";
        return rel ? rel.value : "";
    }

}
