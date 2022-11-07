const { Component, Mixin } = Shopware;
import template from './api-test-button.html.twig';

Component.register('api-test-button', {
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
                'salesChannelId': $parent.currentSalesChannelId
            }
        }
    },

    methods: {
        saveFinish() {
            this.isSaveSuccessful = false;
        },

        check() {
            this.isLoading = true;
            this.apiTest.check(this.pluginConfig).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.api-test-button.title'),
                        message: this.$tc('worldline.api-test-button.success')
                    });
                    document.querySelector('.sw-extension-config__save-action').click();
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.api-test-button.title'),
                        message: this.$tc('worldline.api-test-button.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        }
    }
})
