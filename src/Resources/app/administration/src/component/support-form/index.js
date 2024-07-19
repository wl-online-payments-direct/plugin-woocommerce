const { Component, Mixin } = Shopware;
import template from './support-form.html.twig';

Component.register('support-form', {
    template,

    props: ['label'],
    inject: ['supportForm'],

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
    },

    methods: {
        send() {
            this.supportForm.send(
                {
                    'createAccount': document.getElementById('mo-support-createAccount').checked,
                    'attachLog': document.getElementById('mo-support-attachLog').checked,
                    'description': document.getElementById('mo-support-description').value,
                }
            ).then((res) => {
                if (res.success) {
                    this.isSaveSuccessful = true;
                    this.createNotificationSuccess({
                        title: this.$tc('worldline.support-form.title'),
                        message: this.$tc('worldline.support-form.success')
                    });
                } else {
                    this.createNotificationError({
                        title: this.$tc('worldline.support-form.title'),
                        message: this.$tc('worldline.support-form.error') + res.message
                    });
                }

                this.isLoading = false;
            });
        }
    }
})
