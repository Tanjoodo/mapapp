package com.example.tanjoodo.mapapp;

import com.google.android.gms.maps.model.LatLng;

public class MapMarker {
    public String id;
    public String status;
    public LatLng location;

    public MapMarker(String id, String status, LatLng location) {
        this.id = id;
        this.status = status;
        this.location = location;
    }
}
