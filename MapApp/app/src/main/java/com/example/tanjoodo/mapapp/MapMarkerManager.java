package com.example.tanjoodo.mapapp;

import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.model.Marker;
import com.google.android.gms.maps.model.MarkerOptions;

import java.util.HashMap;

public class MapMarkerManager {
    GoogleMap map;
    HashMap<String, Marker> markers;

    public MapMarkerManager(GoogleMap map) {
        this.map = map;
        this.markers = new HashMap<>();
    }

    private Marker addNewMarker(MapMarker newMarker) {
        return map.addMarker(new MarkerOptions().title(newMarker.status)
                .position(newMarker.location));
    }

    public void addOrUpdateMarkers(MapMarker newMarker) {
        Marker m = markers.get(newMarker.id);
        if (m == null) {
            markers.put(newMarker.id, addNewMarker(newMarker));
        } else {
            m.setTitle(newMarker.status);
            m.setPosition(newMarker.location);
        }
    }

    public void addOrUpdateMarkers(MapMarker [] newMarkers) {
        for (int i = 0; i < newMarkers.length; ++i) {
            Marker m = markers.get(newMarkers[i].id);
            if (m == null) {
                markers.put(newMarkers[i].id, addNewMarker(newMarkers[i]));
            } else {
                m.setTitle(newMarkers[i].status);
                m.setPosition(newMarkers[i].location);
            }
        }
    }

    public void removeMarkers(String id) {
        Marker m = markers.get(id);
        if (m != null) {
            m.remove();
        }
    }

    public void removeMarkers(String[] ids) {
        for (int i = 0; i < ids.length; ++i) {
            Marker m = markers.get(ids[i]);
            if (m != null) {
                m.remove();
            }
        }
    }
}
