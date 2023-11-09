<?php
namespace App\Geonamesdump\Loader;
use App\Geonames\Entity\Admin3;
use App\Geonamesdump\Model\LoaderInteface;

/**
 * Load Admin3.
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class Admin3Loader extends BaseLoader implements LoaderInteface
{

    /**
     * @var string
     */
    protected $name = 'admin3';

    /**
     * @inheritDoc
     */
    public function getCsvOrderedCols(): array
    {
        return ['geonameid', 'name', 'asciiname', 'alternatenames', 'latitude', 'longitude', 'featureclass', 'featurecode', 'country', 'alternatecountrycodes', 'admin1', 'admin2', 'admin3', 'admin4', 'population', 'elevation', 'dem', 'timezone', 'modificationdate'];
    }


    /**
     * Filter Admin3 with featureClass A && featureCode ADM3
     */
    protected  function filterInClass(string $countryCode, string $admin1Code, string $admin2Code, array $csv, array $cols,  bool|array|null $filter): bool
    {
        $featureCode= $csv[array_search('featurecode', $cols)];
        $admin3Code= $csv[array_search('admin3', $cols)];
        if($featureCode!='ADM3') return false;

        return !(($this->filterAdmin3($countryCode, $admin1Code, $admin2Code, $admin3Code, $filter) === false));
    }

    /**
     * @inheritDoc
     */
    public function setEntity($data): ?Admin3
    {
        $csv = \array_map(fn($value) => $value === "" ? null : $value, \preg_split( "/[\r\t]/", (string) $data ));
        $cols = $this->getCsvOrderedCols();
        $entity = new Admin3();
        $countryCode = $csv[array_search('country', $cols)];
        $admin1Code= $csv[array_search('admin1', $cols)];
        $admin2Code= $csv[array_search('admin2', $cols)];
        $admin3Code= $csv[array_search('admin3', $cols)];

        //@TODO Por ahora no hay soporte para Admin2 huerfanos
        if(!$admin2Code) return null;

        if(!$this->filterInClass($countryCode, $admin1Code, $admin2Code, $csv, $cols, $this->getFilter())) return null;
        $country= $this->repositoryHelper->getCountry($countryCode);
        $admin1= $this->repositoryHelper->getAdmin1($countryCode.'.'.$admin1Code);
        $admin2= $this->repositoryHelper->getAdmin2($countryCode . '.' . $admin1Code . '.' . $admin2Code);


        $entity->setCountry($country);
        $entity->setAdmin1($admin1);
        $entity->setAdmin2($admin2);

        //set ID
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $countryCode.'.'.$admin1Code.'.'.$admin2Code.'.'.$admin3Code);

        foreach(['nameascii','name','geonameid'] as $field){
            $fn='set'.ucfirst(strtolower($field));
            $val = $csv[array_search($field, $cols)];
            if($field=='geonameid') $val = (int)$val;
            $entity->$fn($val);
        }

        return $entity;
    }

    /**
     * @inheritDoc
     */
    public function load(): LoaderInteface
    {
        $countries = (is_int(key($this->getFilter())))? $this->getFilter() : array_keys($this->getFilter());
        $pattern= "/\\tADM3\\t/";

        foreach ($countries as $countryCode)
        {
            $zip = $countryCode.'.zip';
            $txt=  $countryCode.'.txt';
            $endFileName= $countryCode.'.admin3Codes.txt';
            if(!$this->filehelper->searchForCustomFileOrCachedFile($endFileName)){
                switch (true)
                {
                    case $this->filehelper->searchForCustomFileOrCachedFile($txt):
                        break;
                    case $this->filehelper->searchForCustomFileOrCachedFile($zip):
                        $this->filehelper->unzip($zip);
                        break;
                    default:
                        $this->filehelper->downloadFile($zip);
                        $this->filehelper->unzip($zip);
                }

                //Grep lines with <tab>ADM3<tab> pattern to file in temporary dir
                $endFile= $this->filehelper->getTmpdir().$endFileName;
                $lines = preg_grep($pattern, file($this->filehelper->getTmpdir().$txt));
                file_put_contents($endFile, $lines);

            }

            $this->appConfig['loaders'][$this->name]['file'] = $endFileName;
            $this->Commonloader();
        }
        return $this;
    }

}