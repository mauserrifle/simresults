# Simresults

Simresults is an open-source, object-oriented library built using PHP5. It
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

This project is created and maintained by
[Maurice van der Star](http://twitter.com/mauserrifleNL)

* Website: <http://simresults.net>
* Twitter: [@mauserrifleNL](http://twitter.com/mauserrifleNL)

## Supported games

### rFactor

Both rFactor and rFactor 2 are supported, or anything based on these engines.

#### rFactor powered games

Because the rFactor engine is used within other games, those below are
confirmed to be working too:

* Game Stock Car 2012
* Game Stock Car 2013
* Formula Truck 2013

Please note that anything other than rFactor or rFactor 2 is reported as a
rFactor game.

### Assetto Corsa

Very limited support due to BETA state. Only laps and drifting points are read.


## Features

* Can read out a full session consisting of the following information: Game,
  Server, Settings, Track, Participants/Drivers including swaps, Vehicle,
  Compound choice, Chats, Laps/Sectors, Fuel usage, Penalties and Incidents
* Offers extra methods to get specific data, e.g. `getBestLap()` and
  `getBestLapBySector(<int>)`
* Offers a Helper class to sort laps by time and format times to human readable
  format (`h:i:s.u`)
* Caches heavy methods like `getLapsSortedBySector(<int>)`. This is very useful
  with endurance results that have 200+ laps
* The API is unittested

### rFactor reader

* Supports rFactor and rFactor 2. Also works for other rFactor powered games:
  Game Stock Car 2012, Game Stock Car 2013 and Formula Truck 2013
* Detects and fixes position data (sometimes log files report complete wrong
  positions due to lag/bugs)
* Detects human and AI players using their aids (sometimes log files report
  wrong player state)

## Example

    // Get a reader using a source file
    $reader = \Simresults\Data_Reader::factory('qualify.xml');

    // Get the session
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
    $best_lap_gap = \Simresults\Helper::formatTime(
        $session_best_lap->getGap($best_lap));

## Installation

Simresults can be installed and autoloaded using
[composer](https://packagist.org). But ofcourse it will work with any
[PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)
autoloader.

## Requirements

- PHP 5.3

## Bugs

Have a bug or a feature request?
[Please open a new issue](https://github.com/mauserrifle/simresults/issues).

## Contributing

The project is designed to be extended with new features and game support.
Feel free to [fork Simresults on GitHub](https://github.com/mauserrifle/simresults)
and submit a pull request!

The project is fully unittested using PHPUnit. If you offer any changes, make
sure all your new additions are tested.

## Running simresults tests

To run the tests, use the following command:

    ./vendor/bin/phpunit tests

## License

The Simresults library is open-sourced software licensed under the
[ISC license](http://opensource.org/licenses/ISC).