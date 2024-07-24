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
                    'contact': document.getElementById('mo-support-contact').value,
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
        },
        downloadLog() {
            this.supportForm.downloadLog(
            ).then((response) => {
                if (response.data) {
                    const filename = response.headers['content-disposition'].split('filename=')[1];
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(response.data);
                    link.download = filename;
                    link.dispatchEvent(new MouseEvent('click'));
                    link.parentNode.removeChild(link);
                }
            });
        }
    }
})
