import template from './orders-paid.html.twig';

const { Component, Utils, Mixin } = Shopware;
const { get, format } = Utils;


Component.register('mo-orders-paid', {
    template,

    inject: ['transactionsControl'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        order: {
            type: Object,
            required: true,
        },
        transactionId: {
            type: String,
            required: true,
        },
        paymentStatus: {
            type: Array,
            required: true,
        },
        maxRefund: {
            type: Number
        },
        lockedButton: {
            type: Boolean,
            required: true,
        }
    },

    data() {
        return {
            transactionSuccess: false,
            Selection: [],
            isLoading: false,
            itemPrices: [],
            amountToProcess: 0,
        };
    },

    mounted() {
        for (let i = 0; i < this.orderLineItems.length; i++) {
            this.itemPrices.push(0);
            this.Selection.push(0);
        }
    },

    computed: {
        maxAmountToProcess() {
            return !isNaN(this.maxRefund) ?
                this.maxRefund :
                this.paymentStatus.reduce((accumulator, currentValue) => accumulator + (currentValue.paid * currentValue.unitPrice), 0);
        },

        orderLineItems() {
            return this.paymentStatus.filter(entry => entry.paid > 0);
        },

        hasContent() {
            return this.orderLineItems.length > 0;
        },

        taxStatus() {
            return get(this.order, 'taxStatus', '');
        },

        unitPriceLabel() {
            return this.$tc('worldline.transaction-control.table.unitPrice');
        },

        linePriceLabel() {
            return this.taxStatus === 'gross' ?
                'sw-order.detailBase.columnTotalPriceGross' :
                'sw-order.detailBase.columnTotalPriceNet';
        },

        getLineItemColumns() {
            const columnDefinitions = [{
                property: 'quantity',
                dataIndex: 'paid',
                label: 'sw-order.detailBase.columnQuantity',
                allowResize: false,
                align: 'right',
                width: '100px',
            }, {
                property: 'refund',
                dataIndex: 'paid',
                label: 'worldline.transaction-control.table.selected',
                allowResize: false,
                align: 'right',
                width: '150px',
            },{
                property: 'label',
                dataIndex: 'label',
                label: 'sw-order.detailBase.columnProductName',
                allowResize: false,
                primary: true,
                multiLine: true,
            }, {
                property: 'unitPrice',
                dataIndex: 'unitPrice',
                label: this.unitPriceLabel,
                allowResize: false,
                align: 'right',
                width: '120px',
            }];

            return [...columnDefinitions, {
                property: 'totalPrice',
                dataIndex: 'totalPrice',
                label: this.linePriceLabel,
                allowResize: false,
                align: 'right',
                width: '120px',
            }];
        },

        payloadItems() {
            const payload = [];
            this.orderLineItems.forEach((item, index) => {
                payload.push({
                    id: item.id,
                    quantity: this.Selection[index],
                });
            });
            return payload;
        },
    },

    methods: {
        refund() {
            const payload = {
                transactionId: this.transactionId,
                amount: this.amountToProcess,
                items: this.payloadItems,
            }
            this.transactionsControl.refund(payload)
                .then((res) => {
                    if (res.success) {
                        this.transactionSuccess = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.createNotificationError({
                            title: this.$tc('worldline.capture-payment-button.title'),
                            message: this.$tc('worldline.capture-payment-button.error') + res.message
                        });
                    }
                })
                .finally(() => {
                });
        },

        setPriceSum() {
            this.amountToProcess = Math.min(this.itemPrices.reduce((accumulator, currentValue) => accumulator + currentValue), this.maxAmountToProcess);
        },

        setLinePrice(index, amount, price) {
            this.itemPrices[index] = price * amount;
            this.setPriceSum();
        },

        setCustomAmount(value) {
            this.amountToProcess = value;
        },
    },
});