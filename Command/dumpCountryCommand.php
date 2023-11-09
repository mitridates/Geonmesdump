<?php
namespace App\Geonamesdump\Command;
use App\Geonamesdump\Model\LoaderInteface;
use App\Geonamesdump\Util\FileHelper;
use App\Geonamesdump\Util\RepositoryHelper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Question\ConfirmationQuestion,
    Symfony\Component\Console\Question\Question,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Helper\QuestionHelper,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Intl\Countries;


class dumpCountryCommand extends Command
{
    private OutputInterface $output;

    private InputInterface $input;

    private QuestionHelper $helper;

    private array $parameters;

    public function __construct(ParameterBagInterface $bag, private readonly ManagerRegistry $doctrine) {
        parent::__construct();
        $this->parameters = $bag->get('geonames_dump');
    }

    protected function configure()
    {
        $this
            ->setName("geonamesdump:country")
            ->addArgument('country',InputArgument::OPTIONAL, 'Country name')
            ->addOption(
                'deep',
                'd',
                InputOption::VALUE_REQUIRED,
                'How many deep in administrative divisions (1-3): admin1, admin2, admin3',
                3
            )
            ->addOption(
                'test',
                't',
                InputOption::VALUE_NONE,
                'Test mode. Set flush option to false.'
            )
            ->setDescription('load country and administrative divisions admin1, admin2 and admin3')
            ->setHelp('Use geonames:dump for custom parameters.yml and/or single loader.');
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int|null
    {
        $this->output = $output;
        $this->input = $input;
        $country = strtoupper((string) $input->getArgument('country'));
        $d = (int)$input->getOption('deep');
        $this->parameters['config']['flush']= !$input->getOption('test');
        $this->helper = $this->getHelper('question');
        $loaders = array_slice(['country', 'admin1', 'admin2', 'admin3'], 0,$d+1);

        $this->output->writeln(sprintf('<info>Dump to DB administrative divisions with deep level %s: %s</info>', min($d, 3), implode(" + ", $loaders)));

        if ($country == null) {
            $country = array_unique(array_merge($this->askForCountry(), (empty($country))? [] : (array)$country));
        } else {

            if (in_array($country, Countries::getCountryCodes())) {
                $country = (array)$country;
            } else {
                $this->output->writeln(sprintf('<error>Unknown country: %s</error>', $country));
                $country = $this->askForCountry();
            }
        }

        if(!count($country)){
            $this->output->writeln(sprintf("No country selected. Bye!"));
            return 0;
        }else{
            $question = new ConfirmationQuestion(sprintf('Confirm to load country [%s] until lower administrative division "%s" [y/n]: ', implode(', ', $country), end($loaders)));
            if(!$this->helper->ask($this->input, $this->output, $question)){
                return 0;
            }
        }

        $this->load($country, $loaders);
        return 1;

    }

    /**
     * Ask for countries.
     *
     * @return array
     */
    private function askForCountry(): array
    {
        $l=[];
        $availableCountries= Countries::getCountryCodes();

        do{
            if(count($l)){
                $this->output->writeln(sprintf('Press <info><Enter></info> to run. Selected countries: <info>%s</info>',implode(', ', $l)));
            }

            $reply = strtoupper((string) $this->helper->ask($this->input, $this->output, new Question('Add country: ')));

            if($reply==''){
                return $l;
            }elseif(!preg_match("/^[A-Z0-9]+$/i",$reply) || empty($reply)){
                continue;
            }

            if(in_array($reply, ['QUIT', 'EXIT'])){
                return [];
            }elseif(in_array($reply,$availableCountries)){
                array_push($l, $reply);
                $availableCountries= array_diff($availableCountries,$l);
            }else{
                if(in_array($reply, $l)){
                    $this->output->writeln(sprintf('<info>Already selected: %s</info>',$reply));
                }else{
                    $this->output->writeln(sprintf('<error>Not available: %s</error>',$reply));
                }

            }

        }while(count($availableCountries));

        return $l;
    }

    /**
     * Run loaders
     *
     * @param array $countries countries
     * @return bool
     * @throws \Exception
     */
    private function load(array  $countries, array $loaders)
    {
        $this->parameters['selected_countries']= $countries;
        $this->parameters['dump']=[
            'country' => $countries,
            'admin1' => $countries,
            'admin2' => $countries,
            'admin3' => $countries
        ];
        $config = $this->parameters['config'];

        $repositoryHelper = new RepositoryHelper($this->doctrine->getManager());
        $fileHelper = new FileHelper($config['webdir'], $config['localdir'], $config['tmpdir']);
        $loaderNamespace = 'App\Geonamesdump\Loader\\';

        foreach($loaders as $key => $loader) {

            if($key==0) $fileHelper->createTempDir();
            $class = $loaderNamespace.ucfirst((string) $loader).'Loader';
            /**
             * @var LoaderInteface $loader
             */
            $loader = new $class($fileHelper, $repositoryHelper, $this->parameters);
            $loader->load();

            if($key == count($loaders)-1 && $this->parameters['config']['rmdir']) $fileHelper->deleteTemporaryDir();
        }
        return true;

    }

}
