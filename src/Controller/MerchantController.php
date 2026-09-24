<?php
declare(strict_types=1);
namespace App\Controller;
use App\Entity\Merchant;
use App\Exception\ApiException;
use App\Http\InputValidator;
use App\Repository\MerchantRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
final readonly class MerchantController
{
    public function __construct(private MerchantRepository $merchants) {}
    #[Route('/api/merchants', methods: ['POST'])]
    public function create(Request $request, InputValidator $validator, EntityManagerInterface $em): JsonResponse
    {
        $input = $validator->merchant($request);
        $merchant = new Merchant($input->name, $input->email);
        $em->persist($merchant);
        $em->flush();
        return new JsonResponse($merchant->toArray(), 201, ['Location' => '/api/merchants/'.$merchant->getId()]);
    }
    #[Route('/api/merchants/{id}', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function get(string $id): JsonResponse { return new JsonResponse($this->find($id)->toArray()); }
    #[Route('/api/merchants/{id}/payments', methods: ['GET'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function payments(string $id, Request $request, PaymentRepository $payments): JsonResponse
    {
        $merchant = $this->find($id);
        $limit = filter_var($request->query->get('limit', '20'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
        $offset = filter_var($request->query->get('offset', '0'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000000]]);
        if ($limit === false || $offset === false) { throw new ApiException(422, 'Invalid pagination: limit 1–100, offset 0–1000000'); }
        $items = $payments->findBy(['merchant' => $merchant], ['createdAt' => 'DESC', 'id' => 'DESC'], $limit, $offset);
        return new JsonResponse(['data' => array_map(static fn ($p) => $p->toArray(), $items), 'limit' => $limit, 'offset' => $offset]);
    }
    private function find(string $id): Merchant
    {
        if (!\Symfony\Component\Uid\Uuid::isValid($id)) { throw new ApiException(404, 'Merchant not found'); }
        return $this->merchants->find($id) ?? throw new ApiException(404, 'Merchant not found');
    }
}
