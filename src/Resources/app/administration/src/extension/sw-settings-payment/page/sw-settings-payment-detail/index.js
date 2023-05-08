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
            console.log(pM);
            // this.currentPaymentMethodId = pM.customFields.worldline_payment_method_id;
            this.currentPaymentMethodId = 5110;

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
                    console.log(res);
                    this.oneyPaymentOptionValue = res.value;
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
                    console.log(res);
                })
                .finally(() => {
                    this.loading = false;
                })
            ;
        },
    },
})
