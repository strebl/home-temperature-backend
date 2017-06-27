<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfluxDB\Database;
use InfluxDB\Point;

class TemperatureController extends Controller
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
        $sensors = $this->{"temperaturesForA".ucfirst($range)}();

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

    protected function temperaturesForADay()
    {
        $fromTime = Carbon::now()->subHours(25);
        $resolution = '40m';
        
        return $this->temperatures($fromTime, $resolution);
    }

    protected function temperaturesForAWeek()
    {
        $fromTime = Carbon::now()->subDays(8);
        $resolution = '5h';
        
        return $this->temperatures($fromTime, $resolution);
    }

    protected function temperaturesForAMonth()
    {
        $fromTime = Carbon::now()->subDays(31);
        $resolution = '1d';
        
        return $this->temperatures($fromTime, $resolution);
    }

    protected function temperaturesForAYear()
    {
        $fromTime = Carbon::now()->subDays(365);
        $resolution = '1w';
        
        return $this->temperatures($fromTime, $resolution);
    }

    protected function temperatures($fromTime, $resolution)
    {
        $fromTime = $fromTime->timestamp * 1000000000;

        $query = $this->database->getQueryBuilder()
            ->select('*')
            ->from('temperature')
            ->groupBy('sensor')
            ->groupBy("time($resolution)")
            ->mean('temperature')
            ->where([
                "time > $fromTime",
                'temperature > 1',
                'temperature < 100'
            ])
            ->getQuery();

        return $this->database->query("$query fill(none)")
            ->getSeries();
    }

    /**
     * Add a new Temperature Measurement.
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
                'temperature',
                null,
                ['sensor' => $request->device],
                ['temperature' => (float) $request->value]
            )
        ];

        $this->database->writePoints($points);

        return response('', 201);
    }
}
