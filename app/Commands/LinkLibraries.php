<?php

namespace App\Commands;

use App\Commands\Helpers\CommandHelper;
use App\Fox\Helpers\NetworkHelper;
use App\Fox\LinkLibraries\LinkLibrariesFactory;
use LaravelZero\Framework\Commands\Command;
use App\Fox\LinkLibraries\DataType\Command as CommandType;

class LinkLibraries extends Command
{
    const GLOBAL_NPM_DIRECTORY_LOCATION = '/usr/local/lib/node_modules/@foxcorp/';
    const REMOVE_MODULES_AND_LOCK       = 'rm -rf node_modules/ package-lock.json';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'link-libraries
                            {--network= : The API network (ex: foxnews)}
                            {--npm : Link with npm link}
                            {--r|revert : Revert local link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Links the local libraries inside the API repo.';

    private $networkName;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->networkName = $this->option('network');

        if( empty($this->networkName) || !array_search($this->networkName, NetworkHelper::NETWORKS) ){
            $this->networkName = $this->choice(
                'What network do you want to link?',
                CommandHelper::mapChoices( array_values(NetworkHelper::NETWORKS) )
            );

        }

        $linkType = $this->option('npm') ? 'npm' : 'file';

        $start = microtime(true);

        $linkClass = LinkLibrariesFactory::getLinkage( $linkType, $this->networkName );

        $this->executeContainerCommands( $linkClass->commands() );

        $this->restartDockerContainer();

        $time = floor((microtime(true) - $start) / 60);

        $this->info("\nExecution Time: " . ($time > 1 ? $time : '<1') . " minute" . ($time > 1 ? 's' : ''));

        $this->notify("Artisan Command Complete", "Local libraries have been linked");

        return 0;
    }

    private function getLambdaContainer(): string {
        return NetworkHelper::getNetworkCode($this->networkName) . '-lambdanode';
    }

    private function restartDockerContainer(): self {
        $cmd = sprintf("docker restart %s", $this->getLambdaContainer());

        $this->output->write("\nRestarting lambda container... ");

        exec($cmd, $output, $returnVal);

        if($returnVal === 0){
            $this->output->writeln("<fg=black;bg=green> DONE </>");
        } else {
            $this->output->writeln("<error> ERROR </error>");
        }

        return $this;
    }

    private function executeContainerCommands( array $commands ): self {
        $this->output->note("Local libraries are now being linked.\nThis may take some time.");

        $bar = $this->output->createProgressBar(count($commands));

        $bar->start();

        /** @var CommandType $command */
        foreach ($commands as $command) {
            $this->runTask($command->getCommand());

            $bar->advance();
        }

        $bar->finish();

        $this->line("\n");

        return $this;
    }

    private function runTask( string $cmd ): ?array {
        exec($cmd, $output, $return_val);

        return $output;
    }
}
