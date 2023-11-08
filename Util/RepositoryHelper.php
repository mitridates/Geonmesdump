<?php
namespace App\Geonamesdump\Util;
use App\Geonames\Entity\Admin1;
use App\Geonames\Entity\Admin2;
use App\Geonames\Entity\Admin3;
use App\Geonames\Entity\Country;
use App\Geonames\Entity\Geonames;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;

/**
 * Reduce database calls
 * @package App\Geonamesdump\Util
 */
class RepositoryHelper
{
    private array $ObjectRepositoryBag = [];
    private array $registryBag = [];

    public function __construct(private readonly ObjectManager $em)
    {
    }

    /**
     * @throws \Exception
     */
    public function getRepositoryByEntityName(string $name): ObjectRepository
    {
        return match ($name) {
            'country' => $this->getRepository(Country::class),
            'admin1' => $this->getRepository(Admin1::class),
            'admin2' => $this->getRepository(Admin2::class),
            'admin3' => $this->getRepository(Admin3::class),
            'continent', 'geonames' => $this->getRepository(Geonames::class),
            default => throw new \Exception(sprintf('Invalid argument "%s". Expected country|admin1|admin2|admin3|continent|geonames', $name)),
        };

    }

    /**
     * @throws \Exception
     */
    public function getRepository(string $persistentObject): ObjectRepository
    {

        if(isset($this->ObjectRepositoryBag[$persistentObject]))
        {
            return $this->ObjectRepositoryBag[$persistentObject];

        }else{
            $newRepository =  $this->em->getRepository($persistentObject);
        }

        if($newRepository == null){
            throw new \Exception(sprintf('Unknown Repository %s', $persistentObject));
        }else{
            $this->ObjectRepositoryBag[$persistentObject]= $newRepository;
            return $newRepository;
        }
    }

    /**
     * @throws \Exception
     */
    public function getRegistry(string $persistentObject, string $id): Admin1|Country|Admin3|Admin2|Geonames|null
    {

        if(!isset($this->registryBag[$persistentObject][$id])){
            $repository=  $this->getRepository($persistentObject);
            $this->registryBag[$persistentObject][$id]= $repository->getRegistry($id);
        }
        return $this->registryBag[$persistentObject][$id];
    }

    /**
     * @throws \Exception
     */
    public function getCountry(string $alpha2): ?Country
    {
        return $this->getRegistry(Country::class, $alpha2);
    }

    /**
     * @throws \Exception
     */
    public function getAdmin1(string $code): ?Admin1
    {
        return $this->getRegistry(Admin1::class, $code);
    }

    /**
     * @throws \Exception
     */
    public function getAdmin2(string $code): ?Admin2
    {
        return $this->getRegistry(Admin2::class, $code);
    }

    public function getManager(): ObjectManager
    {
        return $this->em;
    }
}