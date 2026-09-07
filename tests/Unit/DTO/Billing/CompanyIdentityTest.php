<?php

namespace Tests\Unit\DTO\Billing;

use App\DTO\Billing\CompanyIdentity;
use PHPUnit\Framework\TestCase;

class CompanyIdentityTest extends TestCase
{
    public function test_it_identifies_an_association_from_the_official_flag(): void
    {
        $identity = new CompanyIdentity(
            provider: 'recherche-entreprises.api.gouv.fr',
            country: 'FR',
            legalName: 'Association Exemple',
            association: true,
            rnaNumber: 'W123456789',
        );

        $this->assertTrue($identity->isAssociation());
        $this->assertSame('W123456789', $identity->rnaNumber);
    }

    public function test_it_identifies_an_association_from_its_legal_category(): void
    {
        $identity = new CompanyIdentity(
            provider: 'recherche-entreprises.api.gouv.fr',
            country: 'FR',
            legalName: 'Association Exemple',
            legalCategory: '9220',
        );

        $this->assertTrue($identity->isAssociation());
    }
}
