package com.example.tanjoodo.mapapp;

import android.content.Context;
import android.support.v7.app.AlertDialog;
import android.util.Log;

import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;

public class AppUtils {
    public static AlertDialog.Builder createSimpleDialog(String title, String message, Context c) {
        AlertDialog.Builder adb = new AlertDialog.Builder(c);
        adb.setTitle(title);
        adb.setMessage(message);
        return adb;
    }

    public static String URLEncode(String input) {
        try {
            return URLEncoder.encode(input, "UTF-8");
        } catch (UnsupportedEncodingException e) {
            Log.d("MapApp", e.getMessage());
            Log.d("MapApp", input);
            return input;
        }
    }
}
