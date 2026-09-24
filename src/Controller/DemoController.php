<?php
declare(strict_types=1);
namespace App\Controller;
use App\Dto\DemoPaymentInput;
use App\Exception\ApiException;
use App\Service\DemoService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
final readonly class DemoController
{
    public function __construct(private DemoService $demo) {}
    #[Route('/demo', methods: ['GET'])]
    public function index(Request $request, Environment $twig): Response
    {
        return new Response($twig->render('demo/index.html.twig', ['token' => $this->demo->token($request->getSession())]), 200, ['Cache-Control' => 'private, no-store', 'X-Frame-Options' => 'DENY', 'Referrer-Policy' => 'no-referrer']);
    }
    #[Route('/demo/app.js', methods: ['GET'])]
    public function javascript(): Response
    {
        $source = file_get_contents(dirname(__DIR__, 2).'/assets/demo.js');
        if ($source === false) { throw new \RuntimeException('Demo asset unavailable'); }
        return new Response($source, 200, ['Content-Type' => 'text/javascript; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
    #[Route('/demo/payments', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->demo->authorize($request->getSession(), $request->headers->get('X-Demo-Token', ''));
        if ($request->getContentTypeFormat() !== 'json') { throw new ApiException(415, 'Un contenu JSON est requis.'); }
        if (strlen($request->getContent()) > 2048) { throw new ApiException(413, 'Requête trop volumineuse.'); }
        try { $data = json_decode($request->getContent(), false, 16, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new ApiException(400, 'JSON invalide.'); }
        if (!$data instanceof \stdClass) { throw new ApiException(400, 'Un objet JSON est requis.'); }
        $result = $this->demo->create($request->getSession(), DemoPaymentInput::fromArray(get_object_vars($data)));
        return new JsonResponse(['payment' => $result->payment->toArray(), 'replayed' => !$result->created, 'history' => $this->demo->history($request->getSession()), 'limit' => DemoService::MAX_PAYMENTS], $result->created ? 201 : 200, ['Cache-Control' => 'private, no-store']);
    }
    #[Route('/demo/history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        $this->demo->authorize($request->getSession(), $request->headers->get('X-Demo-Token', ''));
        return new JsonResponse(['history' => $this->demo->history($request->getSession()), 'limit' => DemoService::MAX_PAYMENTS], 200, ['Cache-Control' => 'private, no-store']);
    }
}
