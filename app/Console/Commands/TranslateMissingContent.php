<?php

namespace App\Console\Commands;

use App\Models\ProposalServiceTemplate;
use App\Models\Proposal;
use App\Models\Service;
use App\Services\Services\ServiceContentTranslator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class TranslateMissingContent extends Command
{
    protected $signature = 'content:translate-missing
        {--dry-run : Report missing or copied dynamic translations without saving}
        {--service= : Limit sync to one service ID}
        {--template= : Limit sync to one proposal template ID}
        {--proposal= : Limit sync to one proposal ID}
        {--source-locale=en : Source locale to translate from, en or es}';

    protected $description = 'Synchronize missing dynamic service and proposal-template translations through the configured provider.';

    private int $checked = 0;

    private int $updated = 0;

    private int $skipped = 0;

    private int $failed = 0;

    public function handle(ServiceContentTranslator $translator): int
    {
        $this->checked = 0;
        $this->updated = 0;
        $this->skipped = 0;
        $this->failed = 0;

        $sourceLocale = $this->option('source-locale') === 'es' ? 'es' : 'en';
        $targetLocale = $sourceLocale === 'es' ? 'en' : 'es';
        $dryRun = (bool) $this->option('dry-run');

        if (! $dryRun && $this->option('service') === null && $this->option('template') === null && $this->option('proposal') === null) {
            $this->components->error('Refusing unrestricted mutation. Use --dry-run or pass --service, --template, or --proposal.');

            return self::FAILURE;
        }

        $this->syncServices($translator, $sourceLocale, $targetLocale, $dryRun);
        $this->syncTemplates($translator, $sourceLocale, $targetLocale, $dryRun);
        $this->syncProposals($translator, $sourceLocale, $targetLocale, $dryRun);

        $this->components->info(sprintf(
            'Dynamic translation sync checked %d fields: %d updated, %d skipped, %d failed.',
            $this->checked,
            $this->updated,
            $this->skipped,
            $this->failed,
        ));

        if ($dryRun) {
            $this->components->warn('Dry run only; no database records were changed.');
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function syncServices(ServiceContentTranslator $translator, string $sourceLocale, string $targetLocale, bool $dryRun): void
    {
        if ($this->option('template') !== null || $this->option('proposal') !== null) {
            return;
        }

        $query = Service::query()->with(['deliverables', 'stages'])->orderBy('id');

        if ($this->option('service') !== null) {
            $query->whereKey((int) $this->option('service'));
        }

        $query->each(function (Service $service) use ($translator, $sourceLocale, $targetLocale, $dryRun): void {
            $this->syncPair($translator, $service, 'name', $sourceLocale, $targetLocale, $dryRun);
            $this->syncPair($translator, $service, 'description', $sourceLocale, $targetLocale, $dryRun);

            $service->deliverables->each(function (Model $deliverable) use ($translator, $sourceLocale, $targetLocale, $dryRun): void {
                $this->syncPair($translator, $deliverable, 'name', $sourceLocale, $targetLocale, $dryRun);
                $this->syncPair($translator, $deliverable, 'description', $sourceLocale, $targetLocale, $dryRun);
            });

            $service->stages->each(function (Model $stage) use ($translator, $sourceLocale, $targetLocale, $dryRun): void {
                $this->syncPair($translator, $stage, 'name', $sourceLocale, $targetLocale, $dryRun);
                $this->syncPair($translator, $stage, 'description', $sourceLocale, $targetLocale, $dryRun);
            });
        });
    }

    private function syncTemplates(ServiceContentTranslator $translator, string $sourceLocale, string $targetLocale, bool $dryRun): void
    {
        if ($this->option('service') !== null || $this->option('proposal') !== null) {
            return;
        }

        $query = ProposalServiceTemplate::query()->with('items')->orderBy('id');

        if ($this->option('template') !== null) {
            $query->whereKey((int) $this->option('template'));
        }

        $query->each(function (ProposalServiceTemplate $template) use ($translator, $sourceLocale, $targetLocale, $dryRun): void {
            $this->syncPair($translator, $template, 'name', $sourceLocale, $targetLocale, $dryRun);

            $template->items->each(function (Model $item) use ($translator, $sourceLocale, $targetLocale, $dryRun): void {
                $this->syncPair($translator, $item, 'description', $sourceLocale, $targetLocale, $dryRun);
            });
        });
    }

    private function syncProposals(ServiceContentTranslator $translator, string $sourceLocale, string $targetLocale, bool $dryRun): void
    {
        if ($this->option('service') !== null || $this->option('template') !== null) {
            return;
        }

        $query = Proposal::query()->orderBy('id');

        if ($this->option('proposal') !== null) {
            $query->whereKey((int) $this->option('proposal'));
        } elseif (! $dryRun) {
            return;
        }

        $query->each(function (Proposal $proposal) use ($translator, $sourceLocale, $targetLocale, $dryRun): void {
            $this->syncPair($translator, $proposal, 'title', $sourceLocale, $targetLocale, $dryRun);
        });
    }

    private function syncPair(
        ServiceContentTranslator $translator,
        Model $model,
        string $baseField,
        string $sourceLocale,
        string $targetLocale,
        bool $dryRun
    ): void {
        $sourceKey = "{$baseField}_{$sourceLocale}";
        $targetKey = "{$baseField}_{$targetLocale}";
        $source = trim((string) $model->getAttribute($sourceKey));
        $target = trim((string) $model->getAttribute($targetKey));

        $this->checked++;

        if ($source === '') {
            $this->skipped++;

            return;
        }

        if ($translator->isUsableTranslation($source, $target)) {
            $this->skipped++;

            return;
        }

        try {
            $translated = $translator->translate($source, $sourceLocale, $targetLocale);
        } catch (Throwable $exception) {
            $this->failed++;
            $this->components->warn(sprintf(
                'Translation unavailable for %s #%s %s.',
                class_basename($model),
                (string) $model->getKey(),
                $targetKey,
            ));

            return;
        }

        if (! $translator->isUsableTranslation($source, $translated)) {
            $this->failed++;
            $this->components->warn(sprintf(
                'Provider returned an unusable translation for %s #%s %s.',
                class_basename($model),
                (string) $model->getKey(),
                $targetKey,
            ));

            return;
        }

        $this->updated++;

        if ($dryRun) {
            return;
        }

        $model->forceFill([$targetKey => $translated])->save();
    }
}
