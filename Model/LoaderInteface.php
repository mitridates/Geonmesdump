<?php
namespace App\Geonamesdump\Model;
use App\Geonames\Entity\Country;
use App\Geonames\Entity\Admin1;
use App\Geonames\Entity\Admin2;
use App\Geonames\Entity\Admin3;
use App\Geonames\Entity\Geonames;
use Doctrine\ORM\Mapping\Entity;

interface LoaderInteface
{
    /**
     * Relation array(property names) <-> explode(line)
     * @return array
     */
    public function getCsvOrderedCols(): array;

    /**
     * Set Entity from cvs line
     * @return Geonames|Country|Admin1|Admin2|Admin3|null
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function setEntity(string|\SimpleXMLElement $data);

    /**
     * Read and load.
     * @return LoaderInteface
     * @throws \Exception
     */
    public function load(): LoaderInteface;
}