<?php
namespace App\Geonamesdump\Loader;
use App\Geonames\Entity\Admin2;
use App\Geonamesdump\Model\LoaderInteface;

/**
 * Load Admin2.
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class Admin2Loader extends BaseLoader implements LoaderInteface
{
    /**
     * @var string
     */
    protected $name = 'admin2';

    /**
     * @inheritDoc
     */
    public function getCsvOrderedCols(): array
    {
        return ['admin2id', 'nameascii', 'name', 'geonameid'];
    }

    /**
     * @inheritDoc
     */
    public function setEntity($data): ?Admin2
    {
        $csv = array_map(fn($value) => $value === "" ? null : $value, \preg_split( "/[\r\t]/", (string) $data ));

        $cols = $this->getCsvOrderedCols();

        $entity = new Admin2();

        $code = $csv[array_search('admin2id', $cols)];

        [$countryCode, $admin1Code, $admin2Code] = explode('.', (string) $code);

        if($this->filterAdmin2($countryCode, $admin1Code, $admin2Code, $this->getFilter())===false) return null;

        $entity->setCountry($this->repositoryHelper->getCountry($countryCode));
        $entity->setAdmin1($this->repositoryHelper->getAdmin1($countryCode.'.'.$admin1Code));

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
