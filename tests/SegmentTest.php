<?php

declare(strict_types=1);

namespace Pkerrigan\Xray;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pkerrigan\Xray\Submission\SegmentSubmitter;

/**
 *
 * @author Patrick Kerrigan (patrickkerrigan.uk)
 * @since 17/05/2018
 */
class SegmentTest extends TestCase
{
    public function testSegmentWithoutErrorsSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin()
            ->end();

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('Test segment', $serialised['name']);
        $this->assertNotNull($serialised['start_time']);
        $this->assertNotNull($serialised['end_time']);
        $this->assertArrayNotHasKey('in_progress', $serialised);
        $this->assertArrayNotHasKey('fault', $serialised);
        $this->assertArrayNotHasKey('error', $serialised);
        $this->assertArrayNotHasKey('throttle', $serialised);
        $this->assertArrayNotHasKey('subsegments', $serialised);
    }

    public function testSegmentWithErrorSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin()
            ->end()
            ->setError(true);

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('Test segment', $serialised['name']);
        $this->assertTrue($serialised['error']);
        $this->assertNotNull($serialised['start_time']);
        $this->assertNotNull($serialised['end_time']);
        $this->assertArrayNotHasKey('in_progress', $serialised);
        $this->assertArrayNotHasKey('fault', $serialised);
        $this->assertArrayNotHasKey('throttle', $serialised);
        $this->assertArrayNotHasKey('subsegments', $serialised);
    }

    public function testSegmentWithFaultSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin()
            ->end()
            ->setFault(true);

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('Test segment', $serialised['name']);
        $this->assertTrue($serialised['fault']);
        $this->assertNotNull($serialised['start_time']);
        $this->assertNotNull($serialised['end_time']);
        $this->assertArrayNotHasKey('in_progress', $serialised);
        $this->assertArrayNotHasKey('error', $serialised);
        $this->assertArrayNotHasKey('throttle', $serialised);
        $this->assertArrayNotHasKey('subsegments', $serialised);
    }

    public function testSegmentWithThrottleSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin()
            ->end()
            ->setThrottle(true);

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('Test segment', $serialised['name']);
        $this->assertTrue($serialised['throttle']);
        $this->assertNotNull($serialised['start_time']);
        $this->assertNotNull($serialised['end_time']);
        $this->assertArrayNotHasKey('in_progress', $serialised);
        $this->assertArrayNotHasKey('error', $serialised);
        $this->assertArrayNotHasKey('fault', $serialised);
        $this->assertArrayNotHasKey('subsegments', $serialised);
    }

    public function testOpenSegmentSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin();

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('Test segment', $serialised['name']);
        $this->assertNotNull($serialised['start_time']);
        $this->assertTrue($serialised['in_progress']);
        $this->assertArrayNotHasKey('end_time', $serialised);
        $this->assertArrayNotHasKey('fault', $serialised);
        $this->assertArrayNotHasKey('error', $serialised);
        $this->assertArrayNotHasKey('throttle', $serialised);
        $this->assertArrayNotHasKey('subsegments', $serialised);
    }

    public function testSegmentWithSubsegmentSerialisesCorrectly(): void
    {
        $segment = new Segment();
        $subsegment = new Segment();

        $subsegment->setName('Test subsegment')
            ->begin()
            ->end();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin()
            ->addSubsegment($subsegment)
            ->end();

        $serialised = $segment->jsonSerialize();

        $this->assertEquals($segment->getId(), $serialised['id']);
        $this->assertEquals('Test segment', $serialised['name']);
        $this->assertNotNull($serialised['start_time']);
        $this->assertNotNull($serialised['end_time']);
        $this->assertArrayNotHasKey('in_progress', $serialised);
        $this->assertArrayHasKey('subsegments', $serialised);

        $this->assertEquals($subsegment, $serialised['subsegments'][0]);
    }

    public function testIndependentSubsegmentSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $segment->setName('Test segment')
                ->setParentId('123')
                ->setTraceId('456')
                ->setIndependent(true)
                ->begin()
                ->end();

        $serialised = $segment->jsonSerialize();

        $this->assertEquals('123', $serialised['parent_id']);
        $this->assertEquals('456', $serialised['trace_id']);
        $this->assertEquals('subsegment', $serialised['type']);
    }

    public function testGivenAnnotationsSerialisesCorrectly(): void
    {
        $segment = new Segment();
        $segment->addAnnotation('key1', 'value1')
            ->addAnnotation('key2', 'value2');

        $serialised = $segment->jsonSerialize();

        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => 'value2'
            ],
            $serialised['annotations']
        );
    }

    public function testGivenMetadataSerialisesCorrectly(): void
    {
        $segment = new Segment();
        $segment->addMetadata('key1', 'value1')
            ->addMetadata('key2', ['value2', 'value3']);

        $serialised = $segment->jsonSerialize();

        $this->assertEquals(
            [
                'key1' => 'value1',
                'key2' => ['value2', 'value3']
            ],
            $serialised['metadata']
        );
    }

    public function testNotGivenExceptionSerialisesCorrectly(): void
    {
        $segment = new Segment();

        $serialised = $segment->jsonSerialize();

        $this->assertArrayNotHasKey('cause', $serialised);
    }

    public function testGivenExceptionSerialisesCorrectly(): void
    {
        $previousException = new \Exception('test');
        $exception1 = new \Exception('test1', 1, $previousException);
        $exception2 = new \Exception('test2', 2);
        $segment = new Segment();
        $segment->addException($exception1);
        $segment->addException($exception2);

        $serialised = $segment->jsonSerialize();

        $this->assertArrayHasKey('working_directory', $serialised['cause']);
        $this->assertArrayHasKey('exceptions', $serialised['cause']);
        $this->assertCount(3, $serialised['cause']['exceptions']);

        $this->assertEquals(bin2hex(spl_object_hash($exception1)), $serialised['cause']['exceptions'][0]['id']);
        $this->assertEquals('test1', $serialised['cause']['exceptions'][0]['message']);
        $this->assertEquals('Exception', $serialised['cause']['exceptions'][0]['type']);
        $this->assertEquals(bin2hex(spl_object_hash($previousException)), $serialised['cause']['exceptions'][0]['cause']);
        $this->assertArrayHasKey('stack', $serialised['cause']['exceptions'][0]);

        $this->assertEquals(bin2hex(spl_object_hash($previousException)), $serialised['cause']['exceptions'][1]['id']);
        $this->assertEquals('test', $serialised['cause']['exceptions'][1]['message']);
        $this->assertEquals('Exception', $serialised['cause']['exceptions'][1]['type']);
        $this->assertArrayNotHasKey('cause', $serialised['cause']['exceptions'][1]);
        $this->assertArrayHasKey('stack', $serialised['cause']['exceptions'][1]);

        $this->assertEquals(bin2hex(spl_object_hash($exception2)), $serialised['cause']['exceptions'][2]['id']);
        $this->assertEquals('test2', $serialised['cause']['exceptions'][2]['message']);
        $this->assertEquals('Exception', $serialised['cause']['exceptions'][2]['type']);
        $this->assertArrayNotHasKey('cause', $serialised['cause']['exceptions'][2]);
        $this->assertArrayHasKey('stack', $serialised['cause']['exceptions'][2]);
    }

    public function testAddingSubsegmentToClosedSegmentFails(): void
    {
        $segment = new Segment();
        $subsegment = new Segment();

        $subsegment->setName('Test subsegment')
            ->begin()
            ->end();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->begin()
            ->end()
            ->addSubsegment($subsegment);

        $serialised = $segment->jsonSerialize();

        $this->assertArrayNotHasKey('subsegments', $serialised);
    }

    public function testAddingSubsegmentSetsSampled(): void
    {
        $segment = new Segment();
        $subsegment = new Segment();

        $subsegment->setName('Test subsegment')
            ->begin()
            ->end();

        $segment->setName('Test segment')
            ->setParentId('123')
            ->setSampled(true)
            ->begin()
            ->addSubsegment($subsegment)
            ->end();

        $this->assertTrue($subsegment->isSampled());
    }

    public function testIsNotOpenIfEndTimeSet(): void
    {
        $segment = new Segment();
        $segment->begin()
            ->end();

        $this->assertFalse($segment->isOpen());
    }

    public function testIsOpenIfEndTimeNotSet(): void
    {
        $segment = new Segment();
        $segment->begin();

        $this->assertTrue($segment->isOpen());
    }

    public function testSubmitsIfSampled(): void
    {
        /** @var SegmentSubmitter|MockObject $submitter */
        $submitter = $this->createMock(SegmentSubmitter::class);

        $segment = new Segment();

        $submitter->expects($this->once())
            ->method('submitSegment')
            ->with($segment);

        $segment->setSampled(true)
            ->submit($submitter);

    }

    public function testDoesNotSubmitIfNotSampled(): void
    {
        /** @var SegmentSubmitter|MockObject $submitter */
        $submitter = $this->createMock(SegmentSubmitter::class);

        $segment = new Segment();

        $submitter->expects($this->never())
            ->method('submitSegment');

        $segment->setSampled(false)
            ->submit($submitter);

    }

    public function testGivenNoSubsegmentsCurrentSegmentReturnsSegment(): void
    {
        $segment = new Segment();
        $segment->begin();

        $this->assertEquals($segment, $segment->getCurrentSegment());
    }

    public function testClosedSubsegmentCurrentSegmentReturnsSegment(): void
    {
        $subsegment = new Segment();
        $subsegment->begin()
            ->end();
        $segment = new Segment();
        $segment->begin()
            ->addSubsegment($subsegment);

        $this->assertEquals($segment, $segment->getCurrentSegment());
    }

    public function testOpenSubsegmentCurrentSegmentReturnsSubsegment(): void
    {
        $subsegment = new Segment();
        $subsegment->begin();
        $segment = new Segment();
        $segment->begin()
            ->addSubsegment($subsegment);

        $this->assertEquals($subsegment, $segment->getCurrentSegment());
    }

    public function testSubsequentCallsCurrentSegmentReturnsSubsegment(): void
    {
        $subsegment = new Segment();
        $subsegment->begin();
        $segment = new Segment();
        $segment->begin()
                ->addSubsegment($subsegment);

        $this->assertEquals($subsegment, $segment->getCurrentSegment());
        $this->assertEquals($subsegment, $segment->getCurrentSegment());
    }

    public function testChangingCurrentSegmentReturnsCorrectStatus(): void
    {
        $subsegment1 = new Segment();
        $subsegment1->begin();
        $subsegment2 = new Segment();
        $subsegment2->begin();
        $subsegment3 = new Segment();
        $subsegment3->begin();

        $segment = new Segment();
        $segment->begin()
                ->addSubsegment($subsegment1)
                ->addSubsegment($subsegment2)
                ->addSubsegment($subsegment3);

        $this->assertEquals($subsegment1, $segment->getCurrentSegment());

        $subsegment1->end();

        $this->assertEquals($subsegment2, $segment->getCurrentSegment());

        $subsegment2->end();

        $this->assertEquals($subsegment3, $segment->getCurrentSegment());
    }

    public function testAwsDataMissing(): void
    {
        $segment = new Segment();

        $serialised = $segment->jsonSerialize();

        $this->assertNotContains('aws', $serialised);
    }

    /**
     * @dataProvider provideGetTraceIdCases
     */
    public function testGetTraceId(?string $traceId, ?string $expectedTraceId): void
    {
        $segment = new Segment();
        $segment->setTraceId($traceId);

        $this->assertEquals($expectedTraceId, $segment->getTraceId());
    }

    public function provideGetTraceIdCases(): iterable
    {
        yield [null, null];
        yield ['123', '123'];
    }

    /**
     * @dataProvider provideGetParentIdCases
     */
    public function testGetParentId(?string $parentId, ?string $expectedParentId): void
    {
        $segment = new Segment();
        $segment->setParentId('123');

        $this->assertEquals('123', $segment->getParentId());
    }

    public function provideGetParentIdCases(): iterable
    {
        yield [null, null];
        yield ['123', '123'];
    }
}
