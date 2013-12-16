<?php

use Techno\Domain\Family\Cell;

class Techno_Test_Family_CellTest extends PHPUnit_Framework_TestCase
{
    /**
     * Teste la génération de la hash key
     */
    public function testBuildMembersHashKey()
    {
        $member1 = Techno_Test_Family_MemberTest::generateObject();
        $member2 = Techno_Test_Family_MemberTest::generateObject();

        $hashKey = Cell::buildMembersHashKey([$member1, $member2]);
        $this->assertNotEmpty($hashKey);
        $parts = explode('|', $hashKey);
        $this->assertCount(2, $parts);
        $this->assertContains($member1->getRef(), $parts);
        $this->assertContains($member2->getRef(), $parts);

        Techno_Test_Family_MemberTest::deleteObject($member1);
        Techno_Test_Family_MemberTest::deleteObject($member2);
    }

    /**
     * Teste l'ordre des éléments dans la hashkey
     *
     * Les membres doivent être ordonnés par le meaning de la dimension
     */
    public function testBuildMembersHashKeyOrder()
    {
        $member1 = Techno_Test_Family_MemberTest::generateObject();
        $member2 = Techno_Test_Family_MemberTest::generateObject();

        $hashKey = Cell::buildMembersHashKey([$member1, $member2]);
        $this->assertNotEmpty($hashKey);
        $parts = explode('|', $hashKey);

        $refDimension1 = $member1->getDimension()->getRef();
        $refDimension2 = $member2->getDimension()->getRef();
        if ($refDimension1 < $refDimension2) {
            $this->assertEquals($member1->getRef(), $parts[0]);
        } else {
            $this->assertEquals($member2->getRef(), $parts[0]);
        }

        Techno_Test_Family_MemberTest::deleteObject($member1);
        Techno_Test_Family_MemberTest::deleteObject($member2);
    }
}
