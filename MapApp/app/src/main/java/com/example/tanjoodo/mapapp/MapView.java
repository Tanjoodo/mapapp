package com.example.tanjoodo.mapapp;

import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.AsyncTask;
import android.os.Bundle;
import android.os.Handler;
import android.provider.Settings;
import android.support.annotation.NonNull;
import android.support.design.widget.FloatingActionButton;
import android.support.v4.app.ActivityCompat;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.ActionBar;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.support.v7.view.SupportActionModeWrapper;
import android.support.v7.widget.Toolbar;
import android.util.Log;
import android.view.View;
import android.view.Menu;
import android.view.MenuItem;
import android.widget.EditText;

import com.google.android.gms.maps.CameraUpdateFactory;
import com.google.android.gms.maps.GoogleMap;
import com.google.android.gms.maps.OnMapReadyCallback;
import com.google.android.gms.maps.SupportMapFragment;
import com.google.android.gms.maps.model.BitmapDescriptorFactory;
import com.google.android.gms.maps.model.LatLng;
import com.google.android.gms.maps.model.Marker;
import com.google.android.gms.maps.model.MarkerOptions;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.io.PrintWriter;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;

public class MapView extends AppCompatActivity implements OnMapReadyCallback, GoogleMap.OnMarkerClickListener {
    GoogleMap map = null;
    Marker myMarker = null;
    MapMarker myMapMarker = null;
    MapMarkerManager myManager = null;

    static String serverHostname = "192.168.1.115";
    static String filePrefix = "~tanjoodo/mapapp_0.2/";

    File tokenFile = null;
    String access_token = "";

    String device_id = null;

    public static int PICK_GROUP_FILTER_REQUEST = 1;
    final MapView c = this;
    class GrabTokenTask extends AsyncTask<String, Void, String> {
        public AsyncResponse<Void> delegate = null;
        boolean error = false;

        public GrabTokenTask(AsyncResponse<Void> delegate) {
            this.delegate = delegate;
        }
        @Override
        public String doInBackground(String... params) {
            String request = filePrefix + "request_token.php/"+params[0]+"/"+params[1]+"/"+params[2]+"/"+params[3];
            ServerRequest sr = new ServerRequest(serverHostname, request);
            sr.makeRequest();
            error = sr.error;
            return sr.response;
       }

        @Override
        public void onPostExecute(String result) {
            String message = "";
            try {
                JSONObject jObj = new JSONObject(result);
                if (error) {
                    message = jObj.getString("error");
                    access_token = "";
                } else {
                    message = jObj.getString("status");
                    access_token = jObj.getString("access_token");

                    setSavedToken(tokenFile, access_token);

                }
            } catch (org.json.JSONException e) {
                error = true;
                Log.e("MapApp", e.getMessage());
                Log.e("MapApp", result);
            }

            if (error) {
                AlertDialog.Builder ab = new AlertDialog.Builder(c);
                ab.setMessage(message);
                ab.show();
            } else {
                myMapMarker.status = message;
                if (myMarker != null) {
                    myMarker.setTitle(myMapMarker.status);
                }
            }

            delegate.processFinish(null);
        }
    }

    class DownloadLocationsTask extends AsyncTask<Void, Void, MapMarker[][]> {
        @Override
        public MapMarker[][] doInBackground(Void... params) {
            String request = filePrefix + "download_public_loc.php/" + access_token;
            Log.d("MapApp", "Downloading public loc");
            ServerRequest sr = new ServerRequest(serverHostname, request);
            sr.makeRequest();
            MapMarker result[][] = new MapMarker[2][];
            if (!sr.error) {
                String response = sr.response;
                try {
                    JSONObject responseObj = new JSONObject(response);
                    JSONArray nearUsers = responseObj.getJSONArray("NearUsers");
                    JSONArray deleteUsers = responseObj.getJSONArray("DeleteUsers");

                    result[0] = new MapMarker[nearUsers.length()];
                    for (int i = 0; i < nearUsers.length(); i++) {
                        JSONObject user = nearUsers.getJSONObject(i);
                        String id = user.getString("id");
                        float lat = (float) user.getDouble("lat");
                        float lng = (float) user.getDouble("lng");
                        String status = user.getString("status");
                        Log.d("MapApp", status);
                        result[0][i] = new MapMarker(id, status, new LatLng(lat, lng));
                    }

                    result[1] = new MapMarker[deleteUsers.length()];
                    for (int i = 0; i < deleteUsers.length(); i++) {
                        String id = deleteUsers.getString(i);
                        result[1][i] = new MapMarker(id, "deleted", new LatLng(0, 0));
                    }

                } catch (JSONException e) {
                    Log.e("MapApp", e.getMessage());
                    Log.e("MapApp", response);
                    return null;
                }
            } else {
                Log.d("MapApp", "error");
                try {
                    JSONObject json = new JSONObject(sr.response);
                    AlertDialog.Builder adb = AppUtils.createSimpleDialog("Error getting data", json.getString("error"), c);
                    Log.d("MapApp", json.getString("error"));
                    adb.show();
                } catch (JSONException e) {
                    Log.d("MapApp", e.getMessage());
                    Log.d("MapApp", sr.response);
                }
            }

            return result;
        }

        @Override
        public void onPostExecute(MapMarker[][] markers) {
            if (markers != null) {
                if (markers[0] != null) {
                    myManager.addOrUpdateMarkers(markers[0]);
                }

                if (markers[1] != null) {
                    for (int i = 0; i < markers[1].length; ++i) {
                        myManager.removeMarkers(markers[1][i].id);
                    }
                }
            }
        }

    }

    class UpdateInfoTask extends AsyncTask<MapMarker, Void, Boolean> {
        @Override
        protected Boolean doInBackground(MapMarker... params) {
            MapMarker m = params[0];
            String status = "";
            try {
                status = URLEncoder.encode(m.status, "UTF-8");
            } catch (UnsupportedEncodingException e) {
                Log.e("MapApp", e.getMessage() + " in UpdateInfoTask");
            }
            String request = filePrefix + "update_pos.php/" + access_token + "/"
                    + m.location.latitude + "/" + m.location.longitude + "/" + status;

            ServerRequest sr = new ServerRequest(serverHostname, request);
            sr.makeRequest();
            return sr.error;
        }

        @Override
        protected void onPostExecute(Boolean error) {
            if (error) {
                Log.d("MapApp", "Error while updating");
            } else {
                setOrChangeMyMarker();
            }
        }
    }

    final int USER_POLL_RATE = 5000; // how often to poll in milliseconds
    boolean running = true;
    Handler pollUsersHandler = new Handler();
    Runnable pollUsersRunnable = new Runnable() {

        @Override
        public void run() {
            new DownloadLocationsTask().execute();
            if (running) {
                pollUsersHandler.postDelayed(pollUsersRunnable, USER_POLL_RATE);
            }
        }
    };

    public static String getSavedToken(File tokenFile) {
        String access_token = "";
        try {
            if (tokenFile.createNewFile()) {
                Log.e("MapApp", "File did not already exist");
            }
            access_token = new BufferedReader(new FileReader(tokenFile)).readLine();
        } catch (FileNotFoundException e) {
            Log.e("MapApp", "File not found even if it was created");
        } catch (IOException e) {
            Log.e("MapApp", "Could not create token file");
        }

        return access_token;
    }

    public static void setSavedToken(File tokenFile, String token){
        try {
            PrintWriter pw = new PrintWriter(tokenFile);
            pw.println(token);
            pw.flush();
            pw.close();
        } catch (FileNotFoundException e) {
            Log.e("MapApp", e.getMessage());
        }
    }
    private void initializeApp() {
        device_id = Settings.Secure.getString(getContentResolver(), Settings.Secure.ANDROID_ID);

        tokenFile = new File(this.getFilesDir(), "token.txt");
        access_token = getSavedToken(tokenFile);

        Log.d("MapApp", "Access token: "+access_token);

        setUpLocationServices();

        //Log.d("MapApp", access_token);
        /*new GrabTokenTask(new AsyncResponse<Void>() {
            @Override
            public void processFinish(Void v) {
                pollUsersHandler.post(pollUsersRunnable);
            }
        }).execute(device_id, access_token,
                ""+myMapMarker.location.latitude, ""+myMapMarker.location.longitude);*/

        Log.d("MapApp", "Started polling service");
        pollUsersHandler.post(pollUsersRunnable);

    }

    private void setUpLocationServices() {
        LocationManager lm = (LocationManager) this.getSystemService(Context.LOCATION_SERVICE);
        LocationListener ll = new LocationListener() {
            @Override
            public void onLocationChanged(Location location) {
                Log.d("MapApp", "Location changed");
                myMapMarker.location = new LatLng(location.getLatitude(), location.getLongitude());
                new UpdateInfoTask().execute(myMapMarker);
                setOrChangeMyMarker();
            }

            public void onStatusChanged(String provider, int status, Bundle extras) {
                Log.d("MapApp", "Location changed");
            }

            public void onProviderEnabled(String provider) {
            }

            public void onProviderDisabled(String provider) {
            }
        };

        Location lastKnownLocation;
        if (ContextCompat.checkSelfPermission(this, android.Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_DENIED) {
            if (ActivityCompat.shouldShowRequestPermissionRationale(this, android.Manifest.permission.ACCESS_FINE_LOCATION)) {
                AlertDialog.Builder adb = AppUtils.createSimpleDialog("We need your location!",
                        "This application needs to see your location to function",
                        this);

                adb.setPositiveButton("OK", new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialogInterface, int i) {
                        dialogInterface.cancel();
                    }
                });
                adb.show();
            }

            ActivityCompat.requestPermissions(this,
                    new String[] {android.Manifest.permission.ACCESS_FINE_LOCATION}, 0);
        }

        lm.requestLocationUpdates(LocationManager.NETWORK_PROVIDER, 0, 0, ll);
        lastKnownLocation = lm.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
        if (lastKnownLocation == null) {
            lastKnownLocation = new Location(LocationManager.NETWORK_PROVIDER);
            lastKnownLocation.setLatitude(0);
            lastKnownLocation.setLongitude(0);
        }

        myMapMarker = new MapMarker(device_id, "",
                new LatLng(lastKnownLocation.getLatitude(), lastKnownLocation.getLongitude()));
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_map_view);
        Toolbar toolbar = (Toolbar) findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        SupportMapFragment mapFragment = (SupportMapFragment) getSupportFragmentManager()
                .findFragmentById(R.id.map_fragment);
        mapFragment.getMapAsync(this);

        FloatingActionButton jump_to_location_fab = (FloatingActionButton) findViewById(R.id.fab);
        jump_to_location_fab.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                if (map != null) {
                    map.animateCamera(CameraUpdateFactory.newLatLngZoom(myMapMarker.location, 10));
                }
            }
        });

        FloatingActionButton new_status_fab = (FloatingActionButton) findViewById(R.id.fab2);
        new_status_fab.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                updateStatus();
            }
        });
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String permissions[], @NonNull int[] grantResults) {
        switch(requestCode) {
            case 0:
                if (grantResults.length == 0 || grantResults[0] == PackageManager.PERMISSION_DENIED) {
                    AlertDialog.Builder adb = AppUtils.createSimpleDialog("We need your location!",
                            "You cannot use this application without granting access to your location.",
                            this);
                    adb.setPositiveButton("OK", new DialogInterface.OnClickListener() {
                        @Override
                        public void onClick(DialogInterface dialogInterface, int i) {
                            dialogInterface.cancel();
                            finish();
                        }
                    });
                    adb.show();
                }
        }
    }
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        // Inflate the menu; this adds items to the action bar if it is present.
        getMenuInflater().inflate(R.menu.menu_map_view, menu);
        return true;
    }

    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        // Handle action bar item clicks here. The action bar will
        // automatically handle clicks on the Home/Up button, so long
        // as you specify a parent activity in AndroidManifest.xml.
        int id = item.getItemId();

        //noinspection SimplifiableIfStatement
        if (id == R.id.action_update_status) {
            updateStatus();
            return true;
        } else if (id == R.id.action_settings) {
            startActivity(new Intent(this, SettingsActivity.class));
            return true;
        } else if (id == R.id.action_group_list) {
            startActivityForResult(new Intent(this, GroupListView.class), PICK_GROUP_FILTER_REQUEST);
            return true;
        } else if (id == R.id.action_reset_filter) {
            ActionBar ab = getSupportActionBar();
            if (ab != null) {
                getSupportActionBar().setTitle("MapApp");
            }
            return true;
        } else if (id == R.id.action_sign_out) {
            class LogOutTask extends AsyncTask<Void, Void, Void> {
                @Override
                protected Void doInBackground(Void... params) {
                    ServerRequest sr = new ServerRequest(serverHostname,
                            filePrefix+"logout.php/"+access_token);
                    sr.makeRequest();
                    return null;
                }
            }
            new LogOutTask().execute();
            running = false;
            if (!tokenFile.delete()){
                Log.d("MapApp", "Couldn't delete token file");
            }
            Intent go_to_sign_in = new Intent(this, Signin.class);
            startActivityForResult(go_to_sign_in, Signin.SIGN_IN_REQUEST);
        }

        return super.onOptionsItemSelected(item);
    }

    @Override
    public void onMapReady(GoogleMap googleMap) {
        this.map = googleMap;
        myManager = new MapMarkerManager(googleMap);
        initializeApp();
        setOrChangeMyMarker();
        map.setOnMarkerClickListener(this);
    }

    void updateStatus() {
        AlertDialog.Builder alert_builder = new AlertDialog.Builder(this);
        alert_builder.setTitle(R.string.action_update_status);
        final EditText input = new EditText(this);
        input.setTextColor(ContextCompat.getColor(this, R.color.black));
        input.setHint("New status...");
        alert_builder.setView(input);

        alert_builder.setPositiveButton("OK", new DialogInterface.OnClickListener() {
            @Override
            public void onClick(DialogInterface dialog, int which) {
                myMapMarker.status = input.getText().toString();
                new UpdateInfoTask().execute(myMapMarker);
            }
        });

        alert_builder.setNegativeButton("Cancel", new DialogInterface.OnClickListener() {
            @Override
            public void onClick(DialogInterface dialog, int which) {
                dialog.cancel();
            }
        });

        alert_builder.show();
    }

    void setOrChangeMyMarker() {
        if (myMapMarker != null) {
            if (myMarker == null) {
                myMarker = map.addMarker(new MarkerOptions().position(myMapMarker.location).title(myMapMarker.status)
                        .icon(BitmapDescriptorFactory.defaultMarker(BitmapDescriptorFactory.HUE_AZURE)));
            } else {
                myMarker.setPosition(myMapMarker.location);
                myMarker.setTitle(myMapMarker.status);
            }
        }
    }

    @Override
    public boolean onMarkerClick(final Marker marker) {
        marker.showInfoWindow();
        return true; //Override default behavior of zooming in on the marker and displaying some buttons at the bottom right
    }

    @Override
    public void onDestroy() {
        class LogOutTask extends AsyncTask<String, Void, Void> {
            @Override
            protected Void doInBackground(String... params) {
                ServerRequest sr = new ServerRequest(params[0], params[1]);
                sr.makeRequest();
                return null;
            }
        }
        new LogOutTask().execute(serverHostname, filePrefix + "logout.php/" + access_token);
        running = false; // stop the polling
        super.onDestroy();
    }

    @Override
    public void onActivityResult(int requestCode, int resultCode, Intent data) {
        if  (requestCode == PICK_GROUP_FILTER_REQUEST && resultCode == RESULT_OK) {
            String gid = data.getStringExtra(MyAdapter.EXTRA_GID);
            ActionBar ab = getSupportActionBar();
            if (ab != null) {
                if (gid.equals("1")) {
                    getSupportActionBar().setTitle("MapApp: Family");
                } else {
                    getSupportActionBar().setTitle("MapApp: Friends");
                }
            } else if (requestCode == Signin.SIGN_IN_REQUEST && resultCode == RESULT_CANCELED) {
                finish();
            }
        }
    }

}
