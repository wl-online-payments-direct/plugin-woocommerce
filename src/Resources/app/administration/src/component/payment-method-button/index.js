const { Component, Mixin } = Shopware;
import template from './payment-method-button.html.twig';
import '../../assets/payment-method-button.less';

Component.register('payment-method-button', {
    template,

    props: ['label'],
    inject: ['apiTest'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            paymentMethodData: [],
            displayMessage: null,
        };
    },

    computed: {
        pluginConfig() {
            let $parent = this.$parent;
            while ($parent.actualConfigData === undefined) {
                $parent = $parent.$parent;
            }
            return {
                'сonfigData': $parent.actualConfigData,
                'salesChannelId': $parent.currentSalesChannelId,
                'responseArray': this.createPaymentMethodsArray()
            }
        }
    },

    methods: {

        getSalesChannelId() {
            let $parent = this.$parent;
            while ($parent.currentSalesChannelId === undefined) {
                $parent = $parent.$parent;
            }
            return  $parent.currentSalesChannelId;
        },

        selectAllCheckbox(shouldBeActive) {
            this.paymentMethodData.forEach(item => {
                item.isActive = shouldBeActive;
            });
            this.saveButton();
        },

        createPaymentMethodsArray() {
            let paymentMethodsArray = [];
            this.paymentMethodData.forEach(item => {
                paymentMethodsArray.push({
                    id: item.id,
                    status: item.isActive,
                    internalId: item.internalId,
                });
            });
            return paymentMethodsArray;
        },

        saveButton() {
            this.isLoading = true;
            this.apiTest.savemethod(
            {
                'data': this.createPaymentMethodsArray(),
                'salesChannelId': this.getSalesChannelId()
            }
            ).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.payment-method-button.APITitle'),
                        message: this.$tc('worldline.payment-method-button.errorAPI') + res.message
                    });
                }

                this.isLoading = false;
            });

        },

        renderPaymentMethods(paymentMethods) {
            if (paymentMethods.length === 0) {
                this.displayMessage = this.$tc('worldline.payment-method-button.requestEmpty');
            } else {
                this.displayMessage = null;
                this.paymentMethodData = [];
                paymentMethods.forEach((item) => {
                    this.paymentMethodData.push({
                        id: item.id,
                        internalId: item.internalId,
                        isActive: item.isActive,
                        logo: item.logo,
                        label: item.label,
                    });
                });
            }
        },

        getPaymentMethods() {
            this.displayMessage = this.$tc('worldline.payment-method-button.request');
            this.isLoading = true;
            this.apiTest.check(this.pluginConfig).then((res) => {//todo  split this
                if (res.success) {
                    this.renderPaymentMethods(res.paymentMethods);
                } else {
                    this.displayMessage = this.$tc('worldline.payment-method-button.error');
                    this.createNotificationError({
                        title: this.$tc('worldline.payment-method-button.title'),
                        message: this.$tc('worldline.payment-method-button.error') + res.message
                    });
                }
                this.isLoading = false;
            }).catch((error) => {
                this.displayMessage = this.$tc('worldline.payment-method-button.error');
                this.isLoading = false;
            });
        }
    },

    mounted() {
        this.getPaymentMethods();
    }
})
