import template from './sw-order-detail-base.html.twig';

const { Component } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    inject: ['transactionsControl'],
    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
        };
    },
     computed: {
            pluginConfig() {
                return {
                    'url': window.location.href
                }
            }
        },
    methods: {
        statusCheck() {
            this.isLoading = true;
            this.transactionsControl.statusCheck(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.check-status-button.title'),
                        message: this.$tc('worldline.check-status-button.success')
                    });
                    location.reload();
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.check-status-button.title'),
                        message: this.$tc('worldline.check-status-button.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        },

        capture() {
            this.isLoading = true;
            this.transactionsControl.capture(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.capture-payment-button.title'),
                        message: this.$tc('worldline.capture-payment-button.success')
                    });
                    location.reload();
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.capture-payment-button.title'),
                        message: this.$tc('worldline.capture-payment-button.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        },
        cancel() {
            this.isLoading = true;
            this.transactionsControl.cancel(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.cancel-payment-button.title'),
                        message: this.$tc('worldline.cancel-payment-button.success')
                    });
                    location.reload();
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.cancel-payment-button.title'),
                        message: this.$tc('worldline.cancel-payment-button.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        },
        refund() {
            this.isLoading = true;
            this.transactionsControl.refund(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.refund-payment-button.title'),
                        message: this.$tc('worldline.refund-payment-button.success')
                    });
                    location.reload();
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.refund-payment-button.title'),
                        message: this.$tc('worldline.refund-payment-button.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        },
        enableButtons() {
            this.transactionsControl.enableButtons(this.pluginConfig).then((res) => {
                if (res.message.length > 0) {
                    console.log(res.message);
                    for (const element of res.message) {
                        console.log(element);
                        document.getElementById(element).disabled = false;

                    }
                }
            });
        }
    },
    updated() {
        //Disabling buttons via twig add some classes and make enabling much more complicated
        document.getElementById("WorldlineBtnCapture").disabled = true;
        document.getElementById("WorldlineBtnCancel").disabled = true;
        document.getElementById("WorldlineBtnRefund").disabled = true;
        this.enableButtons();
    }
});
