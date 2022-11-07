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

        display(content) {
            document.querySelector('.border-inner').innerHTML = content;
        },

        renderPaymentMethods(paymentMethods) {
            if (paymentMethods.length <= 5) {
                document.querySelector('.select-all').innerHTML = '';
            }
            if (paymentMethods.length == 0) {
                this.display(`<p class="innerText">${this.$tc('worldline.payment-method-button.requestEmpty')}</p>`);
            } else {
                let checkboxTemplate = paymentMethods.map((item) => {

                    return `<div class="payment-method--container">
                    <label class="switch">
                      <input type="checkbox" id="${item.id}" internalId="${item.internalId}" class="paymentMethod" ${item.isActive?'checked':''}>
                      <span class="slider round"></span>
                    </label>
                    <img src="${item.logo}">
                    <label for="${item.label}">
                    ${item.label}</label>
                    </div>`;
                }).join(" ");
                this.display(checkboxTemplate);
            }
        },

        getPaymentMethods() {
            this.display(`<p class="innerText">${this.$tc('worldline.payment-method-button.request')}</p>`);
            this.isLoading = true;
            this.apiTest.check(this.pluginConfig).then((res) => {//todo split this
                if (res.success) {
                    this.renderPaymentMethods(res.paymentMethods);
                } else {
                    this.display(`<p class="innerText">${this.$tc('worldline.payment-method-button.error')}</p>`);
                    this.createNotificationError({
                        title: this.$tc('worldline.payment-method-button.title'),
                        message: this.$tc('worldline.payment-method-button.error') + res.message
                    });
                }
                this.isLoading = false;
            }).catch((error) => {
                this.display(`<p class="innerText">${this.$tc('worldline.payment-method-button.error')}</p>`);
            });
        }
    },

    mounted() {
        this.getPaymentMethods();
    }
})
