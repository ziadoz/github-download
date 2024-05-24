<?php

namespace App\Commands;

use Carbon\Carbon;
use Exception;
use Generator;
use Github\ResultPager;
use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use SplFileInfo;

class DownloadGists extends Command
{
    protected $signature   = 'dl-gists {--skip-meta-json} {--skip-clone} {--zip} {--fresh}';
    protected $description = 'Download GitHub gists as JSON';

    protected string $author;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! env('GITHUB_TOKEN')) {
            $this->components->error('Missing environment variable: GITHUB_TOKEN');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->clearStorage();
        }

        if (! $this->option('skip-meta-json')) {
            $this->downloadGistsMetaJson();
        }

        if (! $this->option('skip-clone')) {
            $this->cloneGists();
        }

        if ($this->option('zip')) {
            $this->zipGists();
        }

        return self::SUCCESS;
    }

    protected function clearStorage(): void
    {
        $this->components->info('Clearing storage...');

        Process::run(sprintf(
            'find %s -not -name ".gitignore" -delete',
            storage_path('gists'),
        ));
    }

    protected function downloadGistsMetaJson(): void
    {
        $this->components->info('Downloading gists...');

        foreach ($this->getGists() as $gist) {
            $this->saveGistMetaJson($gist);
        }
    }

    protected function cloneGists(): void
    {
        $this->components->info('Cloning gists...');

        $files = (new Filesystem)->allFiles(storage_path('gists'));

        $progress = $this->output->createProgressBar(count($files));
        $progress->start();

        foreach ($files as $file) {
            $this->cloneGist(json_decode($file->getContents(), true));
            $progress->advance();
        }

        $progress->finish();
    }

    protected function zipGists(): void
    {
        $this->components->info('Zipping gists...');

        Process::run(sprintf(
            '(cd %s && zip -r ./gists-%s.zip ./gists/ && cd %s)',
            storage_path(),
            Carbon::now()->format('Ymdhis'),
            base_path(),
        ))->throw();
    }

    protected function getGists(): Generator
    {
        return (new ResultPager(GitHub::connection()))
            ->fetchAllLazy(GitHub::connection()->gists(), 'all', []);
    }

    protected function saveGistMetaJson(array $gist): void
    {
        $path = storage_path('gists/meta');

        (new Filesystem)->makeDirectory($path, 0744, true, true);

        file_put_contents(
            sprintf('%s/%s-meta.json', $path, $gist['id']),
            json_encode($gist, JSON_PRETTY_PRINT),
        );
    }

    protected function cloneGist(array $gist): void
    {
        $path = storage_path(sprintf(
            'gists/full/%s/%s',
            Carbon::parse($gist['created_at'])->format('Y'),
            Str::slug($gist['description']),
        ));

        (new Filesystem)->makeDirectory($path, 0744, true, true);

        Process::run([
            'git',
            'clone',
            sprintf('https://gist.github.com/%s.git', $gist['id']),
            $path,
        ])->throw();
    }
}
