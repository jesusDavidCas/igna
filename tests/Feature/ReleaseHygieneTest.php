<?php

namespace Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReleaseHygieneTest extends TestCase
{
    public function test_release_hygiene_check_passes_for_the_tracked_repository(): void
    {
        $process = new Process(['bash', 'scripts/verify-release-hygiene.sh'], base_path());
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
        $this->assertStringContainsString('Release hygiene passed', $process->getOutput());
    }

    public function test_release_hygiene_reports_synthetic_sensitive_paths_without_echoing_values(): void
    {
        $repository = sys_get_temp_dir().'/igna-release-hygiene-'.bin2hex(random_bytes(8));
        mkdir($repository, 0700, true);

        try {
            $this->runGit($repository, ['init']);
            file_put_contents($repository.'/README.md', 'safe fixture');
            $this->commit($repository, 'Add safe fixture', ['README.md']);

            $safe = $this->runHygiene($repository);
            $this->assertSame(0, $safe->getExitCode(), $safe->getErrorOutput());

            $syntheticEnvironmentValue = 'synthetic-environment-value';
            file_put_contents($repository.'/.env.backup-local', "APP_KEY={$syntheticEnvironmentValue}\n");
            $this->commit($repository, 'Add environment fixture', ['.env.backup-local']);

            $environmentFailure = $this->runHygiene($repository);
            $this->assertSame(1, $environmentFailure->getExitCode());
            $this->assertStringContainsString('tracked environment material', $environmentFailure->getErrorOutput());
            $this->assertStringNotContainsString($syntheticEnvironmentValue, $environmentFailure->getErrorOutput());

            $this->runGit($repository, ['rm', '.env.backup-local']);
            $this->commit($repository, 'Remove environment fixture');

            $syntheticToken = 'ghp_'.str_repeat('a', 24);
            file_put_contents($repository.'/fixture.txt', $syntheticToken);
            $this->commit($repository, 'Add credential fixture', ['fixture.txt']);

            $credentialFailure = $this->runHygiene($repository);
            $this->assertSame(1, $credentialFailure->getExitCode());
            $this->assertStringContainsString('fixture.txt', $credentialFailure->getErrorOutput());
            $this->assertStringNotContainsString($syntheticToken, $credentialFailure->getErrorOutput());
        } finally {
            app(Filesystem::class)->deleteDirectory($repository);
        }
    }

    private function runHygiene(string $repository): Process
    {
        $process = new Process(['bash', base_path('scripts/verify-release-hygiene.sh')], $repository);
        $process->run();

        return $process;
    }

    private function commit(string $repository, string $message, array $paths = []): void
    {
        if ($paths !== []) {
            $this->runGit($repository, ['add', '--', ...$paths]);
        }

        $this->runGit($repository, [
            '-c', 'user.name=IGNA Test',
            '-c', 'user.email=igna-test@example.invalid',
            'commit', '-m', $message,
        ]);
    }

    private function runGit(string $repository, array $arguments): void
    {
        $process = new Process(['git', ...$arguments], $repository);
        $process->run();

        $this->assertSame(0, $process->getExitCode(), $process->getErrorOutput());
    }
}
