Geonamesdump
=====================

Console Symfony bundle. Utility for Geonames to load in database Country > Admin1 > Admin2 > Amin3

With some configuration can load to DB admin3 codes, or the geonames table (without associations).

The administrative divisions must be loaded in order, from top to down, to avoid dependency errors.

The simplest dump command will load country + admin1 + admin2 + admin3 

        geonamesdump:country [country-code]

## Install

### Get the Bundle

    git clone https://github.com/mitridates/Geonamesdump.git.
    
### Add to config/bundles.php
    ...
    App\Geonames\Geonamesdump::class =>  ['dev' => true];

## Configure

Use some global parameters for loaders or default options 

    parameters:
        config:
            ...
See [Resources/config/parameters.yml](./Resources/config/parameters.yml).
    
## Countries loader command

    geonamesdump:country [optional country-code] [-d deep] [-t test]

ARGUMENT
- [country-code]: If not defined the loader will ask for countries to load
 
OPTIONS
- -t=TEST: Simulate load. Will not flush to database.
- -d=DEEP: How deep to load (0-3): country, admin1, admin2, admin3

## Config loader command

    geonamesdump:config [optional administrative-division] [-d deep] [-t test]

Will look up in parameters.geonames_dump.dump the config for loaders
See dump examples in  [Resources/config/sample.yml](./Resources/config/sample.yml)
 ./sample.yml

ARGUMENT
- [administrative-division]: If not defined the loader will ask for administrative division to load

OPTIONS
- -t=TEST: Simulate load. Will not flush to database.

## Require

[Geonames](https://github.com/mitridates/Geonames)

## See

[http://download.geonames.org/export/dump/](http://download.geonames.org/export/dump/)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.