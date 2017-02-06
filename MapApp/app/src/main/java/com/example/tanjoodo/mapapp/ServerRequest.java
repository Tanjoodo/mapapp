package com.example.tanjoodo.mapapp;

import android.util.Log;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;

public class ServerRequest {
    String response;
    boolean error = false;
    int port;
    String serverHostname;
    int httpErrorCode;
    String request;
    String postRequest = null;

    public ServerRequest(String serverHostname, String request) {
        this.serverHostname = serverHostname;
        this.request = request;
        this.port = 80;
    }
    public ServerRequest(String serverHostname, String request, int port) {
        this.serverHostname = serverHostname;
        this.request = request;
        this.port = port;
    }

    public ServerRequest(String serverHostname, String request, String postMessage, int port) {
        this.serverHostname = serverHostname;
        this.request = request;
        this.port = port;
        this.postRequest = postMessage;
    }
    public String makeRequest() {
        HttpURLConnection connection;
        try {
            URL serverURL = new URL("http", serverHostname, 80, request);
            Log.d("MapApp", serverHostname);
            connection = (HttpURLConnection) serverURL.openConnection();
            if (postRequest != null) {
                Log.d("MapApp", "POST data: "+postRequest);
                connection.setRequestMethod("POST");
                connection.setDoOutput(true);
                OutputStream os = connection.getOutputStream();
                os.write(postRequest.getBytes());
                os.flush();
                os.close();
            }
            connection.connect();
            InputStream is;
            if ((httpErrorCode = connection.getResponseCode()) == 200) {
                is = connection.getInputStream();
            } else {
                error = true;
                is = connection.getErrorStream();
            }

            response = new BufferedReader(new InputStreamReader(is)).readLine();
            return response;

        } catch (MalformedURLException e) {
            Log.e("MapApp", "Caught MalformedURLException. serverHostname: "+serverHostname+". request: "+request);
            error = true;
            response =  "Malformed URL: Server name: "+serverHostname+" request: "+request;
            return response;
        } catch (IOException e) {
            Log.e("MapApp", "Caught IOException: "+e.getMessage());
            error = true;
            return "IO exception caught.";
        }
    }
}
