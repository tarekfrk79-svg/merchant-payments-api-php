#!/usr/bin/env python3
"""Live smoke check. Creates fictional records; never prints credentials."""
import concurrent.futures
import json
import os
import urllib.error
import urllib.request
import uuid

base = os.environ['BASE_URL'].rstrip('/')
api_key = os.environ['DEMO_API_KEY']

def call(method, path, data=None, key=None, auth=True, raw=None):
    headers = {'Content-Type': 'application/json'}
    if auth:
        headers['X-API-Key'] = api_key
    if key:
        headers['Idempotency-Key'] = key
    body = raw if raw is not None else (None if data is None else json.dumps(data).encode())
    request = urllib.request.Request(base + path, data=body, headers=headers, method=method)
    try:
        with urllib.request.urlopen(request, timeout=25) as response:
            return response.status, json.load(response)
    except urllib.error.HTTPError as error:
        return error.code, json.load(error)

with urllib.request.urlopen(base + '/', timeout=25) as response:
    assert response.status == 200
    html = response.read().decode()
    assert 'MerchantPay API' in html and 'github.com/tarekfrk79-svg/merchant-payments-api-php' in html
assert call('GET', '/health')[1]['status'] == 'ok'
assert call('POST', '/api/merchants', {}, auth=False)[0] == 401
assert call('POST', '/api/merchants', raw=b'{broken')[0] == 400
code, merchant = call('POST', '/api/merchants', {'name': 'Smoke Demo Shop', 'email': 'smoke@example.test'})
assert code == 201
assert call('GET', '/api/merchants/' + merchant['id'])[1] == merchant
payload = {'merchantId': merchant['id'], 'amount': 1299, 'currency': 'EUR', 'externalReference': 'smoke_demo'}
assert call('POST', '/api/payments', payload)[0] == 400
assert call('POST', '/api/payments', {**payload, 'amount': -1}, 'negative')[0] == 422
assert call('POST', '/api/payments', {**payload, 'merchantId': str(uuid.uuid4())}, 'missing')[0] == 404
key = 'smoke-' + str(uuid.uuid4())
code, payment = call('POST', '/api/payments', payload, key)
assert code == 201 and payment['status'] == 'SUCCEEDED'
assert call('POST', '/api/payments', payload, key) == (200, payment)
assert call('POST', '/api/payments', {**payload, 'amount': 1300}, key)[0] == 409
assert call('GET', '/api/payments/' + payment['id']) == (200, payment)
assert len(call('GET', '/api/merchants/' + merchant['id'] + '/payments')[1]['data']) == 1
race_key = 'race-' + str(uuid.uuid4())
with concurrent.futures.ThreadPoolExecutor(max_workers=8) as pool:
    results = list(pool.map(lambda _: call('POST', '/api/payments', payload, race_key), range(8)))
assert sorted(status for status, _ in results) == [200] * 7 + [201], results
assert len({body['id'] for _, body in results}) == 1
assert len(call('GET', '/api/merchants/' + merchant['id'] + '/payments')[1]['data']) == 2
code, failed = call('POST', '/api/payments', {**payload, 'externalReference': 'fail_smoke'}, 'failed')
assert code == 201 and failed['status'] == 'FAILED'
assert call('POST', '/api/payments', {**payload, 'externalReference': 'fail_smoke'}, 'failed') == (200, failed)
print('PASS: landing, health, merchant create/read, API key, malformed JSON, validation, missing merchant, required idempotency key, payment create/read/list, replay, conflict, failed simulation.')
print('PASS: 8 concurrent identical requests -> one 201, seven 200, one payment UUID.')
