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
    },

    methods: {
        getInitialData() {
            this.loading = true;
            this.transactionsControl.getOneyPaymentConfig({})
                .then((res) => {
                    this.oneyPaymentOptionValue = res.value;
                    document.getElementById('oneyPaymentOption').value = res.value;
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
    },
})
