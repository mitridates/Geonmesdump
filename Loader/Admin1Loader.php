<?php
namespace App\Geonamesdump\Loader;
use App\Geonames\Entity\Admin1;
use App\Geonamesdump\Model\LoaderInteface;

/**
 * Load State (US), Comunidad Autónoma (ES), départements (FR).
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class Admin1Loader extends BaseLoader  implements LoaderInteface
{
    /**
     * @var string
     */
    protected $name = 'admin1';

    /**
     * @inheritDoc
     */
    public function getCsvOrderedCols(): array
    {
        return ['admin1id', 'nameascii', 'name', 'geonameid'];
    }

    /**
     * @inheritDoc
     */
    public function setEntity($data): ?Admin1
    {
        $csv = array_map(fn($value) => $value === "" ? null : $value, \preg_split( "/[\r\t]/", (string) $data ));
        $cols = $this->getCsvOrderedCols();
        $entity = new Admin1();
        $code = $csv[array_search('admin1id', $cols)];
        [$countryCode, $admin1Code] = explode('.', (string) $code);
        if($this->filterAdmin1($countryCode, $admin1Code, $this->getFilter())===false) return null;

        $entity->setCountry($this->repositoryHelper->getCountry($countryCode));

        //set ID
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $code);

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
        $this->Commonloader();
        return $this;
    }
}