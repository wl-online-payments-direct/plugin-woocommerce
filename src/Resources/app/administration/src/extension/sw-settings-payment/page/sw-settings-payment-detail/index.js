import template from './sw-settings-payment-detail.html.twig';

const { Component } = Shopware;

Component.override('sw-settings-payment-detail', {
    template,

    inject: ['transactionsControl'],

    data() {
        return {
            currentPaymentMethodId: '',
            oneyPaymentOptionValue: '',
            oneyIds: [5110, 5125, 5600],
            bankTransferPaymentOptionValue: '',
            bankTransferIds: [5408],
        };
    },

    created() {
        this.$watch('paymentMethod', (pM) => {
            this.currentPaymentMethodId = pM.customFields.worldline_payment_method_id;
        });
        this.getInitialData();
    },

    computed: {
        showOneyField() {
            return this.oneyIds.includes(this.currentPaymentMethodId);
        },
        showBankTransferField() {
            return this.bankTransferIds.includes(this.currentPaymentMethodId);
        },
    },

    methods: {
        getInitialData() {
            this.loading = true;
            this.transactionsControl.getPaymentPluginOption({})
                .then((res) => {
                    this.oneyPaymentOptionValue = res.oneyValue;
                    this.bankTransferPaymentOptionValue = res.bankTransferValue;
                    document.getElementById('oneyPaymentOption').value = res.oneyValue;
                    document.getElementById('bankTransferPaymentOption').value = res.bankTransferValue;
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },

        setOneyPaymentOption(value) {
            this.loading = true;
            this.oneyPaymentOptionValue = value;
            this.transactionsControl.setOneyPaymentConfig({oneyPaymentOption: this.oneyPaymentOptionValue})
                .then((res) => {
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },

        setBankTransferPaymentOption(value) {
            this.loading = true;
            this.bankTransferPaymentOptionValue = value;
            this.transactionsControl.setBankTransferPaymentConfig({bankTransferPaymentOption: this.bankTransferPaymentOptionValue})
                .then((res) => {
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },
    },
})
