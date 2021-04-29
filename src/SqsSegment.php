<?php

declare(strict_types=1);

namespace Pkerrigan\Xray;

class SqsSegment extends RemoteSegment
{
    protected ?string $queueUrl = null;

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        $data['aws'] = $this->serialiseAwsData();
        $data['namespace'] = 'aws';

        return array_filter($data);
    }

    public function setQueueUrl(string $queueUrl): self
    {
        $this->queueUrl = $queueUrl;

        return $this;
    }

    protected function serialiseAwsData(): array
    {
        return array_merge(parent::serialiseAwsData(), [
            'operation' => 'SendMessage',
            'queue_url' => $this->queueUrl,
        ]);
    }
}