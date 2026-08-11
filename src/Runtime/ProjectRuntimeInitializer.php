<?php

namespace Symfony\Lsp\Runtime;

use Amp\Cancellation;
use Symfony\Lsp\Project\Project;

final class ProjectRuntimeInitializer implements RuntimeInitializerInterface
{
    public function __construct(
        private readonly BridgeInstaller $bridgeInstaller,
        private readonly ProcessRunnerInterface $processRunner,
        private readonly RuntimeSnapshotLoaderRegistry $snapshotLoaders,
        private readonly RuntimeConfiguration $configuration,
    ) {
    }

    public function initialize(Project $project, ?RuntimeRefreshPlan $plan = null, ?Cancellation $cancellation = null): void
    {
        if (!$this->configuration->debug($project)) {
            throw new \RuntimeException('Runtime indexing requires Symfony debug mode.');
        }
        $plan ??= new RuntimeRefreshPlan();
        $mode = $plan->mode();
        $cancellation?->throwIfRequested();
        $sections = $plan->sections() ?? $this->snapshotLoaders->sections();
        $bridge = $this->bridgeInstaller->install($project);
        $result = $this->processRunner->run([
            ...$this->configuration->phpCommand($project),
            $bridge,
            '--project='.$project->rootPath(),
            '--environment='.$this->configuration->environment($project),
            '--debug=1',
            '--sections='.implode(',', $sections),
            ...($plan->preservesContainer() ? ['--targeted-refresh=1'] : []),
            ...(RuntimeRefreshMode::Clear === $mode ? ['--rebuild-container=1'] : []),
        ], $project->rootPath(), $cancellation);

        if (0 !== $result->exitCode()) {
            throw new \RuntimeException(\sprintf('The project bridge failed with status %d.', $result->exitCode()));
        }

        try {
            $snapshot = json_decode($result->stdout(), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new \RuntimeException('The project bridge returned invalid JSON.', 0, $error);
        }

        if (!\is_array($snapshot) || 1 !== ($snapshot['schemaVersion'] ?? null)) {
            throw new \RuntimeException('The project bridge returned an unsupported snapshot.');
        }

        $errors = $snapshot['errors'] ?? null;
        $loadableSnapshot = $snapshot;
        $failedSections = [];
        foreach (\is_array($errors) ? $errors : [] as $error) {
            if (!\is_array($error)) {
                continue;
            }
            $section = $error['section'] ?? null;
            if (!\is_string($section)) {
                continue;
            }
            if (\is_array($loadableSnapshot['sections'] ?? null)) {
                unset($loadableSnapshot['sections'][$section]);
            }
            if ('runtime' === $section || \in_array($section, $sections, true)) {
                $failedSections[$section] = true;
            }
        }
        $this->snapshotLoaders->load($project, $loadableSnapshot);

        if (\is_array($errors) && [] !== $errors) {
            $detail = [] === $failedSections ? '' : ': '.implode(', ', array_keys($failedSections));

            throw new \RuntimeException('The project bridge could not load runtime metadata'.$detail.'.');
        }
    }
}
