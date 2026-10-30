const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

function terminal(response = { ok: true, data: { success: true } }) {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/service/index.blade.php'), 'utf8')
        .match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/\{\{ route\('service.orders.store'\) \}\}/g, '/service/orders');
    const calls = [];
    const alerts = [];
    let reloads = 0;
    const context = vm.createContext({
        fetch: async (url, options) => {
            calls.push({ url, body: JSON.parse(options.body) });
            return { ok: response.ok, json: async () => response.data };
        },
        document: { querySelector: () => ({ content: 'test-csrf' }) },
        window: { location: { reload: () => reloads++ } },
        alert: message => alerts.push(message),
    });
    vm.runInContext(source, context);
    const customer = { id: 7, name: 'Client firmă', fiscal_code: 'TEST7', phone: '0700000000' };
    const desk = context.serviceDesk([], [], [customer]);
    desk.orderId = 42;
    desk.cart = [{ service_item_id: 3, unit_price: 100, quantity: 2 }];
    return { desk, customer, calls, alerts, reloads: () => reloads };
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
