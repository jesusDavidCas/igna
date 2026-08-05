<?php

namespace App\Console\Commands;

use App\Services\Launch\LaunchDataResetter;
use Illuminate\Console\Command;
use RuntimeException;

class LaunchDataResetCommand extends Command
{
    protected $signature = 'igna:launch-reset
        {--force : Execute the reset after confirmation}
        {--confirm= : Required confirmation token for force mode}';

    protected $description = 'Dry-run or execute the guarded launch data reset while preserving master data.';

    public function handle(LaunchDataResetter $resetter): int
    {
        $force = (bool) $this->option('force');

        if ($force && $this->option('confirm') !== LaunchDataResetter::CONFIRMATION) {
            $this->components->error('Refusing launch reset. Pass --confirm='.LaunchDataResetter::CONFIRMATION.' with --force.');

            return self::FAILURE;
        }

        try {
            $counts = $force ? $resetter->reset() : $resetter->preview();
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Area', 'Count'],
            collect($counts)
                ->reject(fn (mixed $count, string $key): bool => $key === 'preserved_superadmin_id')
                ->map(fn (mixed $count, string $key): array => [$key, $count])
                ->values()
                ->all(),
        );

        $this->components->info('Preserved superadministrator: '.LaunchDataResetter::PRESERVED_SUPERADMIN_EMAIL);

        if (! $force) {
            $this->components->warn('Dry run only; no database records or files were changed.');

            return self::SUCCESS;
        }

        $this->components->warn('Launch data reset completed. Production execution must be separate from deployment.');

        return self::SUCCESS;
    }
}
