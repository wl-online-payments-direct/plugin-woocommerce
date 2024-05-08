const { Component, Mixin } = Shopware;
import template from './config-tabs.html.twig';

Component.register('config-tabs', {
    template,

    props: ['label'],
    inject: ['apiTest'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            sandboxClasses: [
                'sw-system-config--field-mopt-worldline-config-merchant-id',
                'sw-system-config--field-mopt-worldline-config-api-key',
                'sw-system-config--field-mopt-worldline-config-api-secret',
                'sw-system-config--field-mopt-worldline-config-webhook-key',
                'sw-system-config--field-mopt-worldline-config-webhook-secret',
                'sw-system-config--field-mopt-worldline-config-sandbox-endpoint',
                'sw-system-config--field-mopt-worldline-config-main-return-server-url',
                'sw-system-config--field-mopt-worldline-config-sandbox-api-test'
            ],
            liveClasses: [
                'sw-system-config--field-mopt-worldline-config-live-merchant-id',
                'sw-system-config--field-mopt-worldline-config-live-api-key',
                'sw-system-config--field-mopt-worldline-config-live-api-secret',
                'sw-system-config--field-mopt-worldline-config-live-webhook-key',
                'sw-system-config--field-mopt-worldline-config-live-webhook-secret',
                'sw-system-config--field-mopt-worldline-config-live-endpoint',
                'sw-system-config--field-mopt-worldline-config-live-main-return-server-url',
                'sw-system-config--field-mopt-worldline-config-live-api-test'
            ],
        };
    },

    computed: {
    },

    methods: {
        hideCredentials() {
            this.sandboxClasses.forEach(item => {
                document.getElementsByClassName(item)[0].style.display = 'none';
            });
            this.liveClasses.forEach(item => {
                document.getElementsByClassName(item)[0].style.display = 'none';
            });
        },
        showSandboxCredentials() {
            this.sandboxClasses.forEach(item => {
                document.getElementsByClassName(item)[0].style.display = 'block';
            });
            this.liveClasses.forEach(item => {
                document.getElementsByClassName(item)[0].style.display = 'none';
            });
        },
        showLiveCredentials() {
            this.sandboxClasses.forEach(item => {
                document.getElementsByClassName(item)[0].style.display = 'none';
            });
            this.liveClasses.forEach(item => {
                document.getElementsByClassName(item)[0].style.display = 'block';
            });
        }
    },

    mounted() {
        this.hideCredentials();
    }
})
