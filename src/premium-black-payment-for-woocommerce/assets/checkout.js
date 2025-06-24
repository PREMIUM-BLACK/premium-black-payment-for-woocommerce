"use strict";
class PremiumBlackGateway {
    constructor() {
        if (PremiumBlackGateway.instance) {
            return PremiumBlackGateway.instance;
        }

        var _set = window.wc.wcSettings.getSetting('paymentMethodData', {})
        this.settings = _set['premium_black'];
        this.blockchainMap = {};
        (this.settings.blockchains || []).forEach(bc => {
            this.blockchainMap[bc.Code] = bc.Name;
        });
        this.title = window.wp.htmlEntities.decodeEntities(this.settings.title) ||
            window.wp.i18n.__('Premium Black - pay with crypto', 'premium_black');

        this.currencies = this.settings.currencies || [];
        this.transaction_currency = null;

        const { createElement, useState } = window.wp.element;

        this.gateway = {
            name: 'premium_black',
            content: this._renderComponent(),
            edit: window.wp.element.createElement(this.Content.bind(this)),
            canMakePayment: () => this.settings.is_configured,
            ariaLabel: this.title,
            supports: {
                features: this.settings.supports,
            },
            paymentData: {
                transaction_currency: this.transaction_currency
            },
            label: createElement(() =>
                createElement(
                    "span",
                    null,
                    createElement("img", {
                        src: this.settings.icon,
                        alt: this.title,
                    }),
                    "  " + this.title
                )
            ),

        };

        window.wc.wcBlocksRegistry.registerPaymentMethod(this.gateway);

        PremiumBlackGateway.instance = this;
    }

    // Komponente rendern
    _renderComponent() {
        const self = this;
        return window.React.createElement(this.Content, {
            plugin: this,
        });
    }


    Content({ plugin, eventRegistration, emitResponse }) {
        const [paymentData, setPaymentData] = window.React.useState({});
        const { createElement, useState } = window.wp.element;
        const [selectedCurrency, setSelectedCurrency] = useState(plugin.transaction_currency);

        window.React.useEffect(() => {

            const unregister = eventRegistration.onPaymentSetup(async () => {


                if (!plugin.transaction_currency) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: 'Please choose a currency to continue.',
                        messageContext: emitResponse.noticeContexts.PAYMENTS
                    };
                }


                const data = plugin.gateway.paymentData;
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: { paymentMethodData: data },
                };
            });

            return () => unregister();
        }, [paymentData]);


        const handleChange = (event) => {
            const value = event.target.value;
            setSelectedCurrency(value);

            plugin.transaction_currency = value;

            plugin.gateway.paymentData = {
                transaction_currency: value
            };
        };

        // Gruppieren nach Chain
        const grouped = plugin.currencies.reduce((acc, curr) => {
            if (!acc[curr.Blockchain]) acc[curr.Blockchain] = [];
            acc[curr.Blockchain].push(curr);
            return acc;
        }, {});

        return createElement('div', {},
            createElement('label', { htmlFor: 'crypto-select' }, plugin.settings.description),
            createElement('select', {
                id: 'crypto-select',
                name: 'transaction_currency',
                onChange: handleChange,
                value: selectedCurrency || ''
            },
                createElement('option', { value: '' }, '-- Please choose --'),
                Object.entries(grouped)
                    .sort(([a], [b]) => {
                        const nameA = plugin.blockchainMap[a] || a;
                        const nameB = plugin.blockchainMap[b] || b;
                        return nameA.localeCompare(nameB);
                    })
                    .map(([blockchainCode, currencies]) =>
                        createElement(
                            'optgroup',
                            { label: (plugin.blockchainMap[blockchainCode] || blockchainCode) },
                            currencies.map((currency) =>
                                createElement('option', { value: currency.CodeChain }, currency.Name + ' (' + currency.Symbol.toUpperCase() + ')')
                            )
                        )
                    )
            )
        );
    }

}

// Singleton-Instanz erzeugen (nur einmal)
new PremiumBlackGateway();