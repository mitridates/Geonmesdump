<?php
namespace App\Geonamesdump\Loader;

use App\Geonames\Entity\Geonames;
use App\Geonamesdump\Model\LoaderInteface;

/**
 * Load Geonames
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class GeonamesLoader extends BaseLoader implements LoaderInteface
{
    use FilterTrait;

    /**
     * @var string
     */
    protected $name = 'geonames';

    /**
     * @inheritDoc
     */
    public function getCsvOrderedCols(): array
    {
        return ['geonameid', 'name', 'asciiname', 'alternatenames', 'latitude', 'longitude', 'featureclass', 'featurecode', 'country', 'cc2', 'admin1', 'admin2', 'admin3', 'admin4', 'population', 'elevation', 'dem', 'timezone', 'modificationdate'];
    }

    /**
     * @inheritDoc
     */
    public function setEntity($data): ?Geonames
    {
        $csv = array_map(fn($value) => $value === "" ? null : $value, \preg_split( "/[\r\t]/", (string) $data ));
        $cols = $this->getCsvOrderedCols();
        $country = $csv[array_search('country', $cols)];

        if(isset($this->getFilter()[$country]['code'])){
            if($this->filterByFeature($country, $csv, $cols, $this->getFilter())===false) return null;
        }

        $entity = new Geonames();

        //set ID
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $csv[array_search('geonameid', $cols)]);

        $date = \DateTime::createFromFormat('Y-m-d',\preg_replace( "/[\n]/", '', (string) $csv[array_search('modificationdate', $cols)]));

        $entity->setModificationdate($date);

        foreach(\array_diff($cols, ['geonameid', 'modificationdate']) as $field){
            $fn='set'.ucfirst(strtolower((string) $field));
            $entity->$fn($csv[array_search($field, $cols)]);
        }
        return $entity;
    }


    /**
     * @inheritDoc
     */
    public function load(): LoaderInteface
    {
        foreach (array_keys($this->getFilter()) as $countryCode)
        {
            $zip = $countryCode.'.zip';
            $txt=  $countryCode.'.txt';

            switch (true)
            {
                case $this->filehelper->getFileFromLocaldir($txt):
                    break;
                case $this->filehelper->getFileFromLocaldir($zip):
                    $this->filehelper->unzip($zip);
                    break;
                default:
                    $this->filehelper->downloadFile($zip);
                    $this->filehelper->unzip($zip);
            }

            $this->appConfig['loaders'][$this->name]['file'] = $countryCode.'.txt';
            $this->Commonloader();
        }

        return $this;
    }
}