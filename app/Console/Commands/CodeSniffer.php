<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Bartlett\Sarif;

class CodeSniffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:sniffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run PHPCS with PSR2 standard on app directories';

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
     * @return mixed
     */
    public function handle()
    {
        $paths = [
            './app', './bootstrap', './database', 
            './public', './routes', './tests'
        ];

        $command = './vendor/bin/phpcs --standard=PSR2 --report-checkstyle=phpcs-report.xml ' . implode(' ', $paths);
        
        $output = shell_exec($command);
        
        $this->info($output ?: 'No issues found.');

        if(!file_exists('phpcs-report.xml')) return 0;

        $checkstyleXml = simplexml_load_file('phpcs-report.xml');
       
        $run = new Sarif\Definition\Run();

        $driver = new Sarif\Definition\ToolComponent();
        $driver->setName('PHP_CodeSniffer');
        $driver->setSemanticVersion('3.10.1');  // Adjust to your phpcs version

        $tool = new Sarif\Definition\Tool();
        $tool->setDriver($driver);

        $run->setTool($tool);

        foreach ($checkstyleXml->file as $file) {
            $fileUri = (string)$file['name'];
            foreach ($file->error as $error) {
                $result = new Sarif\Definition\Result();
                $message = new Sarif\Definition\Message();
                $message->setText((string)$error["message"]);
                $result->setMessage($message);
                $result->setLevel((string)$error["severity"]);
                
                $artifactLocation = new Sarif\Definition\ArtifactLocation();
                $artifactLocation->setUri($fileUri);
                $location = new Sarif\Definition\Location();
                $physicalLocation = new Sarif\Definition\PhysicalLocation();
                $physicalLocation->setArtifactLocation($artifactLocation);
                $region = new Sarif\Definition\Region();
                $region->setStartLine((int)$error["line"]);
                $region->setStartColumn((int)$error["column"]);
                $physicalLocation->setRegion($region);
                $location->setPhysicalLocation($physicalLocation);
                $result->addLocations([$location]);              
                
                $result->setRuleId((string)$error['source']);
                $run->addResults([$result]);
            }
        }

        $sarif = new Sarif\SarifLog([$run]);
        $sarif->setVersion('2.1.0');
        file_put_contents('phpcs-report.sarif', json_encode($sarif, JSON_PRETTY_PRINT));
        unlink('phpcs-report.xml');
        echo "SARIF report generated: phpcs-report.sarif\n";

        return 0;
    }
}
