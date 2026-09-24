<?php
declare(strict_types=1);
namespace App\Http;
use App\Dto\CreateMerchant;
use App\Dto\CreatePayment;
use App\Exception\ApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
final readonly class InputValidator
{
    public function __construct(private ValidatorInterface $validator) {}
    public function merchant(Request $request): CreateMerchant
    {
        $data = $this->decode($request, ['name' => 'string', 'email' => 'string']);
        $dto = new CreateMerchant($this->string($data['name']), $this->string($data['email']));
        $this->validate($dto);
        return $dto;
    }
    public function payment(Request $request): CreatePayment
    {
        $data = $this->decode($request, ['merchantId' => 'string', 'amount' => 'integer', 'currency' => 'string', 'externalReference' => 'string']);
        $amount = $data['amount'];
        if (!is_int($amount)) { throw new ApiException(422, 'Amount must be an integer in cents'); }
        $dto = new CreatePayment($this->string($data['merchantId']), $amount, $this->string($data['currency']), $this->string($data['externalReference']));
        $this->validate($dto);
        return $dto;
    }
    private function string(mixed $value): string
    {
        if (!is_string($value)) { throw new ApiException(422, 'Expected a string'); }
        return $value;
    }
    /** @param array<string, string> $fields
     *  @return array<string, mixed>
     */
    private function decode(Request $request, array $fields): array
    {
        if ($request->getContentTypeFormat() !== 'json') { throw new ApiException(415, 'Content-Type must be application/json'); }
        if (strlen($request->getContent()) > 16384) { throw new ApiException(413, 'Request body too large'); }
        try { $object = json_decode($request->getContent(), false, 32, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new ApiException(400, 'Invalid JSON'); }
        if (!$object instanceof \stdClass) { throw new ApiException(400, 'JSON body must be an object'); }
        $data = get_object_vars($object);
        $errors = [];
        foreach ($fields as $field => $type) {
            if (!array_key_exists($field, $data) || gettype($data[$field]) !== $type) { $errors[$field] = ['Required type: '.$type]; }
        }
        foreach (array_diff(array_keys($data), array_keys($fields)) as $field) { $errors[$field] = ['Unknown field']; }
        if ($errors !== []) { throw new ApiException(422, 'Validation failed', $errors); }
        return $data;
    }
    private function validate(object $dto): void
    {
        $errors = [];
        foreach ($this->validator->validate($dto) as $violation) { $errors[$violation->getPropertyPath()][] = (string) $violation->getMessage(); }
        if ($errors !== []) { throw new ApiException(422, 'Validation failed', $errors); }
    }
}
