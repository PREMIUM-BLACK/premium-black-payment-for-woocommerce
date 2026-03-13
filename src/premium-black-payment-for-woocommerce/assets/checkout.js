"use strict";
class PremblpaGateway {
    constructor() {
        if (PremblpaGateway.instance) {
            return PremblpaGateway.instance;
        }

        var _set = window.wc.wcSettings.getSetting('paymentMethodData', {});
        this.settings = _set['premium_black'];
        this.blockchainMap = {};
        (this.settings.blockchains || []).forEach(bc => {
            this.blockchainMap[bc.Code] = bc.Name;
        });
        this.title = window.wp.htmlEntities.decodeEntities(this.settings.title) ||
            window.wp.i18n.__('Premium Black - pay with crypto', 'premium_black');

        this.currencies = this.settings.currencies || [];
        this.transaction_currency = null;

        const { createElement } = window.wp.element;

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
        PremblpaGateway.instance = this;
    }

    _renderComponent() {
        return window.React.createElement(this.Content, {
            plugin: this,
        });
    }

    Content({ plugin, eventRegistration, emitResponse }) {
        const { createElement, useState } = window.wp.element;
        const __ = window.wp.i18n.__;

        const [step, setStep] = useState(plugin.transaction_currency ? 2 : 1);
        const [selectedBlockchain, setSelectedBlockchain] = useState(null);
        const [selectedCurrency, setSelectedCurrency] = useState(plugin.transaction_currency);
        const [searchTerm, setSearchTerm] = useState('');

        window.React.useEffect(() => {
            const unregister = eventRegistration.onPaymentSetup(async () => {
                if (!plugin.transaction_currency) {
                    return {
                        type: emitResponse.responseTypes.ERROR,
                        message: __('Please choose a currency to continue.', 'premium-black-payment-for-woocommerce'),
                        messageContext: emitResponse.noticeContexts.PAYMENTS
                    };
                }
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: { paymentMethodData: plugin.gateway.paymentData },
                };
            });
            return () => unregister();
        }, [selectedCurrency]);

        var grouped = {};
        plugin.currencies.forEach(function(curr) {
            if (!grouped[curr.Blockchain]) grouped[curr.Blockchain] = [];
            grouped[curr.Blockchain].push(curr);
        });

        var blockchainEntries = Object.keys(grouped).map(function(code) {
            return { code: code, name: plugin.blockchainMap[code] || code, currencies: grouped[code] };
        }).sort(function(a, b) {
            return a.name.localeCompare(b.name);
        });

        var filteredBlockchainEntries = blockchainEntries;
        var normalizedSearch = searchTerm.trim().toLowerCase();
        if (normalizedSearch) {
            filteredBlockchainEntries = blockchainEntries.map(function(bc) {
                var matchingCurrencies = bc.currencies.filter(function(c) {
                    return c.Name.toLowerCase().indexOf(normalizedSearch) !== -1
                        || c.Symbol.toLowerCase().indexOf(normalizedSearch) !== -1;
                });
                if (matchingCurrencies.length === 0) return null;
                return { code: bc.code, name: bc.name, currencies: matchingCurrencies };
            }).filter(Boolean);
        }

        var handleBlockchainClick = function(bcCode) {
            setSelectedBlockchain(bcCode);
            setStep(2);
        };

        var handleCurrencyClick = function(codeChain) {
            setSelectedCurrency(codeChain);
            plugin.transaction_currency = codeChain;
            plugin.gateway.paymentData = { transaction_currency: codeChain };
        };

        var handleBack = function() {
            setStep(1);
            setSelectedBlockchain(null);
            setSelectedCurrency(null);
            plugin.transaction_currency = null;
            plugin.gateway.paymentData = { transaction_currency: null };
        };

        if (step === 1) {
            return createElement('div', { className: 'premblpa-checkout' },
                createElement('div', { className: 'premblpa-picker-toolbar' },
                    createElement('p', { className: 'premblpa-checkout-label' },
                        __('Select a blockchain network:', 'premium-black-payment-for-woocommerce')
                    ),
                    createElement('div', { className: 'premblpa-picker-search' },
                        createElement('span', { className: 'premblpa-picker-search-icon' }, '\uD83D\uDD0D'),
                        createElement('input', {
                            type: 'text',
                            className: 'premblpa-picker-search-input',
                            placeholder: __('Filter by currency\u2026', 'premium-black-payment-for-woocommerce'),
                            value: searchTerm,
                            onChange: function(e) { setSearchTerm(e.target.value); }
                        })
                    )
                ),
                filteredBlockchainEntries.length > 0
                    ? createElement('div', { className: 'premblpa-picker-grid' },
                        filteredBlockchainEntries.map(function(bc) {
                            var count = bc.currencies.length;
                            var totalCount = grouped[bc.code].length;
                            var label = normalizedSearch
                                ? count + '/' + totalCount
                                : String(count);
                            label += ' ' + (totalCount === 1
                                ? __('Currency', 'premium-black-payment-for-woocommerce')
                                : __('Currencies', 'premium-black-payment-for-woocommerce'));
                            return createElement('button', {
                                key: bc.code,
                                type: 'button',
                                className: 'premblpa-picker-tile premblpa-picker-tile--bc',
                                onClick: function() { handleBlockchainClick(bc.code); }
                            },
                                createElement('span', { className: 'premblpa-picker-tile-name' }, bc.name),
                                createElement('span', { className: 'premblpa-picker-tile-count' }, label)
                            );
                        })
                    )
                    : createElement('div', { className: 'premblpa-picker-empty' },
                        __('No blockchains found for', 'premium-black-payment-for-woocommerce') + ' "' + searchTerm.trim() + '"'
                    )
            );
        }

        var currentBcName = plugin.blockchainMap[selectedBlockchain] || selectedBlockchain;
        var bcCurrencies = grouped[selectedBlockchain] || [];

        return createElement('div', { className: 'premblpa-checkout' },
            createElement('div', { className: 'premblpa-picker-header' },
                createElement('button', {
                    type: 'button',
                    className: 'premblpa-picker-back',
                    onClick: handleBack
                },
                    createElement('span', { className: 'premblpa-picker-back-arrow' }, '\u2039'),
                    __('Back', 'premium-black-payment-for-woocommerce')
                ),
                createElement('span', { className: 'premblpa-picker-header-title' }, currentBcName)
            ),
            createElement('p', { className: 'premblpa-checkout-label' },
                __('Select a currency:', 'premium-black-payment-for-woocommerce')
            ),
            createElement('div', { className: 'premblpa-picker-grid' },
                bcCurrencies.map(function(currency) {
                    var isSelected = selectedCurrency === currency.CodeChain;
                    var cls = 'premblpa-picker-tile premblpa-picker-tile--currency';
                    if (isSelected) cls += ' premblpa-picker-tile--selected';
                    return createElement('button', {
                        key: currency.CodeChain,
                        type: 'button',
                        className: cls,
                        onClick: function() { handleCurrencyClick(currency.CodeChain); }
                    },
                        createElement('span', { className: 'premblpa-picker-tile-symbol' }, currency.Symbol.toUpperCase()),
                        createElement('span', { className: 'premblpa-picker-tile-name' }, currency.Name)
                    );
                })
            ),
            selectedCurrency ? createElement('div', { className: 'premblpa-picker-selection' },
                createElement('span', { className: 'premblpa-picker-selection-check' }, '\u2713'),
                createElement('span', null,
                    bcCurrencies.filter(function(c) { return c.CodeChain === selectedCurrency; })
                        .map(function(c) { return c.Name + ' (' + c.Symbol.toUpperCase() + ')'; })[0]
                )
            ) : null
        );
    }
}

new PremblpaGateway();