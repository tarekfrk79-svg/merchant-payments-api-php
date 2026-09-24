'use strict';
(() => {
    const $ = (id) => document.getElementById(id);
    const token = document.querySelector('meta[name="demo-token"]').content;
    let lastRequest = null;
    let lastId = null;
    let busy = false;
    const money = (cents) => `${Math.floor(cents / 100)},${String(cents % 100).padStart(2, '0')} €`;
    const date = (value) => new Date(value).toLocaleString('fr-FR');
    const statusLabel = (value) => value === 'SUCCEEDED' ? 'Accepté · SUCCEEDED' : 'Refusé · FAILED';
    function error(message) { $('error').textContent = message; $('error').hidden = !message; }
    function history(items) {
        $('count').textContent = `${items.length} / 10 paiements`;
        $('history-empty').hidden = items.length > 0;
        $('history-table').hidden = items.length === 0;
        $('history-body').replaceChildren();
        for (const payment of items) {
            const row = document.createElement('tr');
            for (const value of [statusLabel(payment.status), money(payment.amount), payment.externalReference, date(payment.createdAt)]) {
                const cell = document.createElement('td'); cell.textContent = value; row.appendChild(cell);
            }
            $('history-body').appendChild(row);
        }
    }
    async function request(path, payload) {
        const options = {credentials: 'same-origin', headers: {'X-Demo-Token': token}};
        if (payload) { options.method = 'POST'; options.headers['Content-Type'] = 'application/json'; options.body = JSON.stringify(payload); }
        const response = await fetch(path, options);
        const data = await response.json();
        if (!response.ok) { throw new Error(data.error?.message || 'La requête a échoué. Réessayez.'); }
        return {data, status: response.status};
    }
    async function simulate(payload) {
        if (busy) return;
        busy = true; $('simulate').disabled = true; $('retry').disabled = true; error('');
        $('simulate').textContent = 'Simulation en cours…';
        try {
            const {data, status} = await request('/demo/payments', payload);
            const payment = data.payment;
            if (data.replayed && lastId && lastId !== payment.id) throw new Error('Le paiement renvoyé ne correspond pas au précédent.');
            lastId = payment.id;
            $('empty').hidden = true; $('result').hidden = false;
            $('status').textContent = statusLabel(payment.status);
            $('status').classList.toggle('failed', payment.status === 'FAILED');
            $('result-amount').textContent = money(payment.amount);
            $('result-reference').textContent = payment.externalReference;
            $('result-id').textContent = payment.id;
            $('result-date').textContent = date(payment.createdAt);
            $('result-http').textContent = `${status} ${data.replayed ? '— paiement existant' : '— paiement créé'}`;
            $('explanation').textContent = data.replayed
                ? 'La même clé d’idempotence a été réutilisée. L’API a renvoyé le paiement existant au lieu d’en créer un second. Même UUID, aucun doublon dans l’historique.'
                : 'Un paiement simulé a été enregistré. Réessayez la même requête : son UUID et le nombre de paiements resteront identiques.';
            history(data.history);
        } catch (e) { error(e.message || 'Connexion interrompue. Réessayez le même paiement.'); }
        finally { busy = false; $('simulate').disabled = false; $('retry').disabled = !lastRequest; $('simulate').textContent = 'Simuler le paiement'; }
    }
    $('payment-form').addEventListener('submit', (event) => {
        event.preventDefault();
        if (busy) return;
        lastRequest = Object.freeze({amount: $('amount').value.trim(), reference: $('reference').value.trim(), outcome: document.querySelector('input[name="outcome"]:checked').value, key: crypto.randomUUID()});
        lastId = null;
        simulate(lastRequest);
    });
    $('retry').addEventListener('click', () => { if (lastRequest) simulate(lastRequest); });
    request('/demo/history').then(({data}) => history(data.history)).catch((e) => error(e.message));
})();
