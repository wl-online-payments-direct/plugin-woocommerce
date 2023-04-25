import template from './sw-settings-payment-detail.html.twig';

const { Component } = Shopware;

Component.override('sw-settings-payment-detail', {
    template,

    inject: ['transactionsControl'],

    data() {
        return {
            oneyPaymentOptionValue: '',
            isOneyMethod: false
        };
    },

    methods: {
        setOneyPaymentOption(value) {
            this.oneyPaymentOptionValue = value;
            console.log('test');

            this.transactionsControl.getOneyPaymentOption({})
               .then((res) => {
                    console.log(res);
                })
                .finally(() => {
                });

            console.log('test2');

        },
    },
})
