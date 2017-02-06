package com.example.tanjoodo.mapapp;

import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.support.v7.app.ActionBar;
import android.support.v7.app.AlertDialog;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.ListView;
import android.widget.TextView;

public class GroupView extends AppCompatActivity {

    String id;
    String [] family = {"Mom", "Dad", "Baby"};
    String [] friends = {"Khaled", "Ahmad", "Yousef"};
    Context c = this;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_group_view);
        Intent intent = getIntent();
        if (intent != null) {
            id = intent.getStringExtra(MyAdapter.EXTRA_GID);
        }

        Log.d("MapApp", id);
        ActionBar ab = getSupportActionBar();
        if (id.equals("1")) {
            ab.setTitle("Family");
        } else {
            ab.setTitle("Friends");
        }
        ListView lv = (ListView) findViewById(R.id.group_members_list);
        View.OnClickListener ibListener = new View.OnClickListener() {
            @Override
            public void onClick(View view) {
                View parent = (View) view.getParent();
                String member = ((TextView) parent.findViewById(R.id.list_item_content)).getText().toString();
                AlertDialog.Builder adb = new AlertDialog.Builder(c);
                adb.setTitle("Group member '" + member + "'");
                adb.setItems(new CharSequence[]{"Kick from group", "Invite to group", "Make admin"}, new DialogInterface.OnClickListener() {
                    @Override
                    public void onClick(DialogInterface dialogInterface, int i) {
                        switch(i) {
                            case 0:
                                break;
                            case 1:
                                break;
                            case 2:
                                break;
                        }
                    }
                });

                adb.show();
            }
        };
        if (id.equals("1")){
            lv.setAdapter(new MyAdapter(this, R.layout.list_item, family, null, ibListener));
        } else {
            lv.setAdapter(new MyAdapter(this, R.layout.list_item, friends, null, ibListener));
        }
    }
}
