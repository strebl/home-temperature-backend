<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use InfluxDB\Database;
use InfluxDB\Point;

class TemperatureController extends Controller
{
    /**
     * Get all sensors.
     *
     * @param Database $database
     *
     * @return \Illuminate\Support\Collection
     */
    public function index(Database $database)
    {
        $fromTime = Carbon::now()->subHours(25)->timestamp * 1000000000;

        $query = $database->getQueryBuilder()
            ->select('*')
            ->from('temperature')
            ->groupBy('sensor')
            ->groupBy('time(40m)')
            ->mean('temperature')
            ->where(["time > $fromTime"])
            ->getQuery();

        $sensors = $database->query("$query fill(none)")
            ->getSeries();

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

    /**
     * Add a new Temperature Measurement.
     *
     * @param Request $request
     * @param Database $database
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request, Database $database)
    {
        $this->validate($request, [
            'value' => 'required',
            'device' => 'required',
        ]);

        $points = [
            new Point(
                'temperature',
                null,
                ['sensor' => $request->device],
                ['temperature' => (float) $request->value]
            )
        ];

        $database->writePoints($points);

        return response('', 201);
    }
}
