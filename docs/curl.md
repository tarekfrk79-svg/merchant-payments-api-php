# curl examples

Set `BASE_URL` to the local or Railway address and `DEMO_API_KEY` privately. Use fictional data only.

```bash
curl "$BASE_URL/health"
curl -i "$BASE_URL/api/merchants" \
  -H "X-API-Key: $DEMO_API_KEY" -H 'Content-Type: application/json' \
  -d '{"name":"Demo Shop","email":"demo@example.test"}'

# Copy the returned merchant UUID:
export MERCHANT_ID=replace-with-returned-uuid
curl "$BASE_URL/api/merchants/$MERCHANT_ID"
curl -i "$BASE_URL/api/payments" \
  -H "X-API-Key: $DEMO_API_KEY" -H 'Content-Type: application/json' \
  -H 'Idempotency-Key: demo-order-001' \
  -d "{\"merchantId\":\"$MERCHANT_ID\",\"amount\":1299,\"currency\":\"EUR\",\"externalReference\":\"order_001\"}"
# Repeat unchanged -> 200, same UUID. Change amount with same key -> 409.
export PAYMENT_ID=replace-with-returned-uuid
curl "$BASE_URL/api/payments/$PAYMENT_ID"
curl "$BASE_URL/api/merchants/$MERCHANT_ID/payments?limit=20&offset=0"
```

Use `externalReference: fail_demo` with a fresh key to simulate a failed payment. Missing API key -> 401; malformed JSON -> 400; zero/negative/fractional amount -> 422; unknown merchant -> 404.
