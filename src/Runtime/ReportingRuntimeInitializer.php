<?php

namespace Symfony\Lsp\Runtime;

use Amp\Cancellation;
use Amp\CancelledException;
use Symfony\Lsp\Client\ClientInterface;
use Symfony\Lsp\Index\ProjectIndexStatusRegistry;
use Symfony\Lsp\Project\Project;
use Symfony\Lsp\Server\ServerLogger;

final class ReportingRuntimeInitializer implements RuntimeInitializerInterface
{
    public function __construct(
        private readonly RuntimeInitializerInterface $initializer,
        private readonly ClientInterface $client,
        private readonly ProjectIndexStatusRegistry $statuses,
        private readonly ServerLogger $logger,
    ) {
    }

    public function initialize(Project $project, ?RuntimeRefreshPlan $plan = null, ?Cancellation $cancellation = null): void
    {
        try {
            $this->initializer->initialize($project, $plan, $cancellation);
        } catch (CancelledException $error) {
            throw $error;
        } catch (\Throwable $error) {
            $this->logger->error($error);
            $stale = 'stale' === $this->statuses->status($project)['runtime']['state'];
            $this->client->notify('window/showMessage', [
                'type' => 1,
                'message' => \sprintf(
                    $stale
                        ? 'Symfony Language Tools could not refresh runtime metadata for "%s". The last valid metadata remains active.'
                        : 'Symfony Language Tools could not initialize runtime metadata for "%s". Static-only features remain active.',
                    $project->rootPath(),
                ),
            ]);
        }
    }
}
