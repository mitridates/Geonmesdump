<?php
namespace App\Geonamesdump\Loader;
use App\Geonamesdump\Util\RepositoryHelper;
use App\Geonamesdump\Util\FileHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Common process for loaders
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class BaseLoader
{
    use FilterTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var FileHelper
     */
    public $filehelper;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var array
     */
    public $appConfig;
    /**
     * @var array
     */
    public $loaderConfig ;

    /**
     * @var RepositoryHelper
     */
    public $repositoryHelper;


    /**
     * @throws \Exception
     */
    public function __construct(FileHelper &$fileHelper, RepositoryHelper &$repositoryHelper, array &$appConfig, bool $output = true)
    {
        $this->filehelper       =& $fileHelper;
        $this->repositoryHelper =& $repositoryHelper;
        $this->output           =  $output ?  new ConsoleOutput() :  new NullOutput();
        $this->appConfig        =& $appConfig;
        $this->loaderConfig     = $this->setLoaderconfig($appConfig['dump']);
    }

    /**
     * Filter exists?
     * @throws \Exception
     */
    public function setLoaderconfig(array $dump) : array
    {
        if(!array_key_exists($this->name, $dump))
        {
            throw new \Exception(sprintf('Undefined filter in parameters.yml#dump:%s', $this->name));
        }
        if(!is_array($dump[$this->name]))
        {
            throw new \Exception(sprintf('Filter %s must be or type array %s given', $this->name, gettype($dump[$this->name])));
        }

        return  $dump[$this->name];
    }

    /**
     * @return array|null
     */
    public function getFilter()
    {
        return $this->loaderConfig;
    }

    /**
     * Read and load file to DB.
     * @return void
     * @throws \Exception
     */
    protected function Commonloader()
    {
        $filename = $this->appConfig['loaders'][$this->name]['file'];
        $this->filehelper->downloadFile($filename);
        $repository= $this->repositoryHelper->getRepositoryByEntityName($this->name);
        $em = $this->repositoryHelper->getManager();
        //counters
        $time_start = microtime(true);
        $numRows = $repository->countAll();
        $line = $repe = $filtersaisNo = $filtersaisYes = $flushToDb = $passFilterAndNotInDB = $queue = $isComment= 0;
        $file = $this->appConfig['config']['tmpdir'].$filename;
        $flashMsg = sprintf('<comment>Loading %s file. %s, (%s)</comment>',
                                ucfirst($this->name) ,
                                $file,
                                str_replace(".", "," , strval(round(filesize($file)/1024 ** 2, 2)))."mb)"
                            );

        $this->output->writeln($flashMsg);

        $file_array = file($file);
        $numLines = count($file_array);
        $progressBar = new ProgressBar($this->output, $numLines);

        $progressBar->start();

        foreach ($file_array as $string) {
            //line of file
            $line++;
            $progressBar->advance();

            if(!empty($this->appConfig['config']['limit']) && $line>$this->appConfig['config']['limit']) break;

            if(\preg_match('/^#/', $string)){
                $isComment++;
                continue;
            }

            if(!$entity = $this->setEntity($string, $line)){
                $filtersaisNo++;
                continue;
            }
            $filtersaisYes++;
            if(null !== $repository->getRegistry($entity))
            {
                $repe++;
                if(!$this->appConfig['config']['overwrite']) continue;
            }else{
                $passFilterAndNotInDB++;
            }


            if($this->appConfig['config']['flush']){
                $em->persist($entity);
                $flush = $queue>0 && $this->appConfig['config']['flusheach'] <= $queue;

                if($flush){
                    $em->flush();
                    $queue=0;
                }else{
                    $queue++;
                }
                $flushToDb++;
            }
        }

        $em->flush();

        $progressBar->finish();

        $currentNumRows = $repository->countAll();

        $this->output->writeln('');

        $table = new Table($this->output);

        $table
            ->setHeaders(array(
                '<info>'.ucfirst($this->name) .' global params</info>',
                '<info>Database Before:'.$numRows.', Now:'.$currentNumRows.'</info>',
                '<info>Lines read: '.$line.' in '.round((microtime(true) - $time_start), 3).' sec.</info>',
                '<info>File: '.$filename.'</info>',

                ))
            ->setRows(array(
                array(
                    sprintf('Flush: %s | Overwrite.: %s', $this->appConfig['config']['flush']?  'true':'false', $this->appConfig['config']['overwrite'] ? 'true':'false'),
                    sprintf('Insert: %s | Repeated: %s', $currentNumRows-$numRows, $repe),
                    sprintf('Pass filter: %s && Not in DB: %s', $filtersaisYes, $passFilterAndNotInDB),
                    sprintf('Size: %s mb', str_replace(".", "," , strval(round(filesize($file)/1024 ** 2, 2))))
                    )
                ));
        $table->render() ;

    }

}