import template from './orders-refunded.html.twig';

const { Component, Utils, Filter } = Shopware;
const { get, format } = Utils;


Component.register('mo-orders-refunded', {
    template,

    inject: ['transactionsControl'],

    props: {
        order: {
            type: Object,
            required: true,
        },
        paymentStatus: {
            type: Array,
            required: true,
        }
    },

    data() {
        return {
            isLoading: false,
            itemPrices: [],
            totalAmount: 0,
        };
    },

    mounted() {
        this.orderLineItems.forEach(item => {
            const price = item.unitPrice * item.refunded;
            this.itemPrices.push(price);
            this.totalAmount += price;
        });
    },

    computed: {
        orderLineItems() {
            return this.paymentStatus.filter(entry => entry.refunded > 0);
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

        currencyFilter() {
            return Filter.getByName('currency');
        },
    },
});