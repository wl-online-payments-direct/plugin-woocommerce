const { Component, Mixin } = Shopware;
import template from './support-form.html.twig';
import '../../assets/support-form.css';

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
            createAccountCheck: true,
            attachLogCheck: true,
            emailField: '',
            descriptionField: '',
        };
    },

    created() {
        this.checkUserRights();
    },

    methods: {
        checkUserRights() {
            this.supportForm.checkUserRights(
                {
                    'userId': 'userIddd',
                }
            ).then((res) => {
                //todo if res.createUser = true
                // 1. change text of help to: this.$tc('worldline.support-form.create-account-help')
                // 2. enable checkbox
                // 3. set res.userEmail as placeholder for email field
                console.log(this.$tc('worldline.support-form.create-account-help'))
                console.log(res);
                this.isLoading = false;
            });
        },
        send() {
            this.supportForm.send(
                {
                    'createAccount': this.createAccountCheck,
                    'attachLog': this.attachLogCheck,
                    'contact': this.emailField,
                    'description': this.descriptionField,
                    //todo user id should also be sent here
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
                var url = window.location.origin + '/'+response.mediaUrl;
                const link = document.createElement("a");
                link.download = response.mediaName;
                link.href = url;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        },
    }
})
