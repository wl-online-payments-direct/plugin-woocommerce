import template from './sw-order-detail-details.html.twig';
import './my-styles.scss'


const { Component } = Shopware;


Component.override('sw-order-detail-details', {
    template,

    inject: ['transactionsControl'],

    data() {
        return {
            activeTab: 'unprocessed',
            tabs: [
                {name: 'unprocessed'},
                {name: 'paid'},
                {name: 'refunded'},
                {name: 'canceled'},
            ],
            transactionStatus: false,
            partialOperations: false,
            transactionLogs: '',
            worldlinePaymentStatus: [],
            isLoading: false,
            isUnpaidAdminOrder: false,
            adminPayFinishUrl: '',
            adminPayErrorUrl: '',
            swAccessKey: '',
            isWorldlineOnlinePayment: false,
            lockedButtons: false,
            allowedAmounts: null,
            isReady: false,
        };
    },

    created() {
        this.getPanelConfig();
    },

    computed: {
        showPartialOperations() {
            return this.transactionStatus && this.partialOperations;
        },
        transactionId() {
            return this.order.customFields?.payment_transaction_id;
        },

        paymentMethod() {
            return this.order.transactions.last().paymentMethod.customFields.worldline_payment_method_id;
        },

        transactionStatusId() {
            return this.order.customFields?.payment_transaction_status;
        },

        getOrderId() {
            return this.order.id;
        },

        isAdminOrder() {
            return this.order.createdBy !== null;
        },

        isNoTransactionPresent() {
            return !this.transactionId;
        },

        isNoCompleteTransactionPresent() {
            if (this.isNoTransactionPresent) return true;
            return this.transactionStatusId == 0;
        },
    },

    methods: {
        setActiveTab(tab) {
            this.activeTab = tab.name;
        },

        getPanelConfig() {
            this.transactionsControl.getConfig({'orderId': this.order.id}).then((res) => {
                this.adminPayFinishUrl = res.adminPayFinishUrl;
                this.adminPayErrorUrl = res.adminPayErrorUrl;
                this.isWorldlineOnlinePayment = res.isFullRedirectMethod;
                this.swAccessKey = res.swAccessKey;
            }).finally(() => {
                this.isUnpaidAdminOrder = (this.isAdminOrder && this.isWorldlineOnlinePayment && this.isNoCompleteTransactionPresent);
                this.statusCheck();
            });
        },

        setInitialTab() {
            if (this.worldlinePaymentStatus.filter(entry => entry.unprocessed > 0).length === 0 ) {
                if (this.worldlinePaymentStatus.filter(entry => entry.paid > 0).length > 0 ) {
                    this.activeTab = 'paid';
                } else if (this.worldlinePaymentStatus.filter(entry => entry.refunded > 0).length > 0 ) {
                    this.activeTab = 'refunded';
                } else if (this.worldlinePaymentStatus.filter(entry => entry.canceled > 0).length > 0 ) {
                    this.activeTab = 'canceled';
                }
            }
        },

        getValues() {
            if (this.transactionId === null) {
                this.isLoading = false
                return;
            }
            this.transactionsControl.enableButtons({'transactionId': this.transactionId}).then((res) => {
                if (res.success) {
                    this.worldlinePaymentStatus = res.worldlinePaymentStatus;
                    this.transactionLogs = res.log;
                    this.allowedAmounts = res.allowedAmounts;
                    this.lockedButtons = res.worldlineLockButtons;
                    this.setInitialTab();
                    this.transactionStatus = true;
                    this.partialOperations = res.partialOperationsEnabled;
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.check-status-button.title'),
                        message: this.$tc('worldline.check-status-button.error') + res.message
                    });
                }
            }).finally(() => {
                this.isLoading = false;
                this.isReady = true;
            });
        },

        statusCheck() {
            this.isLoading = true;
            this.transactionsControl.statusCheck(
                {'transactionId': this.transactionId}
            ).then((res) => {
                if (res.success) {
                    this.getValues();
                } else {
                    if (!this.isUnpaidAdminOrder) {
                        this.transactionStatus = false;
                    }
                    this.isLoading = false;
                }
            });
        },

        payByAdmin() {
            this.isLoading = true;
            const payload = {
                orderId: this.getOrderId,
                finishUrl: this.adminPayFinishUrl,
                errorUrl: this.adminPayErrorUrl,
            };

            fetch('/store-api/handle-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'sw-access-key': this.swAccessKey
                },
                body: JSON.stringify(payload),
            }).then((response) => {
                if (response.ok) {
                    response.json().then(r => {
                        let popup = null
                        let invId = null
                        let invFunc = () => {
                            if (popup.closed) {
                                clearInterval(invId);
                                location.reload();
                            }
                        }
                        popup = open(r.redirectUrl);
                        invId = setInterval(invFunc, 500);
                    });
                } else {
                    console.error(response);
                    this.createNotificationError({
                        title: this.$tc('worldline.check-status-button.title'),
                        message: this.$tc('worldline.transaction-control.buttons.error')
                    });
                }
            }).catch((error) => {
                console.error(error);
                this.createNotificationError({
                    title: this.$tc('worldline.check-status-button.title'),
                    message: this.$tc('worldline.check-status-button.error') + error
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },
    },

})
