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
            var showIframe = document.getElementById("moptWorldlineShowIframe");
            if (showIframe !== null && showIframe.value) {
                if(this._isRedirectToken() === '1') {
                    this._initRedirectTokenMethod();
                } else {
                    this._initIframe();
                }
            }
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
        this.confirmForm = document.getElementById("confirmOrderForm");
        var token = this._getCurrentToken();
        this._client.get(
            '/worldline_iframe?salesChannelId='+this.salesChannelId+'&token='+token,
            this._setContent.bind(this),
            'application/json',
            true
        );

        this.confirmForm.addEventListener("submit", (event)=>{
            event.preventDefault();
            this._confirmOrderForm();
        });
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

    _confirmOrderForm() {
        var storeCard = this.savePaymentCardCheckbox ? this.savePaymentCardCheckbox.checked : false;
        this.tokenizer.submitTokenization({ storePermanently:storeCard }).then((result) => {
            if (result.success) {
                this._createHiddenInput(this.confirmForm, "moptWorldlineHostedTokenizationId",  result.hostedTokenizationId);
                this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataColorDepth", screen.colorDepth);
                this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataScreenHeight", screen.height);
                this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataScreenWidth", screen.width);
                this._createHiddenInput(this.confirmForm, "moptWorldlineBrowserDataJavaEnabled", navigator.javaEnabled());
                this._createHiddenInput(this.confirmForm, "moptWorldlineLocale", navigator.language);
                this._createHiddenInput(this.confirmForm, "moptWorldlineUserAgent", navigator.userAgent);
                this._createHiddenInput(this.confirmForm, "moptWorldlineTimezoneOffsetUtcMinutes", new Date().getTimezoneOffset());
                this.confirmForm.submit();
            } else {
            }
        });
    }

    _initRedirectTokenMethod() {
        this.confirmForm = document.getElementById("confirmOrderForm");
        this._createHiddenInput(this.confirmForm, "moptWorldlineRedirectToken", this._getCurrentToken());
        this._createHiddenInput(this.confirmForm, "moptWorldlinePaymentProductId", this._getPaymentProductId());
    }

    _createHiddenInput(form, name, value)
     {
         var input = document.createElement("input");
         input.setAttribute("type", "hidden");
         input.setAttribute("name", name);
         input.setAttribute("value", value);
         form.appendChild(input);
     }

    //Send saved card token if exist
    _changePaymentForm() {
        var token = this._getCurrentToken();
        this._client.get('/worldline_cardToken?worldline_cardToken='+token);
        var submit = true;
        var showIframe = document.getElementById("moptWorldlineShowIframe");
        if (showIframe !== null && showIframe.value) {
            if (this.savePaymentCardCheckbox !== null) {
                submit = this.savePaymentCardCheckbox.checked ? false : true;
            }
        }
        if(submit) {
            this.changePaymentForm.submit();
        }
    }

    _getCurrentToken() {
        var elem = document.querySelector('#changePaymentForm input:checked');
        var rel =  elem ? elem.attributes['rel'] : "";
        return rel ? rel.value : "";
    }

    _isRedirectToken() {
        var elem = document.querySelector('#changePaymentForm input:checked');
        var redirect =  elem ? elem.attributes['redirect'] : "";
        return redirect ? redirect.value : "";
    }

    _getPaymentProductId() {
        var elem = document.querySelector('#changePaymentForm input:checked');
        var product =  elem ? elem.attributes['product'] : "";
        return product ? product.value : "";
    }

    //Send saved card token if exist
    _changeAccountPaymentForm() {
        var token = this._getCurrentAccountToken();

        this._client.get(
            '/worldline_accountCardToken?worldline_accountCardToken='+token,
            this._submit.bind(this),
            'application/json',
            true
        );
    }

    _submit(response) {
        this.changeAccountPaymentForm.submit();
    }

    _getCurrentAccountToken() {
        var elem = document.getElementById('moptWorldlinePageId').form.querySelector('input:checked');
        var rel =  elem ? elem.attributes['rel'] : "";
        return rel ? rel.value : "";
    }

}
