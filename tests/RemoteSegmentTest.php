<?php

declare(strict_types=1);

namespace Pkerrigan\Xray;

use PHPUnit\Framework\TestCase;

/**
 *
 * @author Patrick Kerrigan (patrickkerrigan.uk)
 * @since 17/05/2018
 */
class RemoteSegmentTest extends TestCase
{
    public function testUntracedSegmentSerialisesCorrectly(): void
    {
        $segment = new RemoteSegment();
        $segment->setAwsAccountId(12345);

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('remote', $serialised['namespace']);
        $this->assertEquals(12345, $serialised['aws']['account_id']);
        $this->assertArrayNotHasKey('traced', $serialised);
    }
}
