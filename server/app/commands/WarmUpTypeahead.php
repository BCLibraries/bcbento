<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class WarmUpTypeahead extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'typeahead:warmup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $handle = fopen($this->argument('list'), 'r');
        $num_terms = $this->countTerms($handle);

        // Amount of time to wait between calls.
        $wait = ceil(($this->argument('duration') / $num_terms) * .9);

        $controller = App::make('AutosuggestController');

        while (($term = fgets($handle)) !== False) {
            $term = trim($term);
            $this->fetchTerm($term, $controller);
            sleep($wait);
        }

    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('list', InputArgument::REQUIRED, 'The file containing terms to warm up'),
            array('duration', InputArgument::REQUIRED, 'How long to take to iterate in seconds')
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
        );
    }

    protected function fetchTerm($term, AutoSuggestController $controller)
    {
        try {
            $controller->suggest($term);
        } catch (\Exception $e) {

        }
    }

    protected function countTerms($handle)
    {
        $count = 0;
        while (!feof($handle)) {
            fgets($handle);
            $count++;
        }
        rewind($handle);
        return $count;

    }
}
