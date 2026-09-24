<?php
declare(strict_types=1);
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;
final readonly class CreateMerchant
{
    public function __construct(
        #[Assert\NotBlank, Assert\Length(max: 120)] public string $name,
        #[Assert\NotBlank, Assert\Email, Assert\Length(max: 254)] public string $email,
    ) {}
}
