#!/usr/bin/env bash
set -euo pipefail
: "${BASE_URL:=http://127.0.0.1:8080}"
: "${DEMO_API_KEY:?Set DEMO_API_KEY privately before running this demo}"
export BASE_URL DEMO_API_KEY
python3 - <<'PYTHON'
import json, os, urllib.request, urllib.error, uuid
base = os.environ['BASE_URL'].rstrip('/')
key = os.environ['DEMO_API_KEY']
def call(method, route, data=None, idem=None, expected=200):
    headers = {'Content-Type':'application/json', 'X-API-Key':key}
    if idem: headers['Idempotency-Key'] = idem
    req = urllib.request.Request(base+route, data=None if data is None else json.dumps(data).encode(), headers=headers, method=method)
    try:
        with urllib.request.urlopen(req, timeout=20) as r: status, body = r.status, json.load(r)
    except urllib.error.HTTPError as e: status, body = e.code, json.load(e)
    print(method, route, '->', status)
    print(json.dumps(body, indent=2))
    assert status == expected, (status, expected)
    return body
call('GET','/health')
m = call('POST','/api/merchants',{'name':'CV Demo Shop','email':'cv-demo@example.test'},expected=201)
payload = {'merchantId':m['id'],'amount':1299,'currency':'EUR','externalReference':'cv_demo'}
idem = 'demo-'+str(uuid.uuid4())
p = call('POST','/api/payments',payload,idem,201)
replay = call('POST','/api/payments',payload,idem)
assert replay['id'] == p['id']
call('POST','/api/payments',{**payload,'amount':1300},idem,409)
call('GET','/api/payments/'+p['id'])
call('GET','/api/merchants/'+m['id']+'/payments')
print('Demo passed: one payment, successful replay, conflict rejected.')
PYTHON
