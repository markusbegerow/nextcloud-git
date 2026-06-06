<?php

declare(strict_types=1);

namespace OCA\Git\Service;

use OCA\Git\BackgroundJob\WebhookDispatchJob;
use OCA\Git\Db\Webhook;
use OCA\Git\Db\WebhookMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use RuntimeException;

class WebhookService {
    public function __construct(
        private WebhookMapper $webhookMapper,
        private RepoService   $repoService,
        private IJobList      $jobList,
    ) {}

    public function listWebhooks(string $owner, string $repoName): array {
        $repo  = $this->repoService->getRepo($owner, $repoName);
        $hooks = $this->webhookMapper->findAllByRepo((int) $repo['id']);
        return array_map(fn(Webhook $h) => $h->toArray(), $hooks);
    }

    public function createWebhook(string $owner, string $repoName, string $url, string $secret, array $events): array {
        $repo = $this->repoService->getRepo($owner, $repoName);

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Invalid webhook URL.');
        }

        $hook = new Webhook();
        $hook->setRepoId((int) $repo['id']);
        $hook->setUrl($url);
        $hook->setSecret($secret);
        $hook->setEvents(json_encode(array_values(array_filter($events))));
        $hook->setActive(1);
        $hook->setCreatedAt(time());

        return $this->webhookMapper->insert($hook)->toArray();
    }

    public function deleteWebhook(string $owner, string $repoName, int $webhookId): void {
        $repo = $this->repoService->getRepo($owner, $repoName);
        try {
            $hook = $this->webhookMapper->findById($webhookId);
        } catch (DoesNotExistException) {
            throw new RuntimeException('Webhook not found.');
        }
        if ($hook->getRepoId() !== (int) $repo['id']) {
            throw new RuntimeException('Webhook not found.');
        }
        $this->webhookMapper->delete($hook);
    }

    public function trigger(int $repoId, string $event, array $payload): void {
        $hooks = $this->webhookMapper->findAllByRepo($repoId);
        foreach ($hooks as $hook) {
            $events = json_decode($hook->getEvents(), true) ?? [];
            if (!$hook->getActive() || (!in_array($event, $events) && !in_array('*', $events))) {
                continue;
            }
            $payloadJson = json_encode($payload);
            $signature   = hash_hmac('sha256', $payloadJson, $hook->getSecret());
            $this->jobList->add(WebhookDispatchJob::class, [
                'url'       => $hook->getUrl(),
                'payload'   => $payloadJson,
                'signature' => $signature,
            ]);
        }
    }
}
