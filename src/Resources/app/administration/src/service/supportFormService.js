const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class supportForm extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'support-form') {
        super(httpClient, loginService, apiEndpoint);
    }

    send(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/send`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    downloadLog(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/download_log`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('supportForm', (container) => {
    const initContainer = Application.getContainer('init');
    return new supportForm(initContainer.httpClient, container.loginService);
});
