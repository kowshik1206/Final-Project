<?php

namespace App\Services;

use App\Models\Poi;

class PoiService
{
    /**
     * Get POIs by category
     */
    public function getByCategory($category)
    {
        return Poi::where('category', $category)->get();
    }

    /**
     * Get top-rated POIs
     */
    public function getTopRated($limit = 10)
    {
        return Poi::orderBy('rating', 'desc')->limit($limit)->get();
    }

    /**
     * Search POIs by name or description
     */
    public function search($query)
    {
        return Poi::where('name', 'like', "%$query%")
            ->orWhere('description', 'like', "%$query%")
            ->get();
    }

    /**
     * Get POIs within a radius
     */
    public function getNearby($latitude, $longitude, $radius = 5)
    {
        $pois = Poi::all();

        return $pois->filter(function ($poi) use ($latitude, $longitude, $radius) {
            $distance = $poi->distanceFrom($latitude, $longitude);
            return $distance <= $radius;
        });
    }

    /**
     * Get POIs with high rating
     */
    public function getHighRated($minRating = 4.0)
    {
        return Poi::where('rating', '>=', $minRating)
            ->orderBy('rating', 'desc')
            ->get();
    }
}
