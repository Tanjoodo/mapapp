package com.example.tanjoodo.mapapp;

import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.os.AsyncTask;
import android.support.v4.content.ContextCompat;
import android.support.v7.app.ActionBar;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.text.method.PasswordTransformationMethod;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.TextView;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.File;

public class Signin extends AppCompatActivity {

    public static int SIGN_IN_REQUEST = 2;
    boolean notAtStartup = false;
    final Context c = this;
    Intent goToMap;
    File tokenFile;

    TextView error_text;
    EditText phone;
    EditText password;

    class httpResponse {
        public String body;
        public int code;
        public httpResponse(String body, int code) {
            this.body = body;
            this.code = code;
        }

    }

    class SignInResponse implements AsyncResponse<httpResponse> {
        @Override
        public void processFinish(httpResponse param) {
            try {
                JSONObject json = new JSONObject(param.body);
                if (param.code == 200) {
                    String token = json.getString("tok");
                    MapView.setSavedToken(tokenFile, token);
                    startActivity(goToMap);
                } else {
                    String errorMessage = json.getString("error");
                    AppUtils.createSimpleDialog("Error signing you in", errorMessage, c).show();
                }
            } catch (JSONException e) {
                Log.e("MapApp", e.getMessage());
                Log.e("MapApp", param.body);
            }
        }
    }

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        tokenFile = new File(this.getFilesDir(), "token.txt");
        goToMap = new Intent(this, MapView.class);
        setContentView(R.layout.activity_signin);
        ActionBar ab = getSupportActionBar();
        if (ab != null) {
            ab.setTitle("Sign In");
        }

         error_text = (TextView) findViewById(R.id.error_view);
         phone =      (EditText) findViewById(R.id.phone);
         password =   (EditText) findViewById(R.id.password_text);

        Intent myIntent = getIntent();
        String myAction = myIntent.getAction();
        if (myAction != null && !myAction.equals(Intent.ACTION_MAIN)) {
           this.notAtStartup = true;
        } else {
            if (MapView.getSavedToken(tokenFile) != null) {
                Log.d("MapApp", "Login information present");
                startActivity(goToMap);
            }
        }


        Button signInButton = (Button) findViewById(R.id.sign_in_button);
        signInButton.setOnClickListener(new View.OnClickListener() {

            class SignInTask extends AsyncTask<String, Void, Void> {
                public AsyncResponse<httpResponse> delegate = null;
                public String response;
                ServerRequest sr;

                public SignInTask(AsyncResponse<httpResponse> delegate) {
                    this.delegate = delegate;
                }

                @Override
                protected Void doInBackground(String... params) {
                    String postData = "id="+params[0]+"&password="+params[1];
                    sr = new ServerRequest(MapView.serverHostname,
                            MapView.filePrefix+"login.php",
                            postData,
                            80);
                    sr.makeRequest();
                    Log.d("MapApp", sr.response);
                    return null;
                }

                @Override
                protected void onPostExecute(Void v) {
                    this.delegate.processFinish(new httpResponse(sr.response, sr.httpErrorCode));
                }
            }


            @Override
            public void onClick(View view) {
                if (!phone.getText().toString().equals("")) {
                   if (!password.getText().toString().equals("")) {
                       Log.d("MapApp", "Sign in clicked");
                       SignInTask signInTask = new SignInTask(new SignInResponse());
                       signInTask.execute(AppUtils.URLEncode(phone.getText().toString()), AppUtils.URLEncode(password.getText().toString()));
                   } else {
                       error_text.setText(R.string.error_password_empty);
                   }
                } else {
                    error_text.setText(R.string.error_phone_empty);
                }
            }
        });

        Button signUpButton = (Button) findViewById(R.id.sign_up_button);

        signUpButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                if (!phone.getText().toString().equals("")) {
                    if (!password.getText().toString().equals("")) {
                        AlertDialog.Builder adb = new AlertDialog.Builder(c);
                        adb.setTitle("Confirm password and sign up");
                        final EditText input = new EditText(c);
                        input.setTextColor(ContextCompat.getColor(c, R.color.black));
                        input.setTransformationMethod(PasswordTransformationMethod.getInstance());
                        input.setHint("Confirm password");
                        adb.setView(input);

                        adb.setPositiveButton("OK", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialogInterface, int i) {

                                if (!password.getText().toString().equals(input.getText().toString())) {
                                    input.setText("");
                                    error_text.setText(R.string.error_passwords_dont_match);
                                } else {
                                    signUp(phone.getText().toString(), password.getText().toString());
                                }
                            }
                        });

                        adb.setNegativeButton("Cancel", new DialogInterface.OnClickListener() {
                            @Override
                            public void onClick(DialogInterface dialogInterface, int i) {
                                dialogInterface.cancel();
                            }
                        });

                        adb.show();

                    } else {
                        error_text.setText(R.string.error_password_empty);
                    }
                } else {
                    error_text.setText(R.string.error_phone_empty);
                }
            }
        });

    }

    void signUp(String phone, String password) {
        class SignUpTask extends AsyncTask<String, Void, String> {
            ServerRequest sr;
           @Override
            protected String doInBackground(String... params) {
               sr = new ServerRequest(
                       MapView.serverHostname,
                       "~tanjoodo/mapapp_0.2/" + "signup.php/",
                       "id=" + AppUtils.URLEncode(params[0]) + "&password=" + AppUtils.URLEncode(params[1]),
                       80);
               Log.d("MapApp", AppUtils.URLEncode(params[0]));
               return sr.makeRequest();
           }

            @Override
            protected void onPostExecute(String result) {
                if (sr.httpErrorCode == 200) {
                    Log.d("MapApp", "Successful sign up");
                    String token;
                    try {
                        JSONObject json = new JSONObject(sr.response);
                        token = json.getString("tok");
                    } catch (JSONException e){
                        Log.d("MapApp", "Json parsing error.");
                        token = "";
                    }

                    MapView.setSavedToken(tokenFile, token);
                    startActivity(goToMap);
                } else {
                    Log.d("MapApp", "Sign up HTTP error "+sr.httpErrorCode);
                    String message = "Unknown error";
                    try {
                        JSONObject json = new JSONObject(sr.response);
                        message = json.getString("error");
                    } catch (JSONException e) {
                        Log.d("MapApp", "Error parsing JSON: " + sr.response);
                    }
                    AlertDialog.Builder adb = AppUtils.createSimpleDialog("Error signing you up:", message, c);
                    adb.show();
                }
            }
        }

        new SignUpTask().execute(phone, password);
    }

    @Override
    public void onBackPressed() {
        if (notAtStartup) {
            setResult(RESULT_CANCELED);
        }
        super.onBackPressed();
    }

}
