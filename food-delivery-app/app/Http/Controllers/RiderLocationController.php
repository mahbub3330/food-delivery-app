<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\RiderLocation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mockery\Exception;

class RiderLocationController extends Controller
{

    /**
     * Store a collection of Rider Location
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $riderLocation = new RiderLocation();
            $riderLocation->fill($request->all())->save();
            return response()->json(['message' => 'Rider location data stored successfully', 'data' => $riderLocation], 201);
        } catch (Exception $exception) {
            return response()->json($exception->getMessage());
        }

    }


    /**
     * Show a collection of Riders nearest 1km of the restaurant
     * @param Restaurant $restaurant
     * @return JsonResponse
     */
    public function findNearestRider(Restaurant $restaurant): JsonResponse
    {
        try {
            $fiveMinutesAgo = Carbon::now()->subMinutes(5);

            $nearestRider = RiderLocation::where('capture_time', '>=', $fiveMinutesAgo)
                ->selectRaw('*,
                (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians("long") - radians(?)) + sin(radians(?)) * sin(radians(lat)))) as distance',

                    [
                        $restaurant->lat,
                        $restaurant->long,
                        $restaurant->lat,
                    ])
                ->having('distance', '<', 1) //nearest 1km
                ->orderBy('distance')
                ->get();

            if (!$nearestRider) {
                return response()->json(['message' => 'No nearby riders found in the last 5 minutes'], 404);
            }

            return response()->json(['nearest_rider' => $nearestRider]);
        } catch (\Exception $exception) {
            return response()->json($exception->getMessage(), $exception->getCode());
        }


    }

}
