<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfluxDB\Database;
use InfluxDB\Point;

class HumidityController extends Controller
{
    protected $database;

    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Get all sensors.
     *
     * @param Database $database
     *
     * @return \Illuminate\Support\Collection
     */
    public function index($range)
    {
        $sensors = $this->{"humiditiesForA".ucfirst($range)}();

        return collect($sensors)->map(function ($sensor) {
            return [
                'sensor' => $sensor['tags']['sensor'],
                'data' => collect($sensor['values'])->map(function ($value) {
                    $value[1] = round($value[1], 1);

                    return $value;
                }),
            ];
        });
    }

    protected function humiditiesForAHour()
    {
        $fromTime = Carbon::now()->subHour();
        $resolution = '2m';

        return $this->humidities($fromTime, $resolution);
    }

    protected function humiditiesForADay()
    {
        $fromTime = Carbon::now()->subHours(25);
        $resolution = '40m';

        return $this->humidities($fromTime, $resolution);
    }

    protected function humiditiesForAWeek()
    {
        $fromTime = Carbon::now()->subDays(8);
        $resolution = '5h';

        return $this->humidities($fromTime, $resolution);
    }

    protected function humiditiesForAMonth()
    {
        $fromTime = Carbon::now()->subDays(31);
        $resolution = '1d';

        return $this->humidities($fromTime, $resolution);
    }

    protected function humiditiesForAYear()
    {
        $fromTime = Carbon::now()->subDays(365);
        $resolution = '1w';

        return $this->humidities($fromTime, $resolution);
    }

    protected function humidities($fromTime, $resolution)
    {
        $fromTime = $fromTime->timestamp * 1000000000;

        $query = $this->database->getQueryBuilder()
            ->select('*')
            ->from('humidity')
            ->groupBy('sensor')
            ->groupBy("time($resolution)")
            ->mean('humidity')
            ->where([
                "time > $fromTime",
                'humidity > 1',
                'humidity < 100'
            ])
            ->getQuery();

        return $this->database->query("$query fill(none)")
            ->getSeries();
    }

    /**
     * Add a new Humidity Measurement.
     *
     * @param Request $request
     * @param Database $database
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'value' => 'required',
            'device' => 'required',
        ]);

        if ($request->value === 0) {
            return response('', 201);
        }

        $points = [
            new Point(
                'humidity',
                null,
                ['sensor' => $request->device],
                ['humidity' => round((float) $request->value, 1)]
            )
        ];

        $this->database->writePoints($points);

        return response('', 201);
    }
}
