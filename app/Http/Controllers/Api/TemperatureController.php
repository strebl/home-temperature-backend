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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Database $database)
    {
        $fromTime = Carbon::now()->subHours(3)->timestamp * 1000000000;

        $points = $database->getQueryBuilder()
            ->select('*')
            ->from('temperature')
            ->groupBy('sensor')
            ->groupBy('time(5m)')
            ->mean('temperature')
            ->where(["time > $fromTime"])
            ->getResultSet()
            ->getSeries();

        dd($points);
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
