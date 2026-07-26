<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DeploymentScriptSafetyTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir().'/igna-deploy-test-'.bin2hex(random_bytes(6));
        File::makeDirectory($this->fixtureRoot, 0700, true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureRoot);

        parent::tearDown();
    }

    public function test_deployment_script_syntax_passes(): void
    {
        $process = new Process(['bash', '-n', base_path('scripts/deploy-hostinger.sh')]);
        $process->run();

        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }

    public function test_preflight_rejects_missing_expected_commit_and_backup_confirmation(): void
    {
        $missingCommit = $this->process([
            '--preflight',
            '--backup-dir', $this->fixtureRoot.'/backup',
            '--backup-confirmed',
        ]);
        $missingCommit->run();

        $this->assertFalse($missingCommit->isSuccessful());
        $this->assertStringContainsString('expected release commit is required', $missingCommit->getErrorOutput());

        $missingConfirmation = $this->process([
            '--preflight',
            '--expected-commit', str_repeat('a', 40),
            '--backup-dir', $this->fixtureRoot.'/backup',
        ]);
        $missingConfirmation->run();

        $this->assertFalse($missingConfirmation->isSuccessful());
        $this->assertStringContainsString('explicit backup confirmation is required', $missingConfirmation->getErrorOutput());
    }

    public function test_preflight_rejects_wrong_root_branch_dirty_tree_and_parent_public_html(): void
    {
        $fixture = $this->fixture();

        $wrongRoot = $this->process($this->arguments($fixture, [
            '--app-root', $this->fixtureRoot.'/wrong-root',
        ]));
        $wrongRoot->run();
        $this->assertFalse($wrongRoot->isSuccessful());
        $this->assertStringContainsString('Laravel root is not the expected Git worktree', $wrongRoot->getErrorOutput());

        $parentBridge = $this->process($this->arguments($fixture, [
            '--bridge-root', $this->fixtureRoot.'/public_html',
        ]));
        $parentBridge->run();
        $this->assertFalse($parentBridge->isSuccessful());
        $this->assertStringContainsString('parent public_html cannot be targeted', $parentBridge->getErrorOutput());

        $this->runGit($fixture['app'], ['switch', '-c', 'feature']);
        $wrongBranch = $this->process($this->arguments($fixture));
        $wrongBranch->run();
        $this->assertFalse($wrongBranch->isSuccessful());
        $this->assertStringContainsString('production branch must be main', $wrongBranch->getErrorOutput());

        $this->runGit($fixture['app'], ['switch', 'main']);
        File::append($fixture['app'].'/artisan', "\ndirty\n");
        $dirty = $this->process($this->arguments($fixture));
        $dirty->run();
        $this->assertFalse($dirty->isSuccessful());
        $this->assertStringContainsString('production worktree is dirty', $dirty->getErrorOutput());
    }

    public function test_successful_dry_run_performs_no_mutation(): void
    {
        $fixture = $this->fixture();
        $headBefore = trim($this->gitOutput($fixture['app'], ['rev-parse', 'HEAD']));
        $statusBefore = $this->gitOutput($fixture['app'], ['status', '--porcelain']);
        $bridgeHashBefore = hash_file('sha256', $fixture['bridge'].'/index.php');

        $process = $this->process($this->arguments($fixture));
        $process->mustRun();

        $this->assertStringContainsString('NO PRODUCTION MUTATION PERFORMED', $process->getOutput());
        $this->assertSame($headBefore, trim($this->gitOutput($fixture['app'], ['rev-parse', 'HEAD'])));
        $this->assertSame($statusBefore, $this->gitOutput($fixture['app'], ['status', '--porcelain']));
        $this->assertSame($bridgeHashBefore, hash_file('sha256', $fixture['bridge'].'/index.php'));
    }

    private function fixture(): array
    {
        $app = $this->fixtureRoot.'/app';
        $bridge = $this->fixtureRoot.'/public_html/igna-app';
        $backup = $this->fixtureRoot.'/backup';

        File::makeDirectory($app, 0700, true);
        File::makeDirectory($bridge, 0700, true);
        File::makeDirectory($backup, 0700, true);
        File::put($app.'/artisan', "#!/usr/bin/env php\n");
        File::put($app.'/composer.json', "{}\n");
        File::put($bridge.'/index.php', "<?php\n");

        $this->runGit($app, ['init', '-b', 'main']);
        $this->runGit($app, ['config', 'user.email', 'release-test@example.invalid']);
        $this->runGit($app, ['config', 'user.name', 'Release Test']);
        $this->runGit($app, ['add', 'artisan', 'composer.json']);
        $this->runGit($app, ['commit', '-m', 'fixture']);

        foreach ([
            'database-before.sql.gz',
            'storage-app-before.tar.gz',
            'public-bridge-before.tar.gz',
            'deployment-record.txt',
        ] as $name) {
            File::put($backup.'/'.$name, $name."\n");
        }

        $checksums = collect([
            'database-before.sql.gz',
            'storage-app-before.tar.gz',
            'public-bridge-before.tar.gz',
            'deployment-record.txt',
        ])->map(fn (string $name): string => hash_file('sha256', $backup.'/'.$name).'  '.$name);

        File::put($backup.'/SHA256SUMS', $checksums->implode("\n")."\n");

        return [
            'app' => $app,
            'bridge' => $bridge,
            'backup' => $backup,
            'commit' => trim($this->gitOutput($app, ['rev-parse', 'HEAD'])),
        ];
    }

    private function arguments(array $fixture, array $overrides = []): array
    {
        $arguments = [
            '--preflight',
            '--expected-commit', $fixture['commit'],
            '--backup-dir', $fixture['backup'],
            '--backup-confirmed',
            '--app-root', $fixture['app'],
            '--bridge-root', $fixture['bridge'],
            '--expected-user', $this->currentUser(),
        ];

        foreach (array_chunk($overrides, 2) as [$flag, $value]) {
            $position = array_search($flag, $arguments, true);

            if ($position === false) {
                $arguments[] = $flag;
                $arguments[] = $value;
            } else {
                $arguments[$position + 1] = $value;
            }
        }

        return $arguments;
    }

    private function process(array $arguments): Process
    {
        return new Process(
            ['bash', base_path('scripts/deploy-hostinger.sh'), ...$arguments],
            base_path(),
            ['IGNA_DEPLOY_TEST_MODE' => '1'],
        );
    }

    private function runGit(string $directory, array $arguments): void
    {
        (new Process(['git', ...$arguments], $directory))->mustRun();
    }

    private function gitOutput(string $directory, array $arguments): string
    {
        $process = new Process(['git', ...$arguments], $directory);
        $process->mustRun();

        return $process->getOutput();
    }

    private function currentUser(): string
    {
        $account = posix_getpwuid(posix_geteuid());

        return $account['name'];
    }
}
