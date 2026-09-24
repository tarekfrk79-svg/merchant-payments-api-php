<?php
declare(strict_types=1);
namespace App\Entity;
use App\Repository\MerchantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
#[ORM\Entity(repositoryClass: MerchantRepository::class)]
#[ORM\Table(name: 'merchants')]
class Merchant
{
    #[ORM\Id, ORM\Column(type: 'guid')]
    private string $id;
    #[ORM\Column(name: 'is_demo', options: ['default' => false])]
    private bool $isDemo = false;
    #[ORM\Column(length: 120)]
    private string $name;
    #[ORM\Column(length: 254)]
    private string $email;
    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;
    public function __construct(string $name, string $email, bool $isDemo = false)
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->isDemo = $isDemo;
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
    public function isDemo(): bool { return $this->isDemo; }
    public function getId(): string { return $this->id; }
    /** @return array{id: string, name: string, email: string, createdAt: string} */
    public function toArray(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'email' => $this->email, 'createdAt' => $this->createdAt->format(DATE_ATOM)];
    }
}
