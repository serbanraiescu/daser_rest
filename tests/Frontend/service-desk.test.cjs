const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

function terminal(response = { ok: true, data: { success: true } }, options = {}) {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/service/index.blade.php'), 'utf8')
        .match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/\{\{ route\('service.orders.store'\) \}\}/g, '/service/orders');
    const calls = [];
    const alerts = [];
    let reloads = 0;
    let currentUrl = options.url || 'https://service.test/service?order=42';
    const context = vm.createContext({
        URL,
        fetch: async (url, options) => {
            calls.push({ url, body: options.body ? JSON.parse(options.body) : null });
            if (response.waitFor) await response.waitFor;
            return { ok: response.ok, json: async () => response.data };
        },
        document: { querySelector: () => ({ content: 'test-csrf' }) },
        window: {
            location: { get href() { return currentUrl; }, reload: () => reloads++ },
            history: { state: null, replaceState: (state, title, url) => { currentUrl = url; } },
        },
        alert: message => alerts.push(message),
        confirm: () => true,
    });
    vm.runInContext(source, context);
    const customer = { id: 7, name: 'Client firmă', fiscal_code: 'TEST7', phone: '0700000000' };
    const desk = context.serviceDesk([], options.openOrders || [], [customer]);
    desk.orderId = 42;
    desk.cart = [{ service_item_id: 3, unit_price: 100, quantity: 2 }];
    return { desk, customer, calls, alerts, reloads: () => reloads, url: () => currentUrl };
}

test('account checkout opens client selection and sends the saved client on confirmation', async () => {
    const { desk, customer, calls, reloads } = terminal();
    desk.showPaymentModal = true;
    desk.choosePaymentMethod('client_account');
    assert.equal(desk.showCustomerModal, true);
    assert.equal(desk.showPaymentModal, false);
    desk.customerSearch = 'test7';
    assert.equal(desk.filteredCustomers().length, 1);
    desk.chooseCustomer(customer);
    await desk.finalizePayment();
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/service/orders/42/complete');
    assert.equal(calls[0].body.payment_method, 'client_account');
    assert.equal(calls[0].body.service_customer_id, 7);
    assert.equal(reloads(), 1);
});

test('a rejected completion keeps the saved order for retry without duplicating a work sheet', async () => {
    const { desk, customer, calls, alerts, reloads } = terminal({
        ok: false, data: { errors: { service_customer_id: ['Clientul nu mai este activ.'] } },
    });
    desk.paymentMethod = 'client_account';
    desk.chooseCustomer(customer);
    await desk.finalizePayment();
    assert.equal(desk.orderId, 42);
    assert.equal(desk.cart.length, 1);
    assert.equal(desk.busy, false);
    assert.equal(reloads(), 0);
    assert.equal(alerts[0], 'Clientul nu mai este activ.');
    await desk.finalizePayment();
    assert.equal(calls.length, 2);
    assert.equal(calls.every(call => call.url === '/service/orders/42/complete'), true);
});

test('resuming a saved account draft preserves the selected customer; a missing client cannot complete', async () => {
    const { desk, calls } = terminal();
    desk.loadOrder({ id: 42, payment_method: 'client_account', service_customer_id: 7, items: [] });
    assert.equal(desk.paymentMethod, 'client_account');
    assert.equal(desk.selectedCustomer().id, 7);
    desk.serviceCustomerId = 999;
    await desk.finalizePayment();
    assert.equal(calls.length, 0);
    assert.equal(desk.showCustomerModal, true);
});

function savedOrder(id = 42) {
    return {
        id, status: 'open', vehicle_number: 'TEST-01', customer_name: 'Client salvat',
        items: [{ service_item_id: 3, name: 'Montaj', unit_price: 100, quantity: 2 }],
    };
}

test('carpet area accepts decimal commas and points and checkout saves square metres', async () => {
    const order = {
        id: 42, total: 12.5,
        items: [{ service_item_id: 21, name: 'Covoare MP 5', unit_price: 5, quantity: 2.5, unit: 'm²' }],
    };
    const { desk, calls } = terminal({ ok: true, data: { success: true, order } });
    desk.cart = [];
    const carpet = { id: 21, name: 'Covoare MP 5', price: 5, unit: 'm²' };
    desk.addToCart(carpet);
    for (const area of ['2.50', '2,50']) {
        desk.cart[0].quantity = area;
        assert.equal(desk.total(), 12.5);
    }
    await desk.saveOrder(true);
    assert.equal(calls[0].body.items[0].quantity, 2.5);
    assert.equal(desk.showPaymentModal, true);

    desk.addToCart(carpet);
    assert.equal(desk.cart[0].quantity, 3.5);
    assert.equal(desk.total(), 17.5);
});

test('checkout displays the saved catalog snapshots and blocks cart changes while saving', async () => {
    let release;
    const waitFor = new Promise(resolve => { release = resolve; });
    const order = {
        id: 42, total: 175,
        items: [{ service_item_id: 3, name: 'Covoare MP 70', unit: 'm²', quantity: 2.5, unit_price: 70, notes: 'Intrare' }],
    };
    const { desk, calls, reloads } = terminal({ ok: true, waitFor, data: { success: true, order } });
    desk.cart = [{ service_item_id: 3, name: 'Tarif vechi', unit: 'buc.', quantity: '2,50', unit_price: 60, notes: 'Intrare' }];
    desk.vehicleNumber = 'TEST-42';
    desk.customerName = 'Client salvat';
    desk.paymentMethod = 'card';
    desk.paymentNotes = 'Notă plată';
    const saving = desk.saveOrder(true);
    assert.equal(desk.busy, true);
    desk.addToCart({ id: 9, name: 'Alt serviciu', price: 100, unit: 'buc.' });
    desk.addToCart({ id: 3, name: 'Tarif vechi', price: 60, unit: 'buc.' });
    desk.changeQuantity(3, -1);
    assert.equal(desk.cart.length, 1);
    assert.equal(desk.cart[0].quantity, '2,50');
    release();
    await saving;
    assert.equal(calls[0].body.items[0].quantity, 2.5);
    assert.equal(desk.cart[0].name, 'Covoare MP 70');
    assert.equal(desk.cart[0].unit, 'm²');
    assert.equal(desk.cart[0].unit_price, 70);
    assert.equal(desk.cart[0].quantity, 2.5);
    assert.equal(desk.cart[0].notes, 'Intrare');
    assert.equal(desk.total(), order.total);
    assert.equal(desk.openOrders[0].total, order.total);
    assert.equal(desk.vehicleNumber, 'TEST-42');
    assert.equal(desk.customerName, 'Client salvat');
    assert.equal(desk.paymentMethod, 'card');
    assert.equal(desk.paymentNotes, 'Notă plată');
    assert.equal(desk.busy, false);
    assert.equal(desk.showPaymentModal, true);
    assert.equal(reloads(), 0);
});

test('a rejected save keeps the unsaved cart and existing sheet available for correction', async () => {
    const { desk, alerts, reloads, url } = terminal({
        ok: false, data: { errors: { 'items.0.quantity': ['Cantitate invalidă.'] } },
    });
    desk.cart = [{ service_item_id: 21, name: 'Covoare MP 5', unit: 'm²', quantity: '2,50', unit_price: 5, notes: 'Covor mic' }];
    const before = JSON.stringify(desk.cart);
    await desk.saveOrder(true);
    assert.equal(JSON.stringify(desk.cart), before);
    assert.equal(desk.orderId, 42);
    assert.equal(new URL(url()).searchParams.get('order'), '42');
    assert.equal(desk.showPaymentModal, false);
    assert.equal(desk.busy, false);
    assert.equal(alerts[0], 'Cantitate invalidă.');
    assert.equal(reloads(), 0);
});

test('invalid carpet areas keep the sheet editable and block both saving and collecting', async () => {
    const { desk, calls } = terminal();
    desk.cart = [{ service_item_id: 21, unit: 'm²', unit_price: 5, quantity: 1 }];
    for (const area of ['', '0', '-1', '1,234', 'abc', '100000000', '20000000']) {
        desk.cart[0].quantity = area;
        await desk.saveOrder(false);
        await desk.saveOrder(true);
        assert.equal(desk.showPaymentModal, false);
        assert.equal(desk.cart[0].quantity, area);
    }
    assert.equal(calls.length, 0);
    assert.equal(desk.busy, false);
    desk.cart = [
        { service_item_id: 21, unit: 'm²', unit_price: 5, quantity: 15000000 },
        { service_item_id: 22, unit: 'm²', unit_price: 5, quantity: 15000000 },
    ];
    await desk.saveOrder(true);
    assert.equal(calls.length, 0);
});

test('resumed fractional lines round independently and piece counters still add and remove', () => {
    const { desk } = terminal();
    desk.loadOrder({ id: 42, items: [
        { service_item_id: 21, name: 'Covoare', unit: 'm²', quantity: '2.50', unit_price: 5 },
        { service_item_id: 22, name: 'Manoperă', unit: 'oră', quantity: '2.50', unit_price: 4.01 },
    ] });
    assert.equal(desk.total(), 22.53);
    const tire = { id: 23, name: 'ANVELOPE SH 70', price: 70, unit: 'buc.' };
    desk.addToCart(tire);
    desk.addToCart(tire);
    assert.equal(desk.total(), 162.53);
    desk.changeQuantity(tire.id, -1);
    desk.changeQuantity(tire.id, -1);
    assert.equal(desk.total(), 22.53);
    assert.equal(desk.cart.length, 2);
});

test('refresh after saving resumes the same work sheet and the next save updates its ID', async () => {
    const order = savedOrder();
    const first = terminal({ ok: true, data: { success: true, order } }, { url: 'https://service.test/service' });
    first.desk.orderId = null;
    await first.desk.saveOrder(true);
    assert.equal(first.calls[0].body.order_id, null);
    assert.equal(new URL(first.url()).searchParams.get('order'), '42');
    assert.equal(first.desk.openOrders[0].id, 42);

    const refreshed = terminal({ ok: true, data: { success: true, order } }, {
        url: first.url(), openOrders: [savedOrder(99), order],
    });
    refreshed.desk.init();
    assert.equal(refreshed.desk.orderId, 42);
    assert.equal(refreshed.desk.vehicleNumber, 'TEST-01');
    assert.equal(refreshed.desk.total(), 200);
    assert.equal(refreshed.calls.length, 0);
    await refreshed.desk.saveOrder(false);
    assert.equal(refreshed.calls[0].body.order_id, 42);
    assert.equal(new URL(refreshed.url()).searchParams.get('order'), '42');
});

test('each tab resumes its own sheet and a stale reference does not open a different sheet', () => {
    const openOrders = [savedOrder(42), savedOrder(99)];
    for (const id of [42, 99]) {
        const tab = terminal(undefined, { url: `https://service.test/service?order=${id}`, openOrders });
        tab.desk.init();
        assert.equal(tab.desk.orderId, id);
    }
    for (const id of ['404', 'invalid', '']) {
        const tab = terminal(undefined, { url: `https://service.test/service?lang=ro&order=${id}#desk`, openOrders });
        tab.desk.orderId = null;
        tab.desk.cart = [];
        tab.desk.init();
        assert.equal(tab.desk.orderId, null);
        assert.equal(tab.desk.cart.length, 0);
        assert.equal(new URL(tab.url()).searchParams.has('order'), false);
        assert.equal(new URL(tab.url()).searchParams.get('lang'), 'ro');
        assert.equal(new URL(tab.url()).hash, '#desk');
    }
});

test('only successful completion or cancellation clears the active reference', async () => {
    for (const method of ['finalizePayment', 'cancelOrder']) {
        const failed = terminal({ ok: false, data: { message: 'Eroare temporară' } });
        await failed.desk[method]();
        assert.equal(new URL(failed.url()).searchParams.get('order'), '42');
        assert.equal(failed.reloads(), 0);
        const success = terminal();
        await success.desk[method]();
        assert.equal(new URL(success.url()).searchParams.has('order'), false);
        assert.equal(success.reloads(), 1);
    }
});

test('new work sheet clears the active reference but switching is blocked while a request runs', () => {
    const tab = terminal();
    tab.desk.busy = true;
    tab.desk.clearForm();
    tab.desk.loadOrder(savedOrder(99));
    assert.equal(tab.desk.orderId, 42);
    assert.equal(new URL(tab.url()).searchParams.get('order'), '42');
    tab.desk.busy = false;
    tab.desk.clearForm();
    assert.equal(tab.desk.orderId, null);
    assert.equal(tab.desk.cart.length, 0);
    assert.equal(new URL(tab.url()).searchParams.has('order'), false);
});
