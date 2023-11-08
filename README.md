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


## Sample output
 
```console
    $ bin/console geonamesdump:country ES
    Dump to DB administrative divisions with deep level 3: country + admin1 + admin2 + admin3
    Confirm to load country [ES] until lower administrative division "admin3" [y/n]:
    % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
    Dload  Upload   Total   Spent    Left  Speed
    100 31413  100 31413    0     0   105k      0 --:--:-- --:--:-- --:--:--  106k
    Loading Country file. /var/www/html/clear/var/cache/dev/geonames/countryInfo.txt, (0,03mb))
    302/302 [============================] 100%
    +---------------------------------+--------------------------+--------------------------------+-----------------------+
    | Country global params           | Database Before:0, Now:1 | Lines read: 302 in 0.059 sec.  | File: countryInfo.txt |
    +---------------------------------+--------------------------+--------------------------------+-----------------------+
    | Flush: true | Overwrite.: false | Insert: 1 | Repeated: 0  | Pass filter: 1 && Not in DB: 1 | Size: 0,03 mb         |
    +---------------------------------+--------------------------+--------------------------------+-----------------------+
    % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
    Dload  Upload   Total   Spent    Left  Speed
    100  135k  100  135k    0     0   241k      0 --:--:-- --:--:-- --:--:--  240k
    Loading Admin1 file. /var/www/html/clear/var/cache/dev/geonames/admin1CodesASCII.txt, (0,13mb))
    3880/3880 [============================] 100%
    +---------------------------------+---------------------------+----------------------------------+----------------------------+
    | Admin1 global params            | Database Before:0, Now:19 | Lines read: 3880 in 0.074 sec.   | File: admin1CodesASCII.txt |
    +---------------------------------+---------------------------+----------------------------------+----------------------------+
    | Flush: true | Overwrite.: false | Insert: 19 | Repeated: 0  | Pass filter: 19 && Not in DB: 19 | Size: 0,13 mb              |
    +---------------------------------+---------------------------+----------------------------------+----------------------------+
    % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
    Dload  Upload   Total   Spent    Left  Speed
    100 2197k  100 2197k    0     0  1140k      0  0:00:01  0:00:01 --:--:-- 1140k
    Loading Admin2 file. /var/www/html/clear/var/cache/dev/geonames/admin2Codes.txt, (2,15mb))
    45326/45326 [============================] 100%
    +---------------------------------+---------------------------+----------------------------------+-----------------------+
    | Admin2 global params            | Database Before:0, Now:52 | Lines read: 45326 in 0.244 sec.  | File: admin2Codes.txt |
    +---------------------------------+---------------------------+----------------------------------+-----------------------+
    | Flush: true | Overwrite.: false | Insert: 52 | Repeated: 0  | Pass filter: 52 && Not in DB: 52 | Size: 2,15 mb         |
    +---------------------------------+---------------------------+----------------------------------+-----------------------+
    % Total    % Received % Xferd  Average Speed   Time    Time     Time  Current
    Dload  Upload   Total   Spent    Left  Speed
    100 2928k  100 2928k    0     0  1155k      0  0:00:02  0:00:02 --:--:-- 1155k
    Loading Admin3 file. /var/www/html/clear/var/cache/dev/geonames/ES.admin3Codes.txt, (1,05mb))
    8124/8124 [============================] 100%
    +---------------------------------+-----------------------------+--------------------------------------+--------------------------+
    | Admin3 global params            | Database Before:0, Now:8124 | Lines read: 8124 in 6.329 sec.       | File: ES.admin3Codes.txt |
    +---------------------------------+-----------------------------+--------------------------------------+--------------------------+
    | Flush: true | Overwrite.: false | Insert: 8124 | Repeated: 0  | Pass filter: 8124 && Not in DB: 8124 | Size: 1,05 mb            |
    +---------------------------------+-----------------------------+--------------------------------------+--------------------------+
```


## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.