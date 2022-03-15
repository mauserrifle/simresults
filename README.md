# Simresults

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg?style=flat-square)](https://php.net/)
[![CI Status](https://github.com/mauserrifle/simresults/actions/workflows/ci.yml/badge.svg)](https://github.com/mauserrifle/simresults/actions)
[![Total Downloads](https://img.shields.io/packagist/dt/mauserrifle/simresults)](https://packagist.org/packages/mauserrifle/simresults)
[![License](https://img.shields.io/packagist/l/mauserrifle/simresults)](https://raw.githubusercontent.com/mauserrifle/simresults/develop/LICENSE)

Simresults is an open-source, object-oriented library built using PHP. It
allows you to read out log files of a race game and transforms them to a simple
data model to easily read out this data.

This project is the core of the website [simresults.net](http://simresults.net)
, which allows you to upload your race log files and transform them to a
readable format. The uploaded results become saved and public, so visitors can
share them with their fellow racers. An example result can be found at
<http://simresults.net/130529-X2g>.

The website is a service but also a demonstration of what is possible using
this library. This library does not supply any HTML templates or whatsoever.
Any 'implementation' is up to you within your own project and is not limited
to any framework.

This project is created (2013) and maintained by
[Maurice van der Star](https://github.com/sponsors/mauserrifle)

Please consider [donating](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=L9L982XNK7DAA&lc=US&item_name=Simresults&item_number=Simresults&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted)
to cover future development.

It's also possible to use the Simresults website remote results [API](https://simresults.net/api). This requires much less work and programming knowledge.

* Website: [simresults.net](https://simresults.net)
* Twitter (inactive): [@simresults](http://twitter.com/simresults) and [@mauserrifleNL](http://twitter.com/mauserrifleNL)
* Email: <mauserrifle@gmail.com>
* Donate: [Paypal](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=L9L982XNK7DAA&lc=US&item_name=Simresults&item_number=Simresults&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted)
* Sponsor: [GitHub](https://github.com/sponsors/mauserrifle) (please email me for alternatives)


## Supported games

Simresults supports a wide range of games:

* rFactor
* rFactor 2
* Project Cars
* Project Cars 2
* Assetto Corsa
* Assetto Corsa Competizione
* Automobilista
* Automobilista 2
* Game Stock Car 2012
* Game Stock Car 2013
* Game Stock Car Extreme
* Formula Truck 2013
* RACE
* RACE 07
* GTR
* GTR2
* GT Legends
* RaceRoom Racing Experience
* BMW M3 Challenge
* F1 challenge 99-02

The following expansions of RACE (07) should work too:

* Race: Caterham expansion
* GTR Evolution
* Crowne Plaza
* STCC - The Game
* STCC 2 - The Game
* RACE On
* Raceroom - The Game
* Raceroom - The Game 2
* Formula Raceroom
* GT Power
* WTCC 2010
* Retro
* Race Injection
* Volvo - The Game


Please note that Formula Truck will be reported as as a rFactor game.

Results from F1 challenge and GTR might miss laps.

Results from Assetto Corsa (race_out.json) and Raceroom might only contain 1
lap.


## Features

* Can read out a full session consisting of the following information: Game,
  Server, Settings, Track, Participants/Drivers including swaps, Vehicle,
  Compound choice, Chats, Laps/Sectors, Fuel usage, Pit info, Penalties and
  Incidents
* Offers extra methods to get specific data, e.g. `getBestLap()` and
  `getBestLapBySector(<int>)`
* Offers a Helper class to sort laps by time and format times to human readable
  format (`h:i:s.u`)
* Caches heavy methods like `getLapsSortedBySector(<int>)`. This is very useful
  with endurance results that have 200+ laps
* The API is unittested

### rFactor reader

`lib/Data/Reader/Rfactor2.php`

* Supports rFactor and rFactor 2. Also works for other rFactor powered games:
  Automobilista, Game Stock Car 2012, Game Stock Car 2013 and Formula Truck 2013
* Detects and fixes position data (sometimes log files report complete wrong
  positions due to lag/bugs)
* Detects human and AI players using their aids (sometimes log files report
  wrong player state)

### Project Cars Server reader

`lib/Data/Reader/ProjectCarsServer.php`

### Assetto Corsa reader

`lib/Data/Reader/AssettoCorsa.php`

* Limited data. Use server logs if possible.

### Assetto Corsa Server reader

`lib/Data/Reader/AssettoCorsaServer.php`

### Assetto Corsa Server reader JSON

`lib/Data/Reader/AssettoCorsaServerJson.php`

### Assetto Corsa Competizione

`lib/Data/Reader/AssettoCorsaCompetizione.php`

### RaceRoom Server reader

`lib/Data/Reader/RaceRoomServer.php`

### RACE 07 reader

`lib/Data/Reader/Race07.php`

* Also works for the following games: RACE, GTR, GTR2, GT Legends,
  BMW M3 Challenge, F1 challenge 99-02 and all expansions of these games
* Checks and fixes log variations like non-zero based laps and missing lap data

### iRacing reader

`lib/Data/Reader/Iracing.php`

* Limited data. Only contains the best lap of drivers.

## Requirements

- PHP >= 7.3
- Composer (for easy installing and autoloading)

## Installation and example

Simresults can be installed and autoloaded using
[composer](https://packagist.org). But ofcourse it will work with any
[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
autoloader.

### Example for Linux/OSX

[Install composer](https://getcomposer.org/download/).

Add the Simresults package to the `composer.json` file:

    composer require mauserrifle/simresults:dev-develop

Create index.php:

    <?php

    // Load code
    require(realpath('vendor/autoload.php'));

    // Path to the result source file
    $file = realpath(dirname(__FILE__)
            .'/vendor/mauserrifle/simresults/tests/logs/rfactor2'
            .DIRECTORY_SEPARATOR.'race.xml');

    // Get a reader using the source file
    $reader = \Simresults\Data_Reader::factory($file);

    // Get the first session. Note: Use `getSessions()` to get all sessions
    $session = $reader->getSession();

    // Get participants sorted by their position
    $participants = $session->getParticipants();

    // Get the driver name of the second participant
    $driver_name = $participants[1]->getDriver()->getName();

    // Get the best lap of the second participant
    $best_lap = $participants[1]->getBestLap();

    // Get the best lap of the session
    $session_best_lap = $session->getBestLap();

    // Format the gap between the two laps
    $helper = new \Simresults\Helper;
    $best_lap_gap = $helper->formatTime($session_best_lap->getGap($best_lap));
    echo $best_lap_gap;

 Run server

     php -S localhost:8000


Open <http://localhost:8000> and all should work!

For further usage please read the classes code within the `lib` folder. They
are carefully documented.

## Sponsors

Big thanks to the following sponsors for supporting Simresults!
Please pay them a visit :)

* [Coach Dave - David Perel](https://www.davidperel.net/coaching/?ref=simresults)

## Bugs

Have a bug or a feature request?
[Please open a new issue](https://github.com/mauserrifle/simresults/issues).

## Known issues

### Caching

Some classes like `Participant` do heavy caching. So changing any value after
calling sorting methods will be pointless. There are no cache invalidate
methods (yet). Most likely they will never be needed as there's no use case you
actually would want to change values after reading out all data.

When writing a reader. NEVER call methods like `getVehicle()` on Participant
(which uses cached methods). Re-use your own created objects (like `$vehicle`)
within the reading to prevent any early invalid cache. Do this for all type of
objects.

### Laps missing

Logs tend to miss lap data on all games. Check the logs.

### Marked as RACE session while it's not

The Race 07 reader detects qualify if all drivers are DNF. There's no session
type in these log files. This detection might be false in some cases.

### Date is not right of session

GTR, GTL, F1 challenge don't have a timestamp or timezone information. So
dates are created using the default timezone.

## Contributing

The project is designed to be extended with new features and game support.
Feel free to [fork Simresults on GitHub](https://github.com/mauserrifle/simresults)
and submit a pull request!

The project is tested using PHPUnit and phpspec. If you offer any changes,
make sure all your new additions are tested.

## Running simresults tests

To run the tests, use the following command:

    ./vendor/bin/phpunit
    ./vendor/bin/phpspec run

## License

The Simresults library is open-sourced software licensed under the
[ISC license](https://raw.githubusercontent.com/mauserrifle/simresults/develop/LICENSE).