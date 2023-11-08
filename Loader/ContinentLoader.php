<?php
namespace App\Geonamesdump\Loader;

use App\Geonames\Entity\Geonames;
use App\Geonamesdump\Model\LoaderInteface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;

/**
 * Load Continent
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class ContinentLoader extends BaseLoader implements LoaderInteface
{
    use FilterTrait;

    /**
     * @var string
     */
    protected $name = 'continent';

    /**
     * @inheritDoc
     */
    public function getCsvOrderedCols(): array
    {
        return ['geonameid', 'name', 'asciiname', 'alternatenames', 'latitude', 'longitude', 'featureclass', 'featurecode', 'elevation', 'dem'];
    }

    /**
     * Xml response to Geonames entity
     * @inheritDoc
     */
    public function setEntity($data): ?Geonames
    {
        $entity = new Geonames();
        $arr = $this->toArray($data);
        $cols = $this->getCsvOrderedCols();

        if($this->filterContinent($arr['continentcode'], $this->getFilter())===false) return null;

        //set ID
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $arr['geonameid']);

        foreach(\array_diff($cols, ["geonameid"]) as $field){
            $fn='set'.ucfirst(strtolower((string) $field));
            $entity->$fn($arr[$field]);
        }
        return $entity;
    }

    /**
     * Geonames SimpleXMLElement to array
     * @param \SimpleXMLElement $xml Geonames xml response
     * @return array
     */
    public function toArray(\SimpleXMLElement $xml): array
    {
        $arr = [];
        foreach($xml as $k => $v){
            $name = $this->match($k);
            if($name == 'alternatename'){
                if($xml->$k==null) continue;
                foreach ($xml->$k as $y => $z){
                    $lang = (string)$z->attributes()->lang;
                    $val = (string)$z[0];
                    $arr[$name][$lang] = $val;
                }
                continue;
            }
            if($name == 'bbox'){
                $arr[$name]= (array)$xml->$k;
                continue;
            }

            if($xml->$k===null || $xml->$k===''){
                $arr[$name]= null;
            }else{
                $arr[$name]= (string)$xml->$k;
            }

        }
        return $arr;
    }

    /**
     * DB names
     */
    public function match(string $name): string
    {
        $x=  ['lat'=>'latitude', 'lng'=>'longitude', 'fcl'=>'featureclass', 'fcode'=>'featurecode', 'srtm3' => 'dem'];
        return (array_key_exists($name, $x))? strtolower($x[$name]) : strtolower($name);
    }

    /**
     * @throws \Exception
     */
    public function validateSimpleXmlResponse(\SimpleXMLElement $xml) : bool
    {
        if((bool)$xml->geoname) return true;

        if($xml->getName()!=='geonames'){
            throw new \Exception(sprintf('Unexpected XML element name "%s, expected "geonames""', $xml->getName()));
        }

        if($xml->status->count()){
            throw new \Exception(sprintf('Status error with message: %s', $xml->status->attributes()->message));
        }

        return false;
    }

    /**
     * Load XML
     * http://ws.geonames.org/search?q=cont&featureCode=CONT&style=full&userName=XXX
     * @inheritDoc
     */
    public function load(): LoaderInteface
    {

        if (!is_array($this->appConfig['webService'])){
            $this->output->writeln('<error>webService parameters not defined. Uncomment & configure webService array in parameters.yml</error>');
            return $this;
        }

        $ws = $this->appConfig['webService']['geonamesWebService'];
        $url = sprintf('%ssearch?q=cont&featureCode=CONT&style=full&userName=%s&lang=%s', $ws['apiUrl'], $ws['userName'], $ws['defaultLanguage']);
        $response = file_get_contents($url);
        $continents = simplexml_load_string($response);

        if(!$this->validateSimpleXmlResponse($continents))
        {
            throw new \Exception(sprintf('Wrong xml format, check url: %s', $url));
        }

        $repository= $this->repositoryHelper->getRepositoryByEntityName($this->name);
        $em = $this->repositoryHelper->getManager();
        $time_start = microtime(true);
        $numRows = $repository->countAll();
        $line = $repe = $filtersaisNo = $filtersaisYes = $flushToDb = $passFilterAndNotInDB = $queue = $isComment= 0;
        $numLines = count($continents->geoname);
        $flashMsg = sprintf('<comment>Loading %s xml</comment>', ucfirst($this->name));

        $this->output->writeln($flashMsg);

        $progressBar = new ProgressBar($this->output, $numLines);
        $progressBar->start();

        foreach($continents->geoname as $c){
            $line++;
            $progressBar->advance();
            $entity = $this->setEntity($c);

            if(!$entity) {
                $filtersaisNo++;
                continue;
            }

            $filtersaisYes++;

            if(null !== $repository->getRegistry($entity))
            {
                $repe++;
                if(!$this->appConfig['config']['overwrite']) continue;
            }

            $passFilterAndNotInDB++;

            if($this->appConfig['config']['flush']){//flush to database
                $em->persist($entity);
                $flush = ($queue>0 && $this->appConfig['config']['flusheach'] <= $queue)? true : false;
                //persist y flush
                if($flush){
                    $em->flush();
                    $queue=0;
                }else{
                    $queue++;
                }
            }

        }
        $progressBar->finish();

        $em->flush();

        if(isset($entity) && $entity!=null) $em->clear($entity::class);

        $currentNumRows = $repository->countAll();

        $this->output->writeln('');

        $table = new Table($this->output);

        $table
            ->setHeaders(array(
                '<info>'.ucfirst($this->name) .' global params</info>',
                '<info>Database Before:'.$numRows.', Now:'.$currentNumRows.'</info>',
                '<info>Lines read: '.$line.' in '.round((microtime(true) - $time_start), 3).' sec.</info>'
            ))
            ->setRows(array(
                array(
                    sprintf('Flush: %s | Overwrite.: %s', $this->appConfig['config']['flush']?  'true':'false', $this->appConfig['config']['overwrite'] ? 'true':'false'),
                    sprintf('Insert: %s | Repeated: %s', $currentNumRows-$numRows, $repe),
                    sprintf('Pass filter: %s && Not in DB: %s', $filtersaisYes, $passFilterAndNotInDB),
                )
            ));
        $table->render() ;

        return $this;
    }
}