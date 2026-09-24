<?php
declare(strict_types=1);
namespace App\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
final class HomeController
{
    #[Route('/health', methods: ['GET'])]
    public function health(): JsonResponse { return new JsonResponse(['status' => 'ok', 'service' => 'MerchantPay API', 'mode' => 'simulation']); }
    #[Route('/', methods: ['GET'])]
    public function home(): Response
    {
        $github = htmlspecialchars($_SERVER['GITHUB_URL'] ?? 'https://github.com/tarekfrk79-svg/merchant-payments-api-php', ENT_QUOTES, 'UTF-8');
        return new Response('<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MerchantPay API</title><style>body{background:#10192a;color:#e9eef7;font:18px/1.7 system-ui;margin:0}main{max-width:850px;margin:9vh auto;padding:32px}small,a{color:#8aebbc}h1{font-size:clamp(40px,7vw,72px);line-height:1.1}section{border:1px solid #37435a;border-radius:16px;padding:24px;margin:28px 0}code{color:#8aebbc}</style><main><small>PHP · SYMFONY · SIMULATED PAYMENTS</small><h1>MerchantPay API</h1><p>Simulate a payment. Retry safely. Keep one record.</p><p><a href="/demo">Try the interactive demo →</a></p><section><strong>Work in progress</strong><p>Available: merchant registration, simulated payments, idempotent retries, payment history and API key protection for writes. Tested with PHPUnit and PHPStan.</p><p>API status: <span id="status">checking…</span></p><code>GET /health<br>POST /api/merchants<br>GET /api/merchants/{id}<br>POST /api/payments<br>GET /api/payments/{id}<br>GET /api/merchants/{id}/payments</code></section><p>Independent educational project. No affiliation with Lemonway. No real payments and no banking data.</p><a href="'.$github.'">GitHub repository</a> · <a href="/health">Check API health</a> · <a href="https://github.com/tarekfrk79-svg/merchant-payments-api-php/blob/main/openapi.yaml">API contract</a></main><script>fetch("/health").then(r=>{if(!r.ok)throw Error();return r.json()}).then(d=>document.getElementById("status").textContent=d.status).catch(()=>document.getElementById("status").textContent="unavailable")</script></html>');
    }
}
