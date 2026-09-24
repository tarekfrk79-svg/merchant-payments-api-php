<?php
declare(strict_types=1);
namespace App\Controller;
use App\Exception\ApiException;
use App\Http\InputValidator;
use App\Repository\PaymentRepository;
use App\Service\PaymentService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
final class PaymentController
{
    #[Route('/api/payments', methods: ['POST'])]
    public function create(Request $request, InputValidator $validator, PaymentService $service): JsonResponse
    {
        $result = $service->create($validator->payment($request), $request->headers->get('Idempotency-Key', ''));
        $data = $result->payment->toArray();
        return new JsonResponse($data, $result->created ? 201 : 200, ['Location' => '/api/payments/'.$data['id'], 'Idempotency-Replayed' => $result->created ? 'false' : 'true']);
    }
    #[Route('/api/payments/{id}', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function get(string $id, PaymentRepository $payments): JsonResponse
    {
        if (!Uuid::isValid($id)) { throw new ApiException(404, 'Payment not found'); }
        $payment = $payments->find($id) ?? throw new ApiException(404, 'Payment not found');
        return new JsonResponse($payment->toArray());
    }
}
