import template from './orders-unprocessed.html.twig';

const { Component, Utils, Mixin } = Shopware;
const { get, format } = Utils;


Component.register('mo-orders-unprocessed', {
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
        maxCapture: {
            type: Number,
            required: true,
        },
        lockedButton: {
            type: Boolean,
            required: true,
        }
    },

    data() {
        return {
            transactionSuccess: {
                capture: false,
                cancel: false,
            },
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
        totalSelections() {
            return this.Selection.reduce((prev, current) => prev + current, 0);
        },

        maxAmountToProcess() {
            return this.maxCapture;
        },

        orderLineItems() {
            return this.paymentStatus.filter(entry => entry.unprocessed > 0);
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
                dataIndex: 'unprocessed',
                label: 'sw-order.detailBase.columnQuantity',
                allowResize: false,
                align: 'right',
                width: '100px',
            },{
                property: 'unprocessed',
                dataIndex: 'unprocessed',
                label: 'worldline.transaction-control.table.selected',
                allowResize: false,
                align: 'right',
                width: '150px',
            }, {
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

        transactionDisabled() {
            return (this.amountToProcess === 0) && (this.totalSelections === 0);
        },
    },

    methods: {
        capture() {
            const payload = {
                transactionId: this.transactionId,
                amount: this.amountToProcess,
                items: this.payloadItems,
            }
            this.transactionsControl.capture(payload)
                .then((res) => {
                    if (res.success) {
                        this.transactionSuccess.capture = true;
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

        cancel() {
            this.isLoading = true;
            const payload = {
                transactionId: this.transactionId,
                amount: this.amountToProcess,
                items: this.payloadItems,
            }
            this.transactionsControl.cancel(payload)
                .then((res) => {
                    if (res.success) {
                        this.transactionSuccess.cancel = true;
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        this.createNotificationError({
                            title: this.$tc('worldline.cancel-payment-button.title'),
                            message: this.$tc('worldline.cancel-payment-button.error') + res.message
                        });
                    }
                })
                .finally(() => {
                    this.isLoading = false;
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

})
