const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class transactionsControlService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'transactions-control') {
        super(httpClient, loginService, apiEndpoint);
    }

    statusCheck(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/status`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    capture(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/capture`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    cancel(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/cancel`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    refund(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/refund`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
    enableButtons(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/enableButtons`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('transactionsControl', (container) => {
    const initContainer = Application.getContainer('init');
    return new transactionsControlService(initContainer.httpClient, container.loginService);
});
