<?php
namespace App\Geonamesdump\Loader;
use App\Geonames\Entity\Country;
use App\Geonamesdump\Model\LoaderInteface;

/**
 * Load Country.
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
class CountryLoader extends BaseLoader implements LoaderInteface
{
    use FilterTrait;

    /**
     * @var string
     */
    protected $name = 'country';

    /**
     * @inheritDoc
     */
   public function getCsvOrderedCols(): array
    {
       return array( 'countryid', 'isoalpha3', 'isonumeric',
                        'fipscode', 'name', 'capital', 'areainsqkm', 'population',
                        'continent', 'tld', 'currency', 'currencyname', 'phone',
                        'postalcodeformat', 'postalcoderegex', 'languages', 'geonameid',
                        'neighbours', 'equivalentfipscode');
    }

    /**
     * Serbia and Montenegro with geonameid = 863038 no longer exists. isonumeric 891
     * AN (the Netherlands Antilles) with geonameid = 3513447  was dissolved on 10 October 2010. isonumeric 530
     * @inheritDoc
     */
    public function setEntity($data): ?Country
    {
        $csv = array_map(function($value)
            {
                return $value === "" ? null : $value;
            }, \preg_split( "/[\r\t]/", $data ));
        $entity = new Country();
        foreach($this->getCsvOrderedCols() as $k=>$field){

            if($field==='countryid'){

                if($this->filterCountry($csv[$k], $this->getFilter())===false) return null;

                $reflection = new \ReflectionClass($entity);
                $property = $reflection->getProperty('id');
                $property->setAccessible(true);
                $property->setValue($entity, $csv[$k]);
                continue;
            }

            $fn='set'.ucfirst(strtolower($field));

            $entity->$fn($csv[$k]);
        }

        if(in_array($entity->getIsonumeric(), array(891, 530))) return null;//Serbia and Montenegro, Netherlands Antilles
        return  $entity;
    }

    /**
     * @inheritDoc
     */
    public function load(): LoaderInteface
    {
        $this->Commonloader();
       // $this->repositoryHelper->getManager()->clear(Country::class);
        return $this;
    }
}