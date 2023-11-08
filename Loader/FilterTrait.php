<?php
namespace App\Geonamesdump\Loader;


/**
 * Filter
 * @author Mitridates <mitridates[at]gmail.com>
 * @package App\Geonamesdump\Loader
 */
trait FilterTrait
{

    /**
     * Filter continent
     */
    protected function filterContinent(string $continentCode, array $continents): bool|null
    {
        if(array_key_exists($continentCode, $continents)){ //null o multidimensional
            return (empty($continents[$continentCode]))? null : true;
        }
        return (in_array($continentCode, $continents))? null : false;
    }

    /**
     * Country exists in filter?
     * True/false if exists in array, null: no filter
     */
    protected function filterCountry(string $countryCode, array $filter): bool|array|null
    {
        if(array_key_exists($countryCode, $filter)){
            return (empty($filter[$countryCode]))? null : $filter[$countryCode];
        }

        return in_array($countryCode, $filter)? true : false;//[ES, DE, ... ]
    }

    /**
     * Admin1
     * True/false if exists in array, null: no filter, array: custom filter
     */
    protected function filterAdmin1(string $countryCode, ?string $admin1Code, array $filter): bool|array|null
    {
        $countryFiltered = $this->filterCountry($countryCode, $filter);

        if(!is_array($countryFiltered)) return $countryFiltered;

        if(array_key_exists($admin1Code, $countryFiltered)){//[ES: {51:[...], 31:[...]} ] >> $admin1Code === key
            return (empty($countryFiltered[$admin1Code]))? null : $countryFiltered[$admin1Code];
        }
        return in_array($admin1Code, $countryFiltered);//[ES: [51, 31] ] >> $admin1Code === value
    }

    /**
     * Admin2
     * True/false if exists in array, null: no filter, array: custom filter
     */
    protected function filterAdmin2(string $countryCode, ?string $admin1Code, ?string $admin2Code, array $filter): bool|array|null
    {
        $admin1Filtered = $this->filterAdmin1($countryCode, $admin1Code, $filter);

        if(!is_array($admin1Filtered)) return $admin1Filtered;

        if(array_key_exists($admin2Code, $admin1Filtered)){//$admin2Code === key
            return (empty($admin1Filtered[$admin2Code]))? null : $admin1Filtered[$admin2Code];
        }

        return in_array($admin2Code, $admin1Filtered);//$admin2Code === value
    }

    /**
     * Admin3
     * True/false if exists in array, null: no filter, array: custom filter
     */
    protected function filterAdmin3(string $countryCode, ?string $admin1Code, ?string $admin2Code, ?string $admin3Code, array $filter): bool|array|null
    {
        $admin2Filtered = $this->filterAdmin2($countryCode, $admin1Code, $admin2Code, $filter);

        if(!is_array($admin2Filtered)) return $admin2Filtered;

        if(array_key_exists($admin3Code, $admin2Filtered)){//$admin2Code === key
            return (empty($admin2Filtered[$admin3Code]))? null : $admin2Filtered[$admin3Code];
        }

        return in_array($admin3Code, $admin2Filtered);
    }

    public function filterByFeature(string $countryCode, array $csv, array $cols, array $filter)
    {
        $featureCode= $csv[array_search('featurecode', $cols)];
        $featureClass= $csv[array_search('featureclass', $cols)];
        $codes = $filter[$countryCode]['code'];

        if(array_key_exists($featureClass, $codes)){ //FeatureClass = null o multidimensional array >> [P: null, ... ] || [P: []] || [P: [array]]
            return (empty($codes[$featureClass]))? true : in_array($featureCode, $codes[$featureClass]);
        }else{
            return (in_array($featureClass, $codes))? null : false;//[P, L, S, T, ... ]
        }
    }
}