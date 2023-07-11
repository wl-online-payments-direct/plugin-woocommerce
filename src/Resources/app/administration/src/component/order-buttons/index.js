import template from './order-buttons.html.twig';

const { Component, Utils, Mixin } = Shopware;
const { get, format } = Utils;


Component.register('mo-order-buttons', {
    template,

    inject: ['transactionsControl'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        order: {
            type: Object,
            required: true,
        },
        transactionId: {
            type: String,
            required: true,
        },
        paymentStatus: {
            type: Array,
            required: true,
        },
        maxCapture: {
            type: Number,
            required: true,
        },
        maxRefund: {
            type: Number
        },
    },

    data() {
        return {
            transactionSuccess: {
                capture: false,
                cancel: false,
                refund: false,
            },
            isLoading: false,
        };
    },


    computed: {
        unprocessedAmount() {
            return !isNaN(this.maxCapture) ?
                this.maxCapture :
                this.paymentStatus.reduce((accumulator, currentValue) => accumulator + (currentValue.unprocessed * currentValue.unitPrice), 0);
        },

        paidAmount() {
            return !isNaN(this.maxRefund) ?
                this.maxRefund :
                this.paymentStatus.reduce((accumulator, currentValue) => accumulator + (currentValue.paid * currentValue.unitPrice), 0);
        },

        unprocessedLineItems() {
            return this.paymentStatus.filter(entry => entry.unprocessed > 0);
        },

        paidLineItems() {
            return this.paymentStatus.filter(entry => entry.paid > 0);
        },

        unprocessedPayload() {
            const payload = [];
            this.unprocessedLineItems.forEach((item) => {
                payload.push({
                    id: item.id,
                    quantity: item.unprocessed,
                });
            });
            return payload;
        },

        paidPayload() {
            const payload = [];
            this.paidLineItems.forEach((item) => {
                payload.push({
                    id: item.id,
                    quantity: item.paid,
                });
            });
            return payload;
        },
    },

    methods: {
        capture() {
            const payload = {
                transactionId: this.transactionId,
                amount: this.unprocessedAmount,
                items: this.unprocessedPayload,
            }
            console.log(payload);
            return;
            //@todo can hit submit without entering 3digit code on payment order page
            this.transactionsControl.capture(payload)
                .then((res) => {
                    if (res.success) {
                        this.transactionSuccess.capture = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.createNotificationError({
                            title: this.$tc('worldline.capture-payment-button.title'),
                            message: this.$tc('worldline.capture-payment-button.error') + res.message
                        });
                    }
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        cancel() {
            this.isLoading = true;
            const payload = {
                transactionId: this.transactionId,
                amount: this.unprocessedAmount,
                items: this.unprocessedPayload,
            }
            console.log(payload);
            return;
            this.transactionsControl.cancel(payload)
                .then((res) => {
                    if (res.success) {
                        this.transactionSuccess.cancel = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.createNotificationError({
                            title: this.$tc('worldline.refund-payment-button.title'),
                            message: this.$tc('worldline.refund-payment-button.error') + res.message
                        });
                    }
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

        refund() {
            const payload = {
                transactionId: this.transactionId,
                amount: this.paidAmount,
                items: this.paidPayload,
            }
            console.log(payload);
            return;
            this.transactionsControl.refund(payload)
                .then((res) => {
                    if (res.success) {
                        this.transactionSuccess.refund = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.createNotificationError({
                            title: this.$tc('worldline.capture-payment-button.title'),
                            message: this.$tc('worldline.capture-payment-button.error') + res.message
                        });
                    }
                })
                .finally(() => {
                    this.isLoading = false;
                });
        },

    },

})
