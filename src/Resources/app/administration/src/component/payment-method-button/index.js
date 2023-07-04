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

        selectCheckbox() {
            document.querySelectorAll('.paymentMethod').forEach((el, number,parent) => {
                if (number === 0) {
                    el.click();
                } else if (el.checked != parent[0].checked) {
                    el.click();
                }
            })
        },

        createPaymentMethodsArray() {
            let paymentMethodsArray = [];
            document.querySelectorAll('.payment-method--container').forEach((el) => {
                paymentMethodsArray.push({
                    id: el.children[0].children[0].id,
                    status: el.children[0].children[0].checked,
                    internalId: el.children[0].children[0].getAttribute('internalId'),
                });
            })
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
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.payment-method-button.APITitle'),
                        message: this.$tc('worldline.payment-method-button.success')
                    });
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
            if (paymentMethods.length <= 5) {
                document.querySelector('.select-all').innerHTML = '';
            }
            if (paymentMethods.length === 0) {
                this.displayMessage = this.$tc('worldline.payment-method-button.requestEmpty');
            } else {
                this.displayMessage = null;
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
