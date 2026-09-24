<?php
declare(strict_types=1);
namespace App\Entity;
enum PaymentStatus: string { case PENDING = 'PENDING'; case SUCCEEDED = 'SUCCEEDED'; case FAILED = 'FAILED'; }
